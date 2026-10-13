<?php

declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Service;

use App\Module\Personal\FinancialPlanning\Entity\FinCompromiso;
use App\Module\Personal\FinancialPlanning\Entity\FinCuota;
use App\Module\Personal\FinancialPlanning\Repository\FinCompromisoRepository;
use App\Module\Personal\FinancialPlanning\Repository\FinCuotaRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Genera las FinCuota del mes para todos los compromisos activos de un tenant.
 * Es idempotente: no duplica si ya existe una cuota para ese compromiso en ese mes.
 */
final class FinCuotaGeneradorService
{
    public function __construct(
        private readonly FinCompromisoRepository $compromisoRepo,
        private readonly FinCuotaRepository $cuotaRepo,
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * @return array{creadas: int, omitidas: int, detalle: list<string>}
     */
    public function generarMes(int $tenantId, int $anio, int $mes): array
    {
        $compromisos = $this->compromisoRepo->findActivosByTenant($tenantId);
        $creadas     = 0;
        $omitidas    = 0;
        $detalle     = [];

        foreach ($compromisos as $compromiso) {
            if (!$compromiso->aplicaEnMes($anio, $mes)) {
                continue;
            }

            $slots = $this->calcularSlots($compromiso, $anio, $mes);

            foreach ($slots as $fechaVencimiento) {
                // Verificar si ya existe una cuota con esa fecha exacta
                $existentes = $this->cuotaRepo->findByCompromisoMes($compromiso->getId(), $anio, $mes);
                $yaExiste   = false;
                foreach ($existentes as $e) {
                    if ($e->getFechaVencimiento()->format('Y-m-d') === $fechaVencimiento->format('Y-m-d')) {
                        $yaExiste = true;
                        break;
                    }
                }

                if ($yaExiste) {
                    $omitidas++;
                    $detalle[] = sprintf('OMITIDA %s — %s', $compromiso->getNombre(), $fechaVencimiento->format('d/m/Y'));
                    continue;
                }

                $numero = $this->calcularNumero($compromiso, $fechaVencimiento);
                $cuota  = $this->crearCuota($compromiso, $numero, $fechaVencimiento);
                $this->em->persist($cuota);
                $creadas++;
                $detalle[] = sprintf('CREADA  %s — %s — L %s', $compromiso->getNombre(), $fechaVencimiento->format('d/m/Y'), number_format($cuota->getMontoTotal(), 2));
            }
        }

        $this->em->flush();

        return ['creadas' => $creadas, 'omitidas' => $omitidas, 'detalle' => $detalle];
    }

    /** @return list<\DateTimeImmutable> fechas de vencimiento a generar */
    private function calcularSlots(FinCompromiso $c, int $anio, int $mes): array
    {
        $dia = min($c->getDiaPago(), (int) (new \DateTimeImmutable("{$anio}-{$mes}-01"))->format('t'));

        switch ($c->getFrecuencia()) {
            case FinCompromiso::FRECUENCIA_MENSUAL:
                return [new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $anio, $mes, $dia))];

            case FinCompromiso::FRECUENCIA_QUINCENAL:
                $dia2 = min($dia + 15, (int) (new \DateTimeImmutable("{$anio}-{$mes}-01"))->format('t'));
                return [
                    new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $anio, $mes, $dia)),
                    new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $anio, $mes, $dia2)),
                ];

            case FinCompromiso::FRECUENCIA_ANUAL:
                // Solo genera en el mes de inicio
                if ((int) $c->getFechaInicio()->format('n') !== $mes) {
                    return [];
                }
                return [new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $anio, $mes, $dia))];

            case FinCompromiso::FRECUENCIA_UNICA:
                // Solo en el mes exacto de inicio
                $fi = $c->getFechaInicio();
                if ((int) $fi->format('Y') !== $anio || (int) $fi->format('n') !== $mes) {
                    return [];
                }
                return [new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $anio, $mes, $dia))];

            default:
                return [];
        }
    }

    private function calcularNumero(FinCompromiso $c, \DateTimeImmutable $fechaVencimiento): int
    {
        $inicio = $c->getFechaInicio();
        $meses  = (((int) $fechaVencimiento->format('Y')) - ((int) $inicio->format('Y'))) * 12
                + ((int) $fechaVencimiento->format('n')) - ((int) $inicio->format('n'))
                + 1;

        if ($c->getFrecuencia() === FinCompromiso::FRECUENCIA_QUINCENAL) {
            $meses = $meses * 2 - 1;
            // Si el día es mayor al diaPago, es la segunda quincena
            if ((int) $fechaVencimiento->format('j') > $c->getDiaPago()) {
                $meses++;
            }
        }

        return max(1, $meses);
    }

    private function crearCuota(FinCompromiso $c, int $numero, \DateTimeImmutable $fechaVencimiento): FinCuota
    {
        $cuota = new FinCuota();
        $cuota->setCompromiso($c);
        $cuota->setNumero($numero);
        $cuota->setFechaVencimiento($fechaVencimiento);
        $cuota->setMontoTotal($c->getMontoCuota());
        // Para préstamos en el futuro: capital/interés vendrán de FinPrestamo
        $cuota->setCapital(0.0);
        $cuota->setInteres(0.0);
        $cuota->setOtros(0.0);
        $cuota->setMontoPagado(0.0);
        return $cuota;
    }
}
