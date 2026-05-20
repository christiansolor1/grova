<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Setup\EstadoInstalacion;
use App\Service\Setup\InstaladorGrova;
use App\Service\Setup\SolicitudInstalacion;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Routing\Attribute\Route;

final class ControladorInstalacion extends AbstractController
{
    public function __construct(
        private readonly EstadoInstalacion $estadoInstalacion,
        private readonly InstaladorGrova $instalador,
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

        if (!$this->isCsrfTokenValid('grova_setup', (string) $peticion->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido. Recarga la página e inténtalo de nuevo.');

            return $this->redirectToRoute('app_setup');
        }

        $solicitud = SolicitudInstalacion::desdeFormulario($peticion->request->all());
        $errores = $this->validarSolicitud($solicitud, $peticion);

        if ($errores !== []) {
            return $this->render('setup/indexSetup.html.twig', [
                'form' => $peticion->request->all(),
                'errors' => $errores,
            ]);
        }

        try {
            $this->instalador->instalar($solicitud);
        } catch (ProcessFailedException $e) {
            $salida = trim($e->getProcess()->getErrorOutput()."\n".$e->getProcess()->getOutput());

            return $this->render('setup/indexSetup.html.twig', [
                'form' => $peticion->request->all(),
                'errors' => ['install' => 'Error durante la instalación: '.($salida !== '' ? $salida : $e->getMessage())],
            ]);
        } catch (\Throwable $e) {
            return $this->render('setup/indexSetup.html.twig', [
                'form' => $peticion->request->all(),
                'errors' => ['install' => 'Error durante la instalación: '.$e->getMessage()],
            ]);
        }

        $this->addFlash('success', '¡Grova se instaló correctamente! Ya puedes iniciar sesión.');

        return $this->redirectToRoute('login', ['_locale' => 'es']);
    }

    /**
     * @return array<string, string>
     */
    private function validarSolicitud(SolicitudInstalacion $solicitud, Request $peticion): array
    {
        $errores = [];

        if ($solicitud->hostBd === '') {
            $errores['db_host'] = 'Indica el host de la base de datos.';
        }

        if ($solicitud->nombreBd === '') {
            $errores['db_name'] = 'Indica el nombre de la base de datos.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $solicitud->nombreBd)) {
            $errores['db_name'] = 'Solo letras, números y guión bajo.';
        }

        if ($solicitud->usuarioBd === '') {
            $errores['db_user'] = 'Indica el usuario de la base de datos.';
        }

        if ($solicitud->contrasenaBd === '') {
            $errores['db_password'] = 'Indica la contraseña de la base de datos (en local suele ser la de tu usuario MySQL).';
        }

        if ($solicitud->emailAdmin === '' || !filter_var($solicitud->emailAdmin, \FILTER_VALIDATE_EMAIL)) {
            $errores['admin_email'] = 'Indica un email válido.';
        }

        if (strlen($solicitud->contrasenaAdmin) < 8) {
            $errores['admin_password'] = 'La contraseña debe tener al menos 8 caracteres.';
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
