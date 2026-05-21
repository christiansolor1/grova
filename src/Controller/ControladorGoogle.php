<?php

declare(strict_types=1);

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ControladorGoogle extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google')]
    public function redirigir(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry->getClient('google')->redirect(
            ['openid', 'email', 'profile'],
        );
    }

    /**
     * Esta ruta la maneja AuthenticatorGoogle.
     * Nunca debe ejecutarse este método.
     */
    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function verificar(): never
    {
        throw new \LogicException('Este método no debería ejecutarse.');
    }
}
