<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ExchangeRateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Página pública de tasas de cambio — no requiere autenticación.
 * Muestra las tasas USD/EUR → HNL de los bancos hondureños.
 */
#[Route('/{_locale}/tasas-cambio', name: 'grova_tasas_cambio_')]
final class ControladorTasasCambio extends AbstractController
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRate,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $rates = $this->exchangeRate->getRates();

        return $this->render('public/tasasCambio.html.twig', [
            'rates' => $rates,
        ]);
    }
}
