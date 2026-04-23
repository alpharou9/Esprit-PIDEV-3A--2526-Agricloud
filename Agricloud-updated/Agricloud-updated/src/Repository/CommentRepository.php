<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Post;
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

    /**
     * Admin moderation list: top-level comments only (no replies).
     * Supports filter by status and optional keyword search across content or author name.
     */
    public function adminQueryBuilder(
        ?string $status = null,
        ?string $q = null,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')->addSelect('u')
            ->leftJoin('c.post', 'p')->addSelect('p')
            ->where('c.parent IS NULL')
            ->orderBy('c.createdAt', 'DESC');

        if ($status) {
            $qb->andWhere('c.status = :status')
               ->setParameter('status', $status);
        }

        if ($q) {
            $qb->andWhere('c.content LIKE :q OR u.name LIKE :q OR p.title LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }

        return $qb;
    }

    /**
     * Returns pending comment count across all posts (used in admin dashboard badges).
     */
    public function countPending(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.status = :status')
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns approved top-level comments for a given post, eagerly loading users.
     * Used on the public show page so Doctrine does not issue N+1 queries.
     */
    public function findApprovedForPost(Post $post): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')->addSelect('u')
            ->leftJoin('c.replies', 'r')->addSelect('r')
            ->leftJoin('r.user', 'ru')->addSelect('ru')
            ->where('c.post = :post')
            ->andWhere('c.status = :status')
            ->andWhere('c.parent IS NULL')
            ->setParameter('post', $post)
            ->setParameter('status', 'approved')
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
