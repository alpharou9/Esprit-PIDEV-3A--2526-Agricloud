<?php

namespace App\Repository;

use App\Entity\Farm;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Farm>
 */
class FarmRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Farm::class);
    }

    public function listQueryBuilder(?string $query = null, ?User $owner = null, string $sort = 'newest'): QueryBuilder
    {
        $qb = $this->createQueryBuilder('f')
            ->leftJoin('f.user', 'u')->addSelect('u')
            ->leftJoin('f.fields', 'fl');

        if ($owner) {
            $qb->andWhere('f.user = :owner')->setParameter('owner', $owner);
        }

        if ($query) {
            $qb->andWhere('f.name LIKE :q OR f.location LIKE :q')
               ->setParameter('q', '%' . $query . '%');
        }

        match ($sort) {
            'fields_desc' => $qb->groupBy('f.id')->orderBy('COUNT(fl.id)', 'DESC'),
            'fields_asc'  => $qb->groupBy('f.id')->orderBy('COUNT(fl.id)', 'ASC'),
            'area_desc'   => $qb->orderBy('f.area', 'DESC'),
            'area_asc'    => $qb->orderBy('f.area', 'ASC'),
            default       => $qb->orderBy('f.createdAt', 'DESC'),
        };

        return $qb;
    }

    public function countByStatus(): array
    {
        return $this->createQueryBuilder('f')
            ->select('f.status, COUNT(f.id) AS cnt')
            ->groupBy('f.status')
            ->getQuery()
            ->getResult();
    }
}
