<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /** Badge count — solo no leídas y no descartadas */
    public function countUnread(User $user): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.user = :user')
            ->andWhere('n.readAt IS NULL')
            ->andWhere('n.dismissedAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Inbox del dropdown — no descartadas */
    /** @return Notification[] */
    public function findInbox(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->andWhere('n.dismissedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** Historial completo — todas, incluyendo descartadas */
    /** @return Notification[] */
    public function findHistory(User $user, int $page = 1, int $perPage = 20, ?string $module = null, ?string $type = null): array
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        if ($module !== null && $module !== '') {
            $qb->andWhere('n.module = :module')->setParameter('module', $module);
        }
        if ($type !== null && $type !== '') {
            $qb->andWhere('n.type = :type')->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }

    public function countHistory(User $user, ?string $module = null, ?string $type = null): int
    {
        $qb = $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.user = :user')
            ->setParameter('user', $user);

        if ($module !== null && $module !== '') {
            $qb->andWhere('n.module = :module')->setParameter('module', $module);
        }
        if ($type !== null && $type !== '') {
            $qb->andWhere('n.type = :type')->setParameter('type', $type);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /** @return string[] */
    public function findDistinctModules(User $user): array
    {
        $rows = $this->createQueryBuilder('n')
            ->select('DISTINCT n.module')
            ->where('n.user = :user')
            ->andWhere('n.module IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleColumnResult();

        return array_filter($rows);
    }

    public function markAllRead(User $user): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.readAt', ':now')
            ->where('n.user = :user')
            ->andWhere('n.readAt IS NULL')
            ->andWhere('n.dismissedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    public function dismissAll(User $user): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.dismissedAt', ':now')
            ->where('n.user = :user')
            ->andWhere('n.dismissedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}
