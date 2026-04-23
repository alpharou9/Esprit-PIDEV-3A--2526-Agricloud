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

    public function findChatbotMatches(string $query, int $limit = 3): array
    {
        $terms = $this->expandChatbotTerms($query);

        $posts = $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', 'published')
            ->orderBy('p.views', 'DESC')
            ->setMaxResults(24)
            ->getQuery()
            ->getResult();

        if (!$terms) {
            return array_slice($posts, 0, $limit);
        }

        $scored = [];
        foreach ($posts as $post) {
            $haystack = mb_strtolower(trim(implode(' ', array_filter([
                $post->getTitle(),
                $post->getExcerpt(),
                $post->getContent(),
                $post->getCategory(),
                implode(' ', $post->getTags() ?? []),
            ]))));

            $score = 0;
            foreach ($terms as $term) {
                if (str_contains($haystack, $term)) {
                    $score += 1;
                }
                if (str_contains(mb_strtolower($post->getTitle()), $term)) {
                    $score += 2;
                }
                if ($post->getCategory() && str_contains(mb_strtolower($post->getCategory()), $term)) {
                    $score += 2;
                }
                foreach ($post->getTags() ?? [] as $tag) {
                    if (str_contains(mb_strtolower($tag), $term)) {
                        $score += 2;
                    }
                }
            }

            if ($score > 0) {
                $scored[] = ['post' => $post, 'score' => $score];
            }
        }

        usort($scored, static function (array $left, array $right): int {
            return $right['score'] <=> $left['score'];
        });

        return array_map(static fn (array $item) => $item['post'], array_slice($scored, 0, $limit));
    }

    /**
     * @return string[]
     */
    private function expandChatbotTerms(string $query): array
    {
        $query = mb_strtolower(trim($query));
        $baseTerms = array_values(array_filter(array_unique(preg_split('/\s+/', $query) ?: []), static function (string $term): bool {
            return mb_strlen($term) >= 3;
        }));

        $aliases = [
            'plant' => ['plants', 'crop', 'crops', 'farming', 'farm', 'garden', 'gardens', 'soil'],
            'plants' => ['plant', 'crop', 'crops', 'farming', 'farm', 'garden', 'gardens', 'soil'],
            'soil' => ['fertility', 'fertilizer', 'compost', 'ground', 'nutrients'],
            'fertilizer' => ['soil', 'compost', 'nutrients', 'manure'],
            'water' => ['irrigation', 'watering', 'moisture'],
            'irrigation' => ['water', 'watering', 'moisture'],
            'pest' => ['pests', 'disease', 'insects', 'protection'],
            'pests' => ['pest', 'disease', 'insects', 'protection'],
            'market' => ['price', 'prices', 'selling', 'sales'],
            'prices' => ['market', 'price', 'selling', 'sales'],
            'weather' => ['climate', 'rain', 'season'],
            'technology' => ['tech', 'innovation', 'tools'],
            'farmer' => ['farming', 'farm', 'crop', 'agriculture'],
            'farming' => ['farmer', 'farm', 'crop', 'agriculture', 'plants'],
            'garden' => ['gardening', 'plants', 'soil', 'crop'],
            'gardens' => ['garden', 'plants', 'soil', 'crop'],
        ];

        $expanded = $baseTerms;
        foreach ($baseTerms as $term) {
            $singular = rtrim($term, 's');
            if ($singular !== '' && !in_array($singular, $expanded, true)) {
                $expanded[] = $singular;
            }
            if (isset($aliases[$term])) {
                foreach ($aliases[$term] as $alias) {
                    if (!in_array($alias, $expanded, true)) {
                        $expanded[] = $alias;
                    }
                }
            }
            if ($singular !== $term && isset($aliases[$singular])) {
                foreach ($aliases[$singular] as $alias) {
                    if (!in_array($alias, $expanded, true)) {
                        $expanded[] = $alias;
                    }
                }
            }
        }

        return $expanded;
    }
}
