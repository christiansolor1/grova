<?php
declare(strict_types=1);

namespace App\Module\Personal\FinancialPlanning\Repository;

use App\Module\Personal\FinancialPlanning\Entity\FinPago;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FinPagoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinPago::class);
    }

    /**
     * Suma de pagos sin cuota (prima, abonos extraordinarios) por compromiso.
     * @return array<int, float>  compromiso_id → monto_hnl total
     */
    public function pagosSinCuotaPorCompromiso(int $tenantId): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT p.compromiso_id, COALESCE(SUM(p.monto_hnl), 0) AS total
             FROM fin_pago p
             WHERE p.tenant_id = :tid AND p.cuota_id IS NULL
             GROUP BY p.compromiso_id',
            ['tid' => $tenantId]
        );

        $resultado = [];
        foreach ($rows as $row) {
            $resultado[(int)$row['compromiso_id']] = (float)$row['total'];
        }
        return $resultado;
    }
}
