<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\LogActividad;
use App\Entity\User;
use App\Mail\CorreoCambioContrasena;
use App\Repository\LogActividadRepository;
use App\Repository\UserCredencialBiometricaRepository;
use App\Repository\UserLockRepository;
use App\Service\Auth\ServicioGeolocalizacion;
use App\Service\Auth\ServicioLogActividad;
use App\Service\MenuTreeBuilder;
use App\Service\NotificationService;
use App\Service\SectionLockService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ProfileController extends AbstractController
{
    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
        private readonly UserLockRepository $userLockRepo,
        private readonly UserCredencialBiometricaRepository $credencialRepo,
        private readonly LogActividadRepository $logRepo,
        private readonly ServicioGeolocalizacion $geo,
        private readonly ServicioLogActividad $logActividad,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly MailerInterface $mailer,
        private readonly NotificationService $notif,
        private readonly HttpClientInterface $httpClient,
        private readonly string $recaptchaSiteKey,
        private readonly string $recaptchaSecretKey,
    ) {
    }

    #[Route('/profile', name: 'grova_profile', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $tree = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));

        /** @var User $user */
        $user     = $this->getUser();
        $userLock = $this->userLockRepo->findOneBy(['user' => $user]);

        $credenciales = $this->credencialRepo->findByUser($user);

        $sesiones = $this->logRepo->findSesionesIndividuales($user);
        foreach ($sesiones as &$sesion) {
            $sesion['ubicacion'] = $this->geo->localizar($sesion['ip']);
        }
        unset($sesion);

        $actividadSeguridad = $this->logRepo->findActividadSeguridad($user);
        foreach ($actividadSeguridad as &$evento) {
            $evento['ubicacion'] = $this->geo->localizar($evento['ip']);
        }
        unset($evento);

        return $this->render('workspace/pages/profile/indexProfile.html.twig', [
            'menu_tree'               => $tree,
            'active_menu_key'         => 'profile-user',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'user_lock'               => $userLock,
            'lock_sections'           => SectionLockService::SECTIONS,
            'credenciales_biometricas' => $credenciales,
            'sesiones'                => $sesiones,
            'actividad_seguridad'     => $actividadSeguridad,
            'recaptcha_site_key'      => $this->recaptchaSiteKey,
            'current_session_token'   => $request->getSession()->get('_session_token'),
        ]);
    }

    #[Route('/profile/cambiar-contrasena', name: 'grova_profile_cambiar_contrasena', methods: ['POST'])]
    public function cambiarContrasena(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('cambiar_contrasena', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');

            return $this->redirectToRoute('grova_profile');
        }

        // Validar reCAPTCHA v3
        $recaptchaToken = (string) $request->request->get('g-recaptcha-response', '');
        if (!$this->validarRecaptcha($recaptchaToken)) {
            $this->addFlash('error', 'No se pudo verificar que eres humano. Intenta de nuevo.');

            return $this->redirectToRoute('grova_profile');
        }

        $actual    = (string) $request->request->get('contrasena_actual', '');
        $nueva     = (string) $request->request->get('contrasena_nueva', '');
        $confirmar = (string) $request->request->get('contrasena_confirmar', '');

        // Validar contraseña actual
        if (!$this->hasher->isPasswordValid($user, $actual)) {
            $this->addFlash('error', 'La contraseña actual no es correcta.');

            return $this->redirectToRoute('grova_profile');
        }

        // Validar nueva contraseña
        if (strlen($nueva) < 8) {
            $this->addFlash('error', 'La nueva contraseña debe tener al menos 8 caracteres.');

            return $this->redirectToRoute('grova_profile');
        }

        if ($nueva !== $confirmar) {
            $this->addFlash('error', 'Las contraseñas nuevas no coinciden.');

            return $this->redirectToRoute('grova_profile');
        }

        // Cambiar contraseña
        $user->setPassword($this->hasher->hashPassword($user, $nueva));
        $user->incrementarSessionVersion();
        $this->em->flush();

        // Actualizar la sesión actual para no cerrarse a sí misma
        $request->getSession()->set('_session_version', $user->getSessionVersion());

        // Registrar en log
        $this->logActividad->registrar(LogActividad::ACCION_CAMBIO_CONTRASENA, $user, $user->getEmail(), [
            'ip'        => $request->getClientIp(),
            'userAgent' => $request->headers->get('User-Agent'),
        ]);

        // Notificar por email
        try {
            $this->mailer->send(new CorreoCambioContrasena($user, [
                'ip'        => $request->getClientIp(),
                'userAgent' => $request->headers->get('User-Agent'),
            ]));
        } catch (\Throwable $e) {
            // El error en el correo no debe impedir el cambio de contraseña
            $this->logActividad->registrar('email_fallo', $user, $user->getEmail(), [
                'error' => $e->getMessage(),
            ]);
        }

        // Notificación en la campana
        $this->notif->notify(
            user: $user,
            title: 'Contraseña cambiada',
            body: 'Tu contraseña fue actualizada correctamente.',
            url: $this->generateUrl('grova_profile', ['_locale' => $request->getLocale()]),
            module: 'core',
            icon: 'bi-key',
            type: 'info',
        );

        $this->addFlash('success', 'Contraseña actualizada correctamente. Las otras sesiones han sido cerradas.');

        return $this->redirectToRoute('grova_profile');
    }

    #[Route('/profile/cerrar-sesiones', name: 'grova_profile_cerrar_sesiones', methods: ['POST'])]
    public function cerrarOtrasSesiones(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('cerrar_sesiones', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');

            return $this->redirectToRoute('grova_profile');
        }

        $user->incrementarSessionVersion();
        $this->em->flush();

        // Actualizar la sesión actual con la nueva versión para no cerrarse a sí misma
        $request->getSession()->set('_session_version', $user->getSessionVersion());

        $this->addFlash('success', 'Todas las demás sesiones han sido cerradas.');

        return $this->redirectToRoute('grova_profile');
    }

    #[Route('/profile/cerrar-dispositivo/{sessionToken}', name: 'grova_profile_cerrar_dispositivo', methods: ['POST'])]
    public function cerrarDispositivo(Request $request, string $sessionToken): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('cerrar_dispositivo', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');

            return $this->redirectToRoute('grova_profile');
        }

        $currentToken = $request->getSession()->get('_session_token');

        // No permitir cerrar la propia sesión actual
        if ($sessionToken === $currentToken) {
            $this->addFlash('error', 'No puedes cerrar tu sesión actual desde aquí. Usa "Cerrar sesión" en el menú.');

            return $this->redirectToRoute('grova_profile');
        }

        $user->revocarSessionToken($sessionToken);
        $user->limpiarRevokedSessionTokens();
        $this->em->flush();

        $this->addFlash('success', 'El dispositivo ha sido desconectado.');

        return $this->redirectToRoute('grova_profile');
    }

    #[Route('/profile/preferencias', name: 'grova_profile_preferencias', methods: ['POST'])]
    public function guardarPreferencias(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('preferencias', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');

            return $this->redirectToRoute('grova_profile');
        }

        $locale = (string) $request->request->get('locale', '');
        if (in_array($locale, ['es', 'en'], true)) {
            $user->setPreferredLocale($locale);
        }

        $theme = (string) $request->request->get('theme', '');
        if (in_array($theme, ['dark', 'light'], true)) {
            $user->setPreferredTheme($theme);
        }

        $this->em->flush();

        // Actualizar cookie de idioma para que el cambio sea inmediato
        $response = $this->redirectToRoute('grova_profile', ['_locale' => $locale ?: $request->getLocale()]);
        $response->headers->setCookie(new \Symfony\Component\HttpFoundation\Cookie('grova_locale', $user->getPreferredLocale() ?? 'es', time() + 365 * 86400));
        $response->headers->setCookie(new \Symfony\Component\HttpFoundation\Cookie('grova_theme', $user->getPreferredTheme() ?? 'dark', time() + 365 * 86400));

        $this->addFlash('success', 'Preferencias guardadas correctamente.');

        return $response;
    }

    private function validarRecaptcha(string $token): bool
    {
        if ($token === '') {
            return false;
        }

        try {
            $respuesta = $this->httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret'   => $this->recaptchaSecretKey,
                    'response' => $token,
                ],
            ]);

            $datos = $respuesta->toArray();

            return ($datos['success'] ?? false) === true;
        } catch (\Throwable) {
            return false;
        }
    }
}
