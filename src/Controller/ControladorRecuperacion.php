<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\LogActividad;
use App\Service\Auth\ServicioLogActividad;
use App\Service\Auth\ServicioRecuperacionContrasena;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ControladorRecuperacion extends AbstractController
{
    public function __construct(
        private readonly ServicioRecuperacionContrasena $servicio,
        private readonly ServicioLogActividad $log,
        private readonly RateLimiterFactoryInterface $recuperacionAttemptLimiter,
        private readonly HttpClientInterface $httpClient,
        private readonly string $recaptchaSiteKey,
        private readonly string $recaptchaSecretKey,
    ) {
    }

    #[Route('/recuperar-contrasena', name: 'recuperar_contrasena', methods: ['GET'])]
    public function formulario(): Response
    {
        return $this->render('auth/recuperar_contrasena.html.twig', [
            'recaptcha_site_key' => $this->recaptchaSiteKey,
        ]);
    }

    #[Route('/recuperar-contrasena', name: 'recuperar_contrasena_enviar', methods: ['POST'])]
    public function enviar(Request $peticion): Response
    {
        $limiter = $this->recuperacionAttemptLimiter->create($peticion->getClientIp() ?? 'unknown');

        if (!$limiter->consume(1)->isAccepted()) {
            return $this->render('auth/recuperar_contrasena.html.twig', [
                'enviado' => true,
                'recaptcha_site_key' => $this->recaptchaSiteKey,
            ]);
        }

        if (!$this->isCsrfTokenValid('recuperar_contrasena', (string) $peticion->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('recuperar_contrasena');
        }

        // Validar reCAPTCHA v3
        $recaptchaToken = (string) $peticion->request->get('g-recaptcha-response', '');
        if (!$this->validarRecaptcha($recaptchaToken)) {
            $this->addFlash('error', 'No se pudo verificar que eres humano. Intenta de nuevo.');

            return $this->render('auth/recuperar_contrasena.html.twig', [
                'recaptcha_site_key' => $this->recaptchaSiteKey,
            ]);
        }

        $email = trim((string) $peticion->request->get('email', ''));

        if ($email !== '' && filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            $this->servicio->solicitarReset($email);
            $this->log->registrar(LogActividad::ACCION_RECUPERACION, email: $email);
        }

        // Siempre mostrar el mismo mensaje — no revelar si el email existe
        return $this->render('auth/recuperar_contrasena.html.twig', [
            'enviado' => true,
            'recaptcha_site_key' => $this->recaptchaSiteKey,
        ]);
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

    #[Route('/nueva-contrasena/{token}', name: 'nueva_contrasena', methods: ['GET'])]
    public function formularioNueva(string $token): Response
    {
        try {
            $this->servicio->validarToken($token);
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('recuperar_contrasena');
        }

        return $this->render('auth/nueva_contrasena.html.twig', ['token' => $token]);
    }

    #[Route('/nueva-contrasena/{token}', name: 'nueva_contrasena_guardar', methods: ['POST'])]
    public function guardar(string $token, Request $peticion): Response
    {
        if (!$this->isCsrfTokenValid('nueva_contrasena', (string) $peticion->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('nueva_contrasena', ['token' => $token]);
        }

        $nueva    = (string) $peticion->request->get('contrasena', '');
        $confirmar = (string) $peticion->request->get('contrasena_confirmar', '');

        if (strlen($nueva) < 8) {
            return $this->render('auth/nueva_contrasena.html.twig', [
                'token'  => $token,
                'errors' => ['contrasena' => 'La contraseña debe tener al menos 8 caracteres.'],
            ]);
        }

        if ($nueva !== $confirmar) {
            return $this->render('auth/nueva_contrasena.html.twig', [
                'token'  => $token,
                'errors' => ['contrasena_confirmar' => 'Las contraseñas no coinciden.'],
            ]);
        }

        try {
            $usuario = $this->servicio->cambiarContrasena($token, $nueva);
            $this->log->registrar(LogActividad::ACCION_CAMBIO_CONTRASENA, $usuario, $usuario->getEmail());
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('recuperar_contrasena');
        }

        $this->addFlash('success', 'Contraseña actualizada. Ya puedes iniciar sesión.');

        return $this->redirectToRoute('login');
    }
}
