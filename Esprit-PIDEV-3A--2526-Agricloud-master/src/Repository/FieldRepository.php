<?php

namespace App\Repository;

use App\Entity\Farm;
use App\Entity\Field;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Field>
 */
class FieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Field::class);
    }

    /** @return Field[] */
    public function findByFarm(Farm $farm): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.farm = :farm')
            ->setParameter('farm', $farm)
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
