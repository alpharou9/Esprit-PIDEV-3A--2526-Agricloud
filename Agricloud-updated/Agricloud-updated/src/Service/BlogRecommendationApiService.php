<?php

namespace App\Service;

use App\Entity\Post;
use App\Repository\PostRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BlogRecommendationApiService
{
    public function __construct(private readonly HttpClientInterface $httpClient, private readonly PostRepository $postRepository)
    {
    }

    public function recommend(Post $post, int $limit = 3): array
    {
        $remote = $this->callRemoteApi($post, $limit);
        if ($remote !== null) {
            return $remote;
        }

        return $this->localRecommendations($post, $limit);
    }

    private function callRemoteApi(Post $post, int $limit): ?array
    {
        $url = $this->env('BLOG_RECOMMENDATION_API_URL');
        if ($url === '') {
            return null;
        }

        $headers = ['Accept' => 'application/json'];
        $apiKey = $this->env('BLOG_RECOMMENDATION_API_KEY');
        if ($apiKey !== '') {
            $headers['Authorization'] = 'Bearer ' . $apiKey;
        }

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => $headers,
                'json' => [
                    'post_id' => $post->getId(),
                    'title' => $post->getTitle(),
                    'content' => $post->getContent(),
                    'category' => $post->getCategory(),
                    'tags' => $post->getTags() ?? [],
                    'limit' => $limit,
                ],
            ]);
            $data = $response->toArray(false);
            if (!isset($data['items']) || !is_array($data['items'])) {
                return null;
            }

            $posts = [];
            foreach ($data['items'] as $item) {
                if (!is_array($item) || !isset($item['id'])) {
                    continue;
                }
                $candidate = $this->postRepository->find($item['id']);
                if ($candidate instanceof Post && $candidate->getId() !== $post->getId() && $candidate->getStatus() === 'published') {
                    $posts[] = $candidate;
                }
            }

            return array_slice($posts, 0, $limit);
        } catch (\Throwable) {
            return null;
        }
    }

    private function localRecommendations(Post $post, int $limit): array
    {
        $candidates = $this->postRepository->findBy(['status' => 'published'], ['publishedAt' => 'DESC'], 18);
        $needleTokens = $this->tokenize($post->getTitle() . ' ' . $post->getContent());
        $needleTags = array_map('mb_strtolower', $post->getTags() ?? []);

        $scored = [];
        foreach ($candidates as $candidate) {
            if (!$candidate instanceof Post || $candidate->getId() === $post->getId()) {
                continue;
            }

            $score = 0;
            if ($candidate->getCategory() && $candidate->getCategory() === $post->getCategory()) {
                $score += 6;
            }

            $candidateTags = array_map('mb_strtolower', $candidate->getTags() ?? []);
            $score += count(array_intersect($needleTags, $candidateTags)) * 4;

            $candidateTokens = $this->tokenize($candidate->getTitle() . ' ' . $candidate->getExcerpt() . ' ' . $candidate->getContent());
            $score += min(6, count(array_intersect($needleTokens, $candidateTokens)));
            $score += min(3, (int) floor($candidate->getViews() / 10));
            $scored[] = ['score' => $score, 'post' => $candidate];
        }

        usort($scored, static fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_map(static fn (array $item) => $item['post'], array_slice(array_filter($scored, static fn (array $item) => $item['score'] > 0), 0, $limit));
    }

    private function tokenize(string $text): array
    {
        $text = mb_strtolower(strip_tags($text));
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $text) ?: [];
        $stopWords = ['the', 'and', 'for', 'with', 'this', 'that', 'from', 'your', 'into', 'have', 'about', 'farm', 'farming', 'post', 'blog'];
        $tokens = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || mb_strlen($part) < 3 || in_array($part, $stopWords, true)) {
                continue;
            }
            $tokens[] = $part;
        }

        return array_values(array_unique($tokens));
    }

    private function env(string $name): string
    {
        return trim((string) ($_ENV[$name] ?? $_SERVER[$name] ?? getenv($name) ?: ''));
    }
}