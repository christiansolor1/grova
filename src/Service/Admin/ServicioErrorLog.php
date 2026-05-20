<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Entity\ErrorLog;
use App\Repository\ErrorLogRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ServicioErrorLog
{
    public function __construct(
        private readonly ErrorLogRepository $repo,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @param array $filtros level, status, channel, tenant_id, desde, hasta
     * @return array{items: ErrorLog[], total: int, pagina: int, paginas: int}
     */
    public function listar(array $filtros = [], int $pagina = 1, int $porPagina = 30): array
    {
        return $this->repo->listar($filtros, $pagina, $porPagina);
    }

    public function obtener(int $id): ErrorLog
    {
        $error = $this->repo->find($id);
        if ($error === null) {
            throw new \InvalidArgumentException('Error no encontrado.');
        }

        return $error;
    }

    public function cambiarEstado(int $id, string $nuevoEstado): ErrorLog
    {
        if (!\in_array($nuevoEstado, ErrorLog::getStatuses(), true)) {
            throw new \InvalidArgumentException('Estado inválido: ' . $nuevoEstado);
        }

        $error = $this->obtener($id);
        $error->setStatus($nuevoEstado);
        $error->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $error;
    }

    /** @return string[] */
    public function getNiveles(): array
    {
        return $this->repo->findDistinctLevels();
    }

    /** @return string[] */
    public function getCanales(): array
    {
        return $this->repo->findDistinctChannels();
    }
}
