<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Search users by name or email.
     *
     * @return User[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.role', 'r')
            ->addSelect('r')
            ->where('u.name LIKE :q OR u.email LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
