<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Post> */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * Public blog listing: only published posts.
     * Supports full-text search across title, excerpt, and content, plus category filter.
     */
    public function publicQueryBuilder(
        ?string $q = null,
        ?string $category = null,
        ?string $sort = 'newest',
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')->addSelect('u')
            ->where('p.status = :status')
            ->setParameter('status', 'published');

        if ($q) {
            $qb->andWhere('p.title LIKE :q OR p.excerpt LIKE :q OR p.content LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }

        if ($category) {
            $qb->andWhere('p.category = :cat')
               ->setParameter('cat', $category);
        }

        match ($sort) {
            'oldest'   => $qb->orderBy('p.publishedAt', 'ASC'),
            'popular'  => $qb->orderBy('p.views', 'DESC'),
            default    => $qb->orderBy('p.publishedAt', 'DESC'),
        };

        return $qb;
    }

    /**
     * Author dashboard: all posts belonging to the given user.
     * Supports search by title and filter by status.
     */
    public function authorQueryBuilder(
        User $author,
        ?string $q = null,
        ?string $status = null,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('p')
            ->where('p.user = :author')
            ->setParameter('author', $author)
            ->orderBy('p.createdAt', 'DESC');

        if ($q) {
            $qb->andWhere('p.title LIKE :q OR p.content LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }

        if ($status) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $status);
        }

        return $qb;
    }

    /**
     * Admin dashboard: all posts from all users.
     * Supports search by title or author name and filter by status.
     */
    public function adminQueryBuilder(
        ?string $q = null,
        ?string $status = null,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')->addSelect('u')
            ->orderBy('p.createdAt', 'DESC');

        if ($q) {
            $qb->andWhere('p.title LIKE :q OR p.excerpt LIKE :q OR u.name LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }

        if ($status) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $status);
        }

        return $qb;
    }

    /**
     * Returns the N most-viewed published posts (used in sidebars).
     */
    public function findMostViewed(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', 'published')
            ->orderBy('p.views', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns all distinct non-null categories that have at least one published post.
     * Used to populate the category filter pills.
     */
    public function findPublishedCategories(): array
    {
        return $this->createQueryBuilder('p')
            ->select('DISTINCT p.category')
            ->where('p.status = :status')
            ->andWhere('p.category IS NOT NULL')
            ->setParameter('status', 'published')
            ->getQuery()
            ->getSingleColumnResult();
    }
}
