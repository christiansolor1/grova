<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Auth\ServicioVerificacionEmail;
use App\Service\Tenant\RegistradorTenant;
use App\Service\Tenant\SolicitudRegistro;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ControladorRegistro extends AbstractController
{
    public function __construct(
        private readonly RegistradorTenant $registrador,
        private readonly ServicioVerificacionEmail $verificacion,
        private readonly RateLimiterFactoryInterface $registroAttemptLimiter,
    ) {
    }

    #[Route('/registro', name: 'registro', methods: ['GET'])]
    public function indice(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('login');
        }

        return $this->render('registro/indexRegistro.html.twig');
    }

    #[Route('/registro', name: 'registro_crear', methods: ['POST'])]
    public function crear(Request $peticion): Response
    {
        $limiter = $this->registroAttemptLimiter->create($peticion->getClientIp() ?? 'unknown');

        if (!$limiter->consume(1)->isAccepted()) {
            $this->addFlash('error', 'Demasiados intentos de registro. Espera un momento e inténtalo de nuevo.');

            return $this->redirectToRoute('registro');
        }

        if (!$this->isCsrfTokenValid('grova_registro', (string) $peticion->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido. Recarga la página e inténtalo de nuevo.');

            return $this->redirectToRoute('registro');
        }

        $errores = $this->validar($peticion);

        if ($errores !== []) {
            return $this->render('registro/indexRegistro.html.twig', [
                'form'   => $peticion->request->all(),
                'errors' => $errores,
            ]);
        }

        $solicitud = new SolicitudRegistro(
            nombreEmpresa: trim((string) $peticion->request->get('empresa', '')),
            nombre:        trim((string) $peticion->request->get('nombre', '')),
            apellido:      trim((string) $peticion->request->get('apellido', '')),
            email:         trim((string) $peticion->request->get('email', '')),
            contrasena:    (string) $peticion->request->get('contrasena', ''),
            slug:          trim((string) $peticion->request->get('slug', '')),
        );

        try {
            $this->registrador->registrar($solicitud);
        } catch (\DomainException $e) {
            $campo = str_contains($e->getMessage(), 'workspace') ? 'slug' : 'email';

            return $this->render('registro/indexRegistro.html.twig', [
                'form'   => $peticion->request->all(),
                'errors' => [$campo => $e->getMessage()],
            ]);
        } catch (\Throwable $e) {
            return $this->render('registro/indexRegistro.html.twig', [
                'form'   => $peticion->request->all(),
                'errors' => ['general' => 'No se pudo crear la cuenta: '.$e->getMessage()],
            ]);
        }

        return $this->render('registro/confirmacion.html.twig', [
            'email' => $solicitud->email,
        ]);
    }

    #[Route('/registro/slug-disponible', name: 'registro_slug_disponible', methods: ['GET'])]
    public function slugDisponible(Request $peticion): JsonResponse
    {
        $slug      = trim((string) $peticion->query->get('slug', ''));
        $sanitized = $this->registrador->sanitizarSlug($slug);
        $disponible = $sanitized !== '' && $this->registrador->slugDisponible($slug);

        return $this->json([
            'disponible' => $disponible,
            'slug'       => $sanitized,
        ]);
    }

    #[Route('/verificar-email/{token}', name: 'verificar_email', methods: ['GET'])]
    public function verificarEmail(string $token): Response
    {
        try {
            $usuario = $this->verificacion->verificar($token);
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('login');
        }

        $this->addFlash('success', '¡Email verificado! Ya puedes iniciar sesión.');

        // Pre-rellena el campo con el username (slug) para que el usuario sepa con qué entrar
        return $this->redirectToRoute('login', ['_username' => $usuario->getUsername()]);
    }

    #[Route('/registro/reenviar-verificacion', name: 'reenviar_verificacion', methods: ['POST'])]
    public function reenviarVerificacion(Request $peticion): Response
    {
        if (!$this->isCsrfTokenValid('reenviar_verificacion', (string) $peticion->request->get('_token'))) {
            $this->addFlash('error', 'Token inválido.');

            return $this->redirectToRoute('login');
        }

        $email = trim((string) $peticion->request->get('email', ''));
        $this->verificacion->reenviar($email);

        $this->addFlash('success', 'Si el email existe y no está verificado, recibirás un nuevo enlace.');

        return $this->redirectToRoute('login');
    }

    /** @return array<string, string> */
    private function validar(Request $peticion): array
    {
        $errores = [];

        $empresa    = trim((string) $peticion->request->get('empresa', ''));
        $nombre     = trim((string) $peticion->request->get('nombre', ''));
        $email      = trim((string) $peticion->request->get('email', ''));
        $slug       = trim((string) $peticion->request->get('slug', ''));
        $contrasena = (string) $peticion->request->get('contrasena', '');
        $confirmar  = (string) $peticion->request->get('contrasena_confirmar', '');

        if ($empresa === '') {
            $errores['empresa'] = 'Indica el nombre de tu empresa o workspace.';
        }

        if ($nombre === '') {
            $errores['nombre'] = 'Indica tu nombre.';
        }

        if ($email === '' || !filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            $errores['email'] = 'Indica un email válido.';
        }

        if ($slug === '') {
            $errores['slug'] = 'Elige un nombre para tu workspace.';
        } elseif ($this->registrador->sanitizarSlug($slug) === '') {
            $errores['slug'] = 'El nombre solo puede tener letras, números y guiones.';
        }

        if (strlen($contrasena) < 8) {
            $errores['contrasena'] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        if ($contrasena !== $confirmar) {
            $errores['contrasena_confirmar'] = 'Las contraseñas no coinciden.';
        }

        return $errores;
    }
}
