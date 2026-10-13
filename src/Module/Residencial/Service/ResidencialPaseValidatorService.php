<?php

declare(strict_types=1);

namespace App\Module\Residencial\Service;

use App\Module\Residencial\Repository\PaseRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Valida un token QR y decide si el acceso está permitido.
 */
final class ResidencialPaseValidatorService
{
    public function __construct(
        private readonly PaseRepository $paseRepo,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function validar(string $token, int $tenantId): ResultadoValidacion
    {
        $pase = $this->paseRepo->findByToken($token);

        if ($pase === null) {
            return ResultadoValidacion::rechazado('Pase no encontrado.');
        }

        if ($pase->getTenantId() !== $tenantId) {
            return ResultadoValidacion::rechazado('Pase no válido para este residencial.');
        }

        if ($pase->getEstado() === 'cancelado') {
            return ResultadoValidacion::rechazado('Este pase fue cancelado.');
        }

        if ($pase->getEstado() === 'vencido') {
            return ResultadoValidacion::rechazado('Este pase está vencido.');
        }

        $ahora = new \DateTimeImmutable();
        $hoy   = new \DateTimeImmutable('today');

        // Verificar fecha de vencimiento
        if ($pase->getFechaFin() !== null && $pase->getFechaFin() < $hoy) {
            $pase->setEstado('vencido');
            $this->em->flush();
            return ResultadoValidacion::rechazado('Este pase venció el ' . $pase->getFechaFin()->format('d/m/Y') . '.');
        }

        // Verificar que ya comenzó
        if ($pase->getFechaInicio() !== null && $pase->getFechaInicio() > $hoy) {
            return ResultadoValidacion::rechazado('Este pase es válido a partir del ' . $pase->getFechaInicio()->format('d/m/Y') . '.');
        }

        // Verificar horario (si aplica)
        if ($pase->getHoraInicio() !== null && $pase->getHoraFin() !== null) {
            $horaActual = $ahora->format('H:i');
            if ($horaActual < $pase->getHoraInicio() || $horaActual > $pase->getHoraFin()) {
                return ResultadoValidacion::rechazado(
                    sprintf('Solo válido entre %s y %s.', $pase->getHoraInicio(), $pase->getHoraFin())
                );
            }
        }

        // Verificar día de la semana (pases recurrentes)
        if ($pase->getTipo() === 'recurrente' && !empty($pase->getDiasSemana())) {
            $diaSemanaHoy = (int) $ahora->format('w'); // 0=domingo, 6=sábado
            if (!in_array($diaSemanaHoy, $pase->getDiasSemana(), true)) {
                return ResultadoValidacion::rechazado('Hoy no es un día permitido para este pase.');
            }
        }

        // Pase de única vez ya usado
        if ($pase->getTipo() === 'unica_vez' && $pase->getEstado() === 'usado') {
            return ResultadoValidacion::rechazado('Este pase de única vez ya fue utilizado.');
        }

        // Marcar como usado si es de única vez
        if ($pase->getTipo() === 'unica_vez') {
            $pase->setEstado('usado');
            $this->em->flush();
        }

        return ResultadoValidacion::aprobado($pase);
    }
}
