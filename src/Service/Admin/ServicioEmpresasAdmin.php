<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\DTO\Admin\FilaEmpresaAdmin;
use App\Entity\Tenant;
use App\Repository\TenantRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ServicioEmpresasAdmin
{
    public function __construct(
        private readonly TenantRepository $repositorioInquilinos,
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
                tipo: $datos['tipo'],
                totalUsuarios: $datos['totalUsuarios'],
                nombrePlan: $datos['nombrePlan'],
                estadoSuscripcion: $datos['estadoSuscripcion'],
                fechaVencimiento: $datos['fechaVencimiento'],
            );
        }

        return $filas;
    }

    public function setTipoEmpresa(int $idInquilino, ?string $tipo): void
    {
        $inquilino = $this->obtenerInquilino($idInquilino);

        $tipoNormalizado = in_array($tipo, ['staff', 'trial', 'cortesia', 'pago'], true) ? $tipo : null;

        $inquilino->setTipo($tipoNormalizado);
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

        $tipoNormalizado = in_array($tipoCliente, ['staff', 'trial', 'cortesia', 'pago'], true) ? $tipoCliente : null;
        $inquilino->setTipo($tipoNormalizado);

        $this->em->flush();
    }
}
