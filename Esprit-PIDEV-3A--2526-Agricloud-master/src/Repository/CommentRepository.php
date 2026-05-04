<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Comment> */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function adminQueryBuilder(?string $status = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')->addSelect('u')
            ->leftJoin('c.post', 'p')->addSelect('p')
            ->where('c.parent IS NULL')
            ->orderBy('c.createdAt', 'DESC');

        if ($status) {
            $qb->andWhere('c.status = :status')->setParameter('status', $status);
        }
        return $qb;
    }
}
