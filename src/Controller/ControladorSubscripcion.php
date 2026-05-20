<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ControladorSubscripcion extends AbstractController
{
    #[Route('/subscripcion/vencida', name: 'app_subscripcion_vencida', methods: ['GET'])]
    public function vencida(): Response
    {
        return $this->render('subscripcion/vencida.html.twig');
    }
}
