<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\ExchangeRateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/api/rates', name: 'api_rates_')]
class ExchangeRateApiController extends AbstractController
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRateService,
    ) {}

    #[Route('', name: 'get', methods: ['GET'])]
    public function rates(): JsonResponse
    {
        $tasas = $this->exchangeRateService->getRates();

        return $this->json([
            'eur'    => $tasas['eur'],
            'usd'    => $tasas['usd'],
            'fecha'  => $tasas['fecha'],
            'source' => $tasas['source'],
        ]);
    }
}
