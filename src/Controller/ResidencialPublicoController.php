<?php

declare(strict_types=1);

namespace App\Controller;

use App\Module\Residencial\Repository\PaseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Vista pública del pase — no requiere autenticación.
 * El visitante accede con el enlace que le mandó el residente.
 */
final class ResidencialPublicoController extends AbstractController
{
    public function __construct(
        private readonly PaseRepository $paseRepo,
    ) {
    }

    #[Route('/pase/{token}', name: 'grova_residencial_pase_publico')]
    public function verPase(string $token): Response
    {
        $pase = $this->paseRepo->findByToken($token);

        if ($pase === null) {
            return $this->render('residencial/pase_no_encontrado.html.twig', [], new Response('', 404));
        }

        return $this->render('residencial/pase_publico.html.twig', [
            'pase' => $pase,
        ]);
    }
}
