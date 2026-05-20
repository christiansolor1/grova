<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserCodigoEmail2FA;
use App\Repository\UserCredencialBiometricaRepository;
use App\Repository\UserLockRepository;
use App\Security\TwoFactor\ProveedorMFA;
use App\Service\Auth\ServicioCodigoEmail2FA;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use lbuchs\WebAuthn\WebAuthn;
use OTPHP\TOTP;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ControladorDosFactores extends AbstractController
{
    private const SESSION_KEY = '2fa_setup_secret';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserLockRepository $lockRepo,
        private readonly UserCredencialBiometricaRepository $credencialRepo,
        private readonly ServicioCodigoEmail2FA $servicioCodigoEmail,
    ) {
    }

    #[Route('/2fa', name: '2fa_login', methods: ['GET'])]
    public function login(Request $request): Response
    {
        /** @var User|null $usuario */
        $usuario         = $this->getUser();
        $tieneTotp       = ($usuario?->isTotpAuthenticationEnabled() && $usuario?->isTotp2faEnabled()) ?? false;
        $credenciales    = $usuario ? $this->credencialRepo->findByUser($usuario) : [];
        $tieneBiometrico = count($credenciales) > 0 && ($usuario?->isWebauthn2faEnabled() ?? false);
        $userLock        = $usuario ? $this->lockRepo->findOneBy(['user' => $usuario]) : null;
        $tienePin        = $userLock !== null && $userLock->hasPin() && ($usuario?->isPin2faEnabled() ?? false);

        $tieneEmail = $usuario !== null && $usuario->isEmail2faEnabled();

        return $this->render('security/2fa_login.html.twig', [
            'authenticationError' => null,
            'tiene_totp'          => $tieneTotp,
            'tiene_biometrico'    => $tieneBiometrico,
            'tiene_pin'           => $tienePin,
            'tiene_email'         => $tieneEmail,
        ]);
    }

    #[Route('/2fa/check', name: '2fa_login_check', methods: ['POST'])]
    public function check(): never
    {
        throw new \LogicException('Este método lo intercepta el firewall de Symfony.');
    }

    /** Genera el challenge WebAuthn para usar biométrico como segundo factor. */
    #[Route('/2fa/webauthn/challenge', name: '2fa_webauthn_challenge', methods: ['GET'])]
    public function webauthnChallenge(Request $request): JsonResponse
    {
        try {
            /** @var User $usuario */
            $usuario      = $this->getUser();
            $credenciales = $this->credencialRepo->findByUser($usuario);

            if (count($credenciales) === 0) {
                return new JsonResponse(['error' => 'Biométrico no configurado'], 400);
            }

            $ids  = array_map(fn($c) => base64_decode($c->getCredentialId()), $credenciales);
            $wa   = $this->buildWebAuthn($request);
            $args = $wa->getGetArgs($ids, 20, true, true, true, true, true, true);

            $request->getSession()->set('_wa_2fa_challenge', $wa->getChallenge()->getBinaryString());

            return new JsonResponse($args);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /** Verifica la respuesta WebAuthn y emite un token de un solo uso para el proveedor MFA. */
    #[Route('/2fa/webauthn/verify', name: '2fa_webauthn_verify', methods: ['POST'])]
    public function webauthnVerify(Request $request): JsonResponse
    {
        try {
            /** @var User $usuario */
            $usuario      = $this->getUser();
            $challenge    = $request->getSession()->get('_wa_2fa_challenge');

            if ($challenge === null) {
                return new JsonResponse(['error' => 'Sesión expirada'], 400);
            }

            $datos        = json_decode((string) $request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
            $credentialId = base64_encode($this->b64Decode((string) ($datos['id'] ?? '')));
            $credencial   = $this->credencialRepo->findByCredentialId($credentialId);

            if ($credencial === null || $credencial->getUser()->getId() !== $usuario->getId()) {
                return new JsonResponse(['error' => 'Credencial no encontrada'], 401);
            }

            $wa = $this->buildWebAuthn($request);
            $wa->processGet(
                $this->b64Decode((string) ($datos['clientDataJSON'] ?? '')),
                $this->b64Decode((string) ($datos['authenticatorData'] ?? '')),
                $this->b64Decode((string) ($datos['signature'] ?? '')),
                $credencial->getPublicKey(),
                $challenge,
                null,
                true,
                true,
            );

            $credencial->marcarUso();
            $this->em->flush();

            $request->getSession()->remove('_wa_2fa_challenge');

            // Token de un solo uso que el JS envía como código al endpoint 2fa_login_check
            $token = bin2hex(random_bytes(16));
            $request->getSession()->set(ProveedorMFA::WA_SESSION_KEY, [
                'token'   => $token,
                'user_id' => $usuario->getId(),
            ]);

            return new JsonResponse(['token' => ProveedorMFA::WA_TOKEN_PREFIX . $token]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    private function buildWebAuthn(Request $request): WebAuthn
    {
        $rpId = $request->isSecure() ? $request->getHost() : 'localhost';

        return new WebAuthn('Grova', $rpId, null, true);
    }

    private function b64Decode(string $s): string
    {
        $s   = str_replace(['-', '_'], ['+', '/'], $s);
        $pad = strlen($s) % 4;
        if ($pad !== 0) {
            $s .= str_repeat('=', 4 - $pad);
        }

        return (string) base64_decode($s, true);
    }

    /** Envía un código de verificación por email. */
    #[Route('/2fa/enviar-codigo-email', name: '2fa_enviar_codigo_email', methods: ['POST'])]
    public function enviarCodigoEmail(Request $request): JsonResponse
    {
        /** @var User|null $usuario */
        $usuario = $this->getUser();

        if (!$usuario instanceof User) {
            return new JsonResponse(['error' => 'No autenticado'], 401);
        }

        try {
            $this->servicioCodigoEmail->enviarCodigo($usuario);

            return new JsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'No se pudo enviar el código. Intenta de nuevo.'], 500);
        }
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/perfil/2fa/configurar', name: '2fa_setup', methods: ['GET'])]
    public function configurar(Request $request): Response
    {
        /** @var User $usuario */
        $usuario = $this->getUser();

        if ($usuario->isTotpAuthenticationEnabled()) {
            $this->addFlash('info', 'El doble factor ya está activado.');

            return $this->redirectToRoute('grova_profile');
        }

        $secreto = TOTP::generate()->getSecret();
        $request->getSession()->set(self::SESSION_KEY, $secreto);

        $totp = TOTP::createFromSecret($secreto);
        $totp->setLabel($usuario->getEmail() ?? $usuario->getUsername() ?? 'usuario');
        $totp->setIssuer('Grova');
        $qrContenido = $totp->getProvisioningUri();

        $builder   = new Builder(writer: new PngWriter());
        $resultado = $builder->build(
            data: $qrContenido,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 240,
            margin: 0,
        );

        return $this->render('security/2fa_setup.html.twig', [
            'qr_data_uri' => $resultado->getDataUri(),
            'secreto'     => $secreto,
        ]);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/perfil/2fa/activar', name: '2fa_activar', methods: ['POST'])]
    public function activar(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('2fa_activar', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');

            return $this->redirectToRoute('2fa_setup');
        }

        $secreto = $request->getSession()->get(self::SESSION_KEY);

        if (!$secreto) {
            $this->addFlash('error', 'Sesión expirada. Inicia el proceso nuevamente.');

            return $this->redirectToRoute('2fa_setup');
        }

        /** @var User $usuario */
        $usuario = $this->getUser();
        $usuario->setTotpSecret($secreto);

        $codigo = (string) $request->request->get('codigo', '');

        $totp = TOTP::createFromSecret($secreto);
        $totp->setPeriod(30);

        if (!$totp->verify($codigo, null, 1)) {
            $usuario->setTotpSecret(null);
            $this->addFlash('error', 'Código incorrecto. Asegúrate de que la app esté sincronizada.');

            return $this->redirectToRoute('2fa_setup');
        }

        $usuario->setTotp2faEnabled(true);
        $this->em->flush();
        $request->getSession()->remove(self::SESSION_KEY);

        $this->addFlash('success', '¡Doble factor activado! Tu cuenta está más segura.');

        return $this->redirectToRoute('grova_profile');
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/perfil/2fa/desactivar', name: '2fa_desactivar', methods: ['POST'])]
    public function desactivar(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('2fa_desactivar', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');

            return $this->redirectToRoute('grova_profile');
        }

        /** @var User $usuario */
        $usuario = $this->getUser();
        $usuario->setTotpSecret(null);
        $usuario->setTotp2faEnabled(false);
        $this->em->flush();

        $this->addFlash('success', 'Doble factor desactivado.');

        return $this->redirectToRoute('grova_profile');
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/perfil/2fa/preferencias', name: '2fa_preferencias', methods: ['POST'])]
    public function guardarPreferencias(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('2fa_preferencias', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');

            return $this->redirectToRoute('grova_profile');
        }

        /** @var User $usuario */
        $usuario = $this->getUser();

        $webauthnRequested = (bool) $request->request->get('webauthn_2fa');
        $pinRequested      = (bool) $request->request->get('pin_2fa');
        $totpRequested     = (bool) $request->request->get('totp_2fa');
        $emailRequested    = (bool) $request->request->get('email_2fa');

        // TOTP: si solicita activar pero no tiene secreto → ir a configuración
        if ($totpRequested && !$usuario->isTotpAuthenticationEnabled()) {
            $this->em->flush();

            return $this->redirectToRoute('2fa_setup');
        }

        // TOTP: guardar preferencia (el secreto se conserva aunque esté desmarcado)
        $usuario->setTotp2faEnabled($totpRequested);

        $usuario->setWebauthn2faEnabled($webauthnRequested);
        $usuario->setPin2faEnabled($pinRequested);
        $usuario->setEmail2faEnabled($emailRequested);
        $this->em->flush();

        $this->addFlash('success', 'Preferencias de verificación guardadas.');

        return $this->redirectToRoute('grova_profile');
    }
}
