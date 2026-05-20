<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\DTO\Admin\FilaEmpresaAdmin;
use App\Entity\Suscripcion;
use App\Entity\Tenant;
use App\Repository\SuscripcionRepository;
use App\Repository\TenantRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ServicioEmpresasAdmin
{
    public function __construct(
        private readonly TenantRepository $repositorioInquilinos,
        private readonly SuscripcionRepository $repositorioSuscripciones,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return list<FilaEmpresaAdmin>
     */
    public function listarEmpresas(): array
    {
        $filas = [];

        foreach ($this->repositorioInquilinos->obtenerDatosListadoAdmin() as $datos) {
            $filas[] = new FilaEmpresaAdmin(
                id: $datos['id'],
                nombre: $datos['nombre'],
                slug: $datos['slug'],
                estado: $datos['estado'],
                totalUsuarios: $datos['totalUsuarios'],
                nombrePlan: $datos['nombrePlan'],
                estadoSuscripcion: $datos['estadoSuscripcion'],
                fechaVencimiento: $datos['fechaVencimiento'],
                tipoCliente: $datos['tipoCliente'],
            );
        }

        return $filas;
    }

    public function setTipoClienteEmpresa(int $idInquilino, ?string $tipo): void
    {
        $inquilino = $this->obtenerInquilino($idInquilino);

        $tipoNormalizado = in_array($tipo, ['cortesia', 'pago'], true) ? $tipo : null;

        $suscripcion = $this->repositorioSuscripciones->findUltimaForTenant($inquilino);

        if (!$suscripcion instanceof Suscripcion) {
            throw new \InvalidArgumentException('La empresa no tiene suscripción registrada.');
        }

        $suscripcion->setTipoCliente($tipoNormalizado);
        $this->em->flush();
    }

    public function alternarEstadoEmpresa(int $idInquilino): void
    {
        $inquilino = $this->obtenerInquilino($idInquilino);

        $inquilino->setEstado($inquilino->isActivo() ? 'suspendido' : 'activo');
        $this->em->flush();
    }

    public function obtenerInquilino(int $idInquilino): Tenant
    {
        $inquilino = $this->repositorioInquilinos->find($idInquilino);

        if (!$inquilino instanceof Tenant) {
            throw new \InvalidArgumentException(sprintf('Empresa con id %d no encontrada.', $idInquilino));
        }

        return $inquilino;
    }

    /**
     * @param 'activo'|'suspendido' $estado
     */
    public function actualizarOrganizacion(
        int $idInquilino,
        string $nombre,
        string $estado,
        ?string $tipoCliente = null,
    ): void {
        $nombreLimpio = trim($nombre);
        if ($nombreLimpio === '') {
            throw new \InvalidArgumentException('El nombre de la organización no puede estar vacío.');
        }

        if (!in_array($estado, ['activo', 'suspendido'], true)) {
            throw new \InvalidArgumentException('Estado de organización no válido.');
        }

        $inquilino = $this->obtenerInquilino($idInquilino);
        $inquilino->setNombre($nombreLimpio);
        $inquilino->setEstado($estado);

        $suscripcion = $this->repositorioSuscripciones->findUltimaForTenant($inquilino);
        if ($suscripcion instanceof Suscripcion) {
            $tipoNormalizado = in_array($tipoCliente, ['cortesia', 'pago'], true) ? $tipoCliente : null;
            $suscripcion->setTipoCliente($tipoNormalizado);
        }

        $this->em->flush();
    }
}
