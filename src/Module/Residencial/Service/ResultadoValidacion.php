<?php

declare(strict_types=1);

namespace App\Module\Residencial\Service;

use App\Module\Residencial\Entity\Pase;

/**
 * DTO que devuelve el validador de pases.
 */
final class ResultadoValidacion
{
    public function __construct(
        public readonly bool $ok,
        public readonly string $razon,
        public readonly ?Pase $pase = null,
    ) {
    }

    public static function aprobado(Pase $pase): self
    {
        return new self(true, 'Acceso aprobado', $pase);
    }

    public static function rechazado(string $razon): self
    {
        return new self(false, $razon);
    }

    public function toArray(): array
    {
        $datos = [
            'ok'    => $this->ok,
            'razon' => $this->razon,
        ];

        if ($this->pase !== null) {
            $casa      = $this->pase->getCasaDestino();
            $visitante = $this->pase->getVisitante();
            $residente = $this->pase->getResidente();

            $datos['pase_id']          = $this->pase->getId();
            $datos['casa_id']          = $casa->getId();
            $datos['casa_label']       = $casa->getLabel();
            $datos['visitante_id']     = $visitante?->getId();
            $datos['visitante_nombre'] = $visitante?->getNombreCompleto();
            $datos['residente_nombre'] = $residente->getNombreCompleto();
            $datos['tipo_pase']        = $this->pase->getTipoLabel();
        }

        return $datos;
    }
}
