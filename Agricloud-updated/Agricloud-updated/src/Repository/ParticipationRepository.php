<?php

namespace App\Repository;

use App\Entity\Participation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Participation> */
class ParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participation::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.event', 'e')->addSelect('e')
            ->where('p.user = :user')->setParameter('user', $user)
            ->orderBy('e.eventDate', 'ASC')
            ->getQuery()->getResult();
    }
}
