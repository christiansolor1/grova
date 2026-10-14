<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Setup\EstadoInstalacion;
use App\Service\Setup\InstaladorGrova;
use App\Service\Setup\SolicitudInstalacion;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

final class ControladorInstalacion extends AbstractController
{
    public function __construct(
        private readonly EstadoInstalacion $estadoInstalacion,
        private readonly InstaladorGrova $instalador,
        private readonly RateLimiterFactory $setupAttemptLimiter,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/setup', name: 'app_setup', methods: ['GET'])]
    public function indice(): Response
    {
        if ($this->estadoInstalacion->estaInstalado()) {
            throw $this->createNotFoundException();
        }

        return $this->render('setup/indexSetup.html.twig');
    }

    #[Route('/setup', name: 'app_setup_install', methods: ['POST'])]
    public function instalar(Request $peticion): Response
    {
        if ($this->estadoInstalacion->estaInstalado()) {
            throw $this->createNotFoundException();
        }

        // Rate limiting por IP
        $limitador = $this->setupAttemptLimiter->create($peticion->getClientIp());
        if (!$limitador->consume(1)->isAccepted()) {
            return $this->render('setup/indexSetup.html.twig', [
                'form'   => $peticion->request->all(),
                'errors' => ['install' => 'Demasiados intentos. Espera unos minutos e inténtalo de nuevo.'],
            ]);
        }

        if (!$this->isCsrfTokenValid('grova_setup', (string) $peticion->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido. Recarga la página e inténtalo de nuevo.');

            return $this->redirectToRoute('app_setup');
        }

        $solicitud = SolicitudInstalacion::desdeFormulario($peticion->request->all());
        $errores   = $this->validarSolicitud($solicitud, $peticion);

        if ($errores !== []) {
            return $this->render('setup/indexSetup.html.twig', [
                'form'   => $peticion->request->all(),
                'errors' => $errores,
            ]);
        }

        try {
            $this->instalador->instalar($solicitud);
        } catch (\Throwable $e) {
            // Loguear el error completo internamente, nunca mostrarlo al usuario
            $this->logger->error('Error durante la instalación de Grova', [
                'error'   => $e->getMessage(),
                'host_bd' => $solicitud->hostBd,
                'nombre_bd' => $solicitud->nombreBd,
            ]);

            return $this->render('setup/indexSetup.html.twig', [
                'form'   => $peticion->request->all(),
                'errors' => ['install' => $this->mensajeAmigable($e)],
            ]);
        }

        $this->addFlash('success', '¡Grova se instaló correctamente! Ya puedes iniciar sesión.');

        return $this->redirectToRoute('login', ['_locale' => 'es']);
    }

    private function mensajeAmigable(\Throwable $e): string
    {
        $msg = $e->getMessage();

        if (str_contains($msg, 'Access denied') || str_contains($msg, '1045')) {
            return 'No se pudo conectar a la base de datos: usuario o contraseña incorrectos.';
        }

        if (str_contains($msg, 'Connection refused') || str_contains($msg, 'No such host') || str_contains($msg, '2002')) {
            return 'No se pudo conectar al servidor de base de datos. Verifica el host y el puerto.';
        }

        if (str_contains($msg, 'No se pudo escribir')) {
            return 'Error de permisos: no se pudo escribir la configuración. Contacta al administrador del servidor.';
        }

        if (str_contains($msg, 'No se pudo generar')) {
            return 'Error al generar las claves de seguridad. Verifica que PHP tenga la extensión OpenSSL activa.';
        }

        if (str_contains($msg, 'Migration')) {
            return 'Error al ejecutar las migraciones de base de datos. Verifica que el usuario tenga permisos suficientes.';
        }

        return 'Ocurrió un error durante la instalación. Revisa los logs del servidor para más detalles.';
    }

    /**
     * @return array<string, string>
     */
    private function validarSolicitud(SolicitudInstalacion $solicitud, Request $peticion): array
    {
        $errores = [];

        // Host: solo hostname o IP válida
        if ($solicitud->hostBd === '') {
            $errores['db_host'] = 'Indica el host de la base de datos.';
        } elseif (
            !filter_var($solicitud->hostBd, \FILTER_VALIDATE_IP) &&
            !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,253}[a-zA-Z0-9]$/', $solicitud->hostBd) &&
            $solicitud->hostBd !== 'localhost'
        ) {
            $errores['db_host'] = 'Host inválido. Usa una IP, hostname o "localhost".';
        }

        if ($solicitud->nombreBd === '') {
            $errores['db_name'] = 'Indica el nombre de la base de datos.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{1,64}$/', $solicitud->nombreBd)) {
            $errores['db_name'] = 'Solo letras, números y guión bajo (máximo 64 caracteres).';
        }

        if ($solicitud->usuarioBd === '') {
            $errores['db_user'] = 'Indica el usuario de la base de datos.';
        }

        if ($solicitud->contrasenaBd === '') {
            $errores['db_password'] = 'Indica la contraseña de la base de datos.';
        }

        if ($solicitud->usernameAdmin === '') {
            $errores['admin_username'] = 'Indica un nombre de usuario.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,32}$/', $solicitud->usernameAdmin)) {
            $errores['admin_username'] = 'Entre 3 y 32 caracteres: letras, números y guión bajo.';
        }

        if ($solicitud->emailAdmin === '' || !filter_var($solicitud->emailAdmin, \FILTER_VALIDATE_EMAIL)) {
            $errores['admin_email'] = 'Indica un email válido.';
        }

        // Contraseña con requisitos mínimos de complejidad
        $pass = $solicitud->contrasenaAdmin;
        if (strlen($pass) < 10) {
            $errores['admin_password'] = 'La contraseña debe tener al menos 10 caracteres.';
        } elseif (!preg_match('/[A-Z]/', $pass) || !preg_match('/[0-9]/', $pass)) {
            $errores['admin_password'] = 'La contraseña debe incluir al menos una mayúscula y un número.';
        }

        $confirmacion = (string) $peticion->request->get('admin_password_confirm', '');
        if ($solicitud->contrasenaAdmin !== $confirmacion) {
            $errores['admin_password_confirm'] = 'Las contraseñas no coinciden.';
        }

        if ($solicitud->nombreWorkspace === '') {
            $errores['workspace_name'] = 'Indica el nombre de tu empresa o workspace.';
        }

        return $errores;
    }
}
