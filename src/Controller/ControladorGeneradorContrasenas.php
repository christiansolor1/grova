<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\GeneradorContrasenas;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ControladorGeneradorContrasenas extends AbstractController
{
    #[Route('/generar-contrasena', name: 'generar_contrasena', methods: ['GET'])]
    public function generar(Request $request): JsonResponse
    {
        $tipo = $request->query->get('tipo', 'frase');

        if ($tipo === 'fuerte') {
            $contrasena = (new GeneradorContrasenas())->generarClaveFuerte();
        } else {
            $contrasena = (new GeneradorContrasenas())->generarFraseMemorable();
        }

        return $this->json([
            'contrasena'  => $contrasena,
            'fortaleza'   => GeneradorContrasenas::evaluarFortaleza($contrasena),
            'nivel'       => GeneradorContrasenas::nivelFortaleza(GeneradorContrasenas::evaluarFortaleza($contrasena)),
        ]);
    }
}
