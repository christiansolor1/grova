<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Entity\Plan;
use App\Repository\PlanRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ServicioPlanesAdmin
{
    /** @var list<string> */
    private const MODULOS_DISPONIBLES = [
        'wallet', 'work', 'agenda', 'habitos', 'pesca',
        'legal', 'contactos', 'construccion',
        'pos', 'restaurante', 'clinica', 'financiera',
        'facturacion', 'inventario', 'rrhh',
    ];

    public function __construct(
        private readonly PlanRepository $repositorioPlanes,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return list<Plan>
     */
    public function listarPlanes(): array
    {
        return $this->repositorioPlanes->findAllOrdered();
    }

    /**
     * @return list<Plan>
     */
    public function listarPlanesActivos(): array
    {
        return $this->repositorioPlanes->findActivos();
    }

    /**
     * @param list<string> $modulos
     */
    public function crearPlan(string $nombre, array $modulos, string $precioMensual): Plan
    {
        if ($nombre === '') {
            throw new \InvalidArgumentException('El nombre del plan no puede estar vacío.');
        }

        if ($this->repositorioPlanes->findByNombre($nombre) instanceof Plan) {
            throw new \InvalidArgumentException('Ya existe un plan con ese nombre.');
        }

        $plan = new Plan();
        $plan->setNombre($nombre);
        $plan->setModulos($modulos);
        $plan->setPrecioMensual($precioMensual);
        $plan->setEstado('activo');

        $this->em->persist($plan);
        $this->em->flush();

        return $plan;
    }

    /**
     * @param list<string> $modulos
     */
    public function actualizarPlan(int $id, string $nombre, array $modulos, string $precioMensual): Plan
    {
        $plan = $this->repositorioPlanes->find($id);

        if (!$plan instanceof Plan) {
            throw new \InvalidArgumentException(sprintf('Plan con id %d no encontrado.', $id));
        }

        if ($nombre === '') {
            throw new \InvalidArgumentException('El nombre del plan no puede estar vacío.');
        }

        $existente = $this->repositorioPlanes->findByNombre($nombre);
        if ($existente instanceof Plan && $existente->getId() !== $id) {
            throw new \InvalidArgumentException('Ya existe otro plan con ese nombre.');
        }

        $plan->setNombre($nombre);
        $plan->setModulos($modulos);
        $plan->setPrecioMensual($precioMensual);
        $this->em->flush();

        return $plan;
    }

    public function obtenerPlan(int $id): Plan
    {
        $plan = $this->repositorioPlanes->find($id);

        if (!$plan instanceof Plan) {
            throw new \InvalidArgumentException(sprintf('Plan con id %d no encontrado.', $id));
        }

        return $plan;
    }

    public function alternarEstadoPlan(int $id): void
    {
        $plan = $this->repositorioPlanes->find($id);

        if (!$plan instanceof Plan) {
            throw new \InvalidArgumentException(sprintf('Plan con id %d no encontrado.', $id));
        }

        $plan->setEstado($plan->getEstado() === 'activo' ? 'inactivo' : 'activo');
        $this->em->flush();
    }

    /**
     * @return list<string>
     */
    public function obtenerModulosDisponibles(): array
    {
        return self::MODULOS_DISPONIBLES;
    }
}
