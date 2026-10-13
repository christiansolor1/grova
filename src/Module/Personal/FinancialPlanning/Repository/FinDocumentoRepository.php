<?php

declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Repository;

use App\Module\Personal\FinancialPlanning\Entity\FinDocumento;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FinDocumento>
 */
class FinDocumentoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinDocumento::class);
    }

    /** @return FinDocumento[] */
    public function findByEntidad(int $tenantId, string $tipo, int $entidadId): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.tenantId = :tid')
            ->andWhere('d.entidadTipo = :tipo')
            ->andWhere('d.entidadId = :eid')
            ->setParameter('tid', $tenantId)
            ->setParameter('tipo', $tipo)
            ->setParameter('eid', $entidadId)
            ->orderBy('d.subidoAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Devuelve todos los documentos de un tenant agrupados por entidad_tipo + entidad_id.
     * Estructura: ['compromiso' => [id => [FinDocumento, ...], ...], 'inversion' => [...]]
     *
     * @return array<string, array<int, FinDocumento[]>>
     */
    public function findAllByTenantGrouped(int $tenantId): array
    {
        $docs = $this->createQueryBuilder('d')
            ->where('d.tenantId = :tid')
            ->setParameter('tid', $tenantId)
            ->orderBy('d.subidoAt', 'ASC')
            ->getQuery()
            ->getResult();

        $agrupado = [];
        foreach ($docs as $doc) {
            $agrupado[$doc->getEntidadTipo()][$doc->getEntidadId()][] = $doc;
        }

        return $agrupado;
    }
}
