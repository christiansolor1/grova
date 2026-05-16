<?php

declare(strict_types=1);

namespace App\Module\Core\Contact\Repository;

use App\Module\Core\Contact\Entity\Contact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Contact> */
class ContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    /** @return Contact[] */
    public function findByTipo(string $tipo): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.tipo = :tipo')
            ->andWhere('c.activo = true')
            ->setParameter('tipo', $tipo)
            ->orderBy('c.apellido', 'ASC')
            ->addOrderBy('c.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Contact[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.apellido', 'ASC')
            ->addOrderBy('c.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Contact[] */
    public function search(string $q): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.nombre LIKE :q OR c.apellido LIKE :q OR c.empresa LIKE :q OR c.email LIKE :q')
            ->andWhere('c.activo = true')
            ->setParameter('q', '%' . $q . '%')
            ->orderBy('c.apellido', 'ASC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }
}
