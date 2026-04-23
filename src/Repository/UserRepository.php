<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    public function listQueryBuilder(?string $query = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.role', 'r')
            ->addSelect('r')
            ->orderBy('u.createdAt', 'DESC');

        if ($query) {
            $qb->where('u.name LIKE :q OR u.email LIKE :q')
               ->setParameter('q', '%' . $query . '%');
        }

        return $qb;
    }

    /**
     * Search users by name or email.
     *
     * @return User[]
     */
    public function search(string $query): array
    {
        return $this->listQueryBuilder($query)->getQuery()->getResult();
    }

    /** Count users grouped by status. Returns [['status'=>'active','cnt'=>N], ...] */
    public function countByStatus(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.status, COUNT(u.id) AS cnt')
            ->groupBy('u.status')
            ->getQuery()
            ->getResult();
    }

    /** Count users grouped by role name. Returns [['roleName'=>'Admin','cnt'=>N], ...] */
    public function countByRole(): array
    {
        return $this->createQueryBuilder('u')
            ->select('COALESCE(r.name, \'No Role\') AS roleName, COUNT(u.id) AS cnt')
            ->leftJoin('u.role', 'r')
            ->groupBy('r.id')
            ->getQuery()
            ->getResult();
    }

    /** Users who have enrolled their face. @return User[] */
    public function findWithFaceEmbeddings(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.faceEmbeddings IS NOT NULL')
            ->getQuery()
            ->getResult();
    }

    /** Last N registered users. @return User[] */
    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.role', 'r')
            ->addSelect('r')
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
