<?php

declare(strict_types=1);

namespace App\Security\TwoFactor;

use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class FormularioMFA implements TwoFactorFormRendererInterface
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function renderForm(Request $request, array $templateVars): Response
    {
        return new Response(
            $this->twig->render('security/2fa_login.html.twig', $templateVars)
        );
    }
}
