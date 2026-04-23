<?php

namespace App\Service;

use App\Entity\Post;
use App\Repository\PostRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BlogChatbotService
{
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly HttpClientInterface $httpClient,
    )
    {
    }

    public function answer(string $message): array
    {
        $message = trim($message);
        if ($message === '') {
            return [
                'reply' => 'Ask me about farming topics from the blog, like irrigation, soil health, crop care, pests, market prices, or technology.',
                'items' => [],
            ];
        }

        if ($greeting = $this->greetingReply($message)) {
            return [
                'reply' => $greeting,
                'items' => [],
            ];
        }

        $matches = $this->postRepository->findChatbotMatches($message, 3);
        if (!$matches) {
            $knowledgeReply = $this->knowledgeReply($message);
            if ($knowledgeReply !== null) {
                return [
                    'reply' => $knowledgeReply,
                    'items' => [],
                ];
            }

            return [
                'reply' => 'I could not find a strong answer in the blog yet. Try asking with clearer farming keywords like soil, fertilizer, irrigation, pests, crop name, market, or weather.',
                'items' => [],
            ];
        }

        $fallbackReply = $this->buildConversationalReply($message, $matches);
        $reply = $this->aiReply($message, $matches) ?? $fallbackReply;

        return [
            'reply' => $reply,
            'items' => array_map(fn (Post $post) => $this->formatPost($post), $matches),
        ];
    }

    private function greetingReply(string $message): ?string
    {
        $normalized = mb_strtolower(trim($message));
        $greetings = ['hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening'];

        foreach ($greetings as $greeting) {
            if ($normalized === $greeting || str_starts_with($normalized, $greeting . ' ')) {
                return 'Hello. Ask me anything about the farming topics covered in your blog, and I’ll answer from the articles here.';
            }
        }

        return null;
    }

    private function knowledgeReply(string $message): ?string
    {
        $normalized = mb_strtolower($message);

        $knowledge = [
            'soil' => 'Healthy soil usually improves plant growth when it has good organic matter, balanced nutrients, and enough moisture without becoming waterlogged. In practice, that often means adding compost, avoiding exhausted soil, and checking drainage before planting heavily.',
            'plant' => 'To improve plant health, focus on three basics first: better soil, steady watering, and enough sunlight. It also helps to remove damaged leaves, avoid overcrowding, and use the right fertilizer instead of overfeeding.',
            'water' => 'Good watering is usually about consistency, not just quantity. Most crops do better when the soil stays evenly moist, so watering deeply and checking drainage is often better than frequent shallow watering.',
            'irrigation' => 'A strong irrigation routine usually means giving crops water at regular times, avoiding waste, and matching the amount of water to the crop and weather. Even simple improvements in timing and consistency can help a lot.',
            'pest' => 'Pest control works best when you catch problems early, remove affected parts, keep the area clean, and avoid stressing the plants. Preventive care and regular inspection are usually more effective than waiting until damage spreads.',
            'fertilizer' => 'Fertilizer helps most when it matches what the soil is missing. Too much can damage roots or leaves, so it is usually safer to improve the soil gradually and apply nutrients with a clear purpose.',
            'market' => 'For farm marketing, the main things to watch are demand, timing, pricing, and product quality. Farmers usually do better when they know what buyers want before harvest and keep a close eye on how prices change.',
            'weather' => 'Weather affects planting, watering, pest pressure, and harvest timing. A useful habit is planning around rainfall, heat, and seasonal changes instead of treating every week the same way.',
        ];

        foreach ($knowledge as $keyword => $answer) {
            if (str_contains($normalized, $keyword) || str_contains($normalized, $keyword . 's')) {
                return $answer . ' If you want, ask me a narrower follow-up like soil fertility, watering frequency, or pest prevention.';
            }
        }

        if (str_contains($normalized, 'plants')) {
            return $knowledge['plant'] . ' If you want, ask me a narrower follow-up like soil fertility, watering frequency, or pest prevention.';
        }

        return null;
    }

    /**
     * @param Post[] $matches
     */
    private function aiReply(string $message, array $matches): ?string
    {
        $token = $this->env('HF_TOKEN');
        if ($token === '') {
            return null;
        }

        $model = $this->env('HF_CHAT_MODEL') ?: 'openai/gpt-oss-120b:fastest';
        $context = $this->buildAiContext($matches);

        try {
            $response = $this->httpClient->request('POST', 'https://router.huggingface.co/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are AgriCloud\'s blog assistant. Answer naturally and helpfully using only the blog context provided. If the context is thin, say so clearly and offer a cautious, practical answer. Keep replies concise, conversational, and focused on farming/blog topics.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "User question:\n" . $message . "\n\nBlog context:\n" . $context . "\n\nWrite a direct helpful answer. Mention the strongest matching post title naturally if useful, but do not sound like a search engine.",
                        ],
                    ],
                    'stream' => false,
                    'max_tokens' => 280,
                    'temperature' => 0.5,
                ],
            ]);

            $data = $response->toArray(false);
            $content = trim((string) ($data['choices'][0]['message']['content'] ?? ''));

            return $content !== '' ? $content : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param Post[] $matches
     */
    private function buildAiContext(array $matches): string
    {
        $chunks = [];

        foreach ($matches as $index => $post) {
            $chunks[] = sprintf(
                "Post %d\nTitle: %s\nCategory: %s\nExcerpt: %s\nContent: %s",
                $index + 1,
                $post->getTitle(),
                $post->getCategory() ?: 'General',
                trim((string) ($post->getExcerpt() ?: '')),
                mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($post->getContent())) ?? ''), 0, 900)
            );
        }

        return implode("\n\n", $chunks);
    }

    /**
     * @param Post[] $matches
     */
    private function buildConversationalReply(string $message, array $matches): string
    {
        $terms = $this->extractTerms($message);
        $lead = $matches[0];
        $bestSnippets = $this->collectSnippets($matches, $terms);

        $opening = $this->openingFor($message, $lead);
        $body = $bestSnippets ? implode(' ', array_slice($bestSnippets, 0, 3)) : $this->fallbackSummary($lead);
        $closing = count($matches) > 1
            ? sprintf('I also found related guidance in %d other blog post%s below if you want to read more.', count($matches) - 1, count($matches) - 1 === 1 ? '' : 's')
            : 'You can open the suggested post below if you want the full article.';

        return trim($opening . ' ' . $body . ' ' . $closing);
    }

    private function openingFor(string $message, Post $lead): string
    {
        $normalized = mb_strtolower($message);

        if (preg_match('/\bhow\b/', $normalized)) {
            return sprintf('Here’s the clearest answer I could pull from the blog, mainly from "%s".', $lead->getTitle());
        }

        if (preg_match('/\bwhat\b/', $normalized)) {
            return sprintf('From the blog, this is the simplest explanation I found, especially in "%s".', $lead->getTitle());
        }

        if (preg_match('/\bwhy\b/', $normalized)) {
            return sprintf('The blog suggests this explanation, with "%s" being the strongest match.', $lead->getTitle());
        }

        return sprintf('Based on the blog content, here’s the best answer I can give right now, led by "%s".', $lead->getTitle());
    }

    /**
     * @param Post[] $matches
     * @param string[] $terms
     * @return string[]
     */
    private function collectSnippets(array $matches, array $terms): array
    {
        $snippets = [];

        foreach ($matches as $post) {
            $content = trim(preg_replace('/\s+/', ' ', strip_tags($post->getContent())) ?? '');
            $sentences = preg_split('/(?<=[.!?])\s+/', $content) ?: [];

            foreach ($sentences as $sentence) {
                $sentence = trim($sentence);
                if (mb_strlen($sentence) < 45) {
                    continue;
                }

                $score = 0;
                $lower = mb_strtolower($sentence);
                foreach ($terms as $term) {
                    if (str_contains($lower, $term)) {
                        $score += 2;
                    }
                }

                if ($score === 0 && count($terms) > 0) {
                    continue;
                }

                if (preg_match('/\b(should|can|best|important|avoid|helps|keep|make sure|improve|use)\b/i', $sentence)) {
                    $score += 2;
                }

                $snippets[] = [
                    'text' => $this->trimSentence($sentence),
                    'score' => $score,
                ];
            }
        }

        usort($snippets, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        $unique = [];
        foreach ($snippets as $snippet) {
            if (in_array($snippet['text'], $unique, true)) {
                continue;
            }
            $unique[] = $snippet['text'];
            if (count($unique) >= 3) {
                break;
            }
        }

        return $unique;
    }

    private function fallbackSummary(Post $post): string
    {
        $text = trim($post->getExcerpt() ?: strip_tags($post->getContent()));
        $text = preg_replace('/\s+/', ' ', $text) ?? '';
        $text = mb_substr($text, 0, 220);

        if ($text !== '' && !preg_match('/[.!?]$/', $text)) {
            $text .= '...';
        }

        return $text !== '' ? $text : 'The article below is the closest match, but it needs to be opened for the full details.';
    }

    /**
     * @return string[]
     */
    private function extractTerms(string $message): array
    {
        $stopWords = [
            'the', 'and', 'for', 'with', 'this', 'that', 'from', 'into', 'your', 'about',
            'have', 'what', 'when', 'where', 'which', 'would', 'could', 'should', 'there',
            'their', 'them', 'then', 'than', 'just', 'like', 'want', 'need', 'tell', 'give',
            'blog', 'post', 'posts', 'article', 'articles', 'please', 'help',
        ];

        return array_values(array_filter(
            array_unique(preg_split('/\s+/', mb_strtolower(trim($message))) ?: []),
            static fn (string $term): bool => mb_strlen($term) >= 3 && !in_array($term, $stopWords, true)
        ));
    }

    private function trimSentence(string $sentence): string
    {
        $sentence = trim(preg_replace('/\s+/', ' ', $sentence) ?? '');
        if (mb_strlen($sentence) <= 180) {
            return $sentence;
        }

        return rtrim(mb_substr($sentence, 0, 177)) . '...';
    }

    private function env(string $name): string
    {
        return trim((string) ($_ENV[$name] ?? $_SERVER[$name] ?? getenv($name) ?: ''));
    }

    private function formatPost(Post $post): array
    {
        $excerpt = trim($post->getExcerpt() ?: strip_tags($post->getContent()));
        $excerpt = preg_replace('/\s+/', ' ', $excerpt) ?? '';

        return [
            'title' => $post->getTitle(),
            'slug' => $post->getSlug(),
            'category' => $post->getCategory(),
            'views' => $post->getViews(),
            'excerpt' => mb_substr($excerpt, 0, 120) . (mb_strlen($excerpt) > 120 ? '...' : ''),
        ];
    }
}
