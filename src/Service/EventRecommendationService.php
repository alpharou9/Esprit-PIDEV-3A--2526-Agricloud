<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Participation;

class EventRecommendationService
{
    /**
     * @param Event[] $events
     * @param Participation[] $participations
     * @return array<int, array<string, mixed>>
     */
    public function recommend(array $events, ?string $userLocation, ?string $need, array $participations = []): array
    {
        $userLocation = trim((string) $userLocation);
        $need = trim((string) $need);
        $history = $this->buildHistoryProfile($participations);
        $recommendations = [];

        foreach ($events as $event) {
            if (!$event->isRegistrationOpen()) {
                continue;
            }

            $score = 0.0;
            $reasons = [];
            $locationConfidence = null;

            if ($need !== '') {
                $intentScore = $this->scoreIntentMatch($event, $need);
                $score += $intentScore;
                if ($intentScore > 0) {
                    $reasons[] = 'Matches what you are looking for';
                }
            }

            if ($userLocation !== '') {
                $locationScore = $this->scoreLocationMatch($event, $userLocation);
                $locationConfidence = $locationScore['confidence'];
                $score += $locationScore['score'];
                if ($locationScore['score'] > 0) {
                    $reasons[] = 'Close to your chosen location';
                } else {
                    $score -= 8;
                }
            }

            $historyScore = $this->scoreHistoryMatch($event, $history);
            $score += $historyScore;
            if ($historyScore > 0) {
                $reasons[] = 'Similar to events you already liked';
            }

            $availabilityScore = $this->scoreAvailability($event);
            $score += $availabilityScore;
            if ($availabilityScore > 0) {
                $reasons[] = 'Still has room for registration';
            }

            $freshnessScore = $this->scoreFreshness($event);
            $score += $freshnessScore;
            if ($freshnessScore > 0) {
                $reasons[] = 'Coming up soon';
            }

            if ($need === '' && $userLocation === '' && $history['categories'] === [] && $history['keywords'] === []) {
                $score += min(10, $event->getConfirmedCount() * 1.5);
                $reasons[] = 'Popular open event';
            }

            $recommendations[] = [
                'event' => $event,
                'score' => round($score, 1),
                'locationConfidence' => $locationConfidence,
                'reasons' => array_values(array_unique(array_slice($reasons, 0, 3))),
            ];
        }

        usort($recommendations, function (array $left, array $right): int {
            if ($left['score'] !== $right['score']) {
                return $right['score'] <=> $left['score'];
            }

            if ($left['locationConfidence'] !== null && $right['locationConfidence'] !== null && $left['locationConfidence'] !== $right['locationConfidence']) {
                return $right['locationConfidence'] <=> $left['locationConfidence'];
            }

            /** @var Event $leftEvent */
            $leftEvent = $left['event'];
            /** @var Event $rightEvent */
            $rightEvent = $right['event'];

            return ($leftEvent->getEventDate()?->getTimestamp() ?? PHP_INT_MAX) <=> ($rightEvent->getEventDate()?->getTimestamp() ?? PHP_INT_MAX);
        });

        return $recommendations;
    }

    /**
     * @param Participation[] $participations
     * @return array{categories: string[], keywords: string[]}
     */
    private function buildHistoryProfile(array $participations): array
    {
        $categories = [];
        $keywords = [];

        foreach ($participations as $participation) {
            $event = $participation->getEvent();
            if ($event === null) {
                continue;
            }

            $category = trim((string) $event->getCategory());
            if ($category !== '') {
                $categories[] = mb_strtolower($category);
            }

            $keywords = array_merge($keywords, $this->tokenize($event->getTitle() . ' ' . $event->getDescription()));
        }

        return [
            'categories' => array_values(array_unique($categories)),
            'keywords' => array_values(array_unique($keywords)),
        ];
    }

    private function scoreIntentMatch(Event $event, string $need): float
    {
        $fields = [
            'title' => $this->normalizeText($event->getTitle()),
            'category' => $this->normalizeText((string) $event->getCategory()),
            'description' => $this->normalizeText($event->getDescription()),
            'location' => $this->normalizeText($event->getLocation()),
        ];
        $haystack = trim(implode(' ', array_filter($fields)));
        $haystackTokens = $this->tokenize($haystack);
        $keywords = $this->expandIntentKeywords($need);

        if ($haystack === '' || $keywords === []) {
            return 0.0;
        }

        $score = 0.0;
        foreach ($keywords as $keyword) {
            if (
                $this->matchesIntentSignal($fields['title'], $keyword)
                || $this->matchesIntentSignal($fields['category'], $keyword)
                || $this->matchesIntentSignal($fields['description'], $keyword)
                || $this->matchesIntentSignal($fields['location'], $keyword)
            ) {
                $score += in_array($keyword, ['online', 'virtual', 'webinar', 'remote', 'workshop', 'training', 'conference', 'fair'], true) ? 12 : 8;

                if ($this->matchesIntentSignal($fields['title'], $keyword)) {
                    $score += 4;
                }

                if ($this->matchesIntentSignal($fields['category'], $keyword)) {
                    $score += 6;
                }

                continue;
            }

            $score += $this->scoreFuzzyIntentSignal($keyword, $haystackTokens);
        }

        similar_text($this->normalizeText($need), $haystack, $sentencePercent);
        if ($sentencePercent >= 18) {
            $score += min(8, $sentencePercent / 6);
        }

        return min(50, $score);
    }

    /**
     * @return array{score: float, confidence: ?float}
     */
    private function scoreLocationMatch(Event $event, string $userLocation): array
    {
        $userLocation = $this->normalizeText($userLocation);
        $eventLocation = $this->normalizeText($event->getLocation());

        if ($userLocation === '' || $eventLocation === '') {
            return ['score' => 0.0, 'confidence' => null];
        }

        if (str_contains($eventLocation, $userLocation) || str_contains($userLocation, $eventLocation)) {
            return ['score' => 42.0, 'confidence' => 1.0];
        }

        $userTokens = $this->tokenize($userLocation);
        $eventTokens = $this->tokenize($eventLocation);
        $overlap = count(array_intersect($userTokens, $eventTokens));

        if ($overlap > 0) {
            return [
                'score' => min(34.0, 14.0 + ($overlap * 8.0)),
                'confidence' => min(0.9, 0.45 + ($overlap * 0.2)),
            ];
        }

        similar_text($userLocation, $eventLocation, $percent);
        if ($percent >= 55) {
            return ['score' => 16.0, 'confidence' => round($percent / 100, 2)];
        }

        return ['score' => 0.0, 'confidence' => null];
    }

    /**
     * @param array{categories: string[], keywords: string[]} $history
     */
    private function scoreHistoryMatch(Event $event, array $history): float
    {
        $score = 0.0;
        $category = mb_strtolower(trim((string) $event->getCategory()));
        if ($category !== '' && in_array($category, $history['categories'], true)) {
            $score += 18;
        }

        $tokens = $this->tokenize($event->getTitle() . ' ' . $event->getDescription());
        $overlap = count(array_intersect($tokens, $history['keywords']));
        $score += min(12, $overlap * 3);

        return $score;
    }

    private function scoreAvailability(Event $event): float
    {
        $capacity = $event->getCapacity();
        if ($capacity === null || $capacity <= 0) {
            return 12.0;
        }

        $remaining = max(0, $capacity - $event->getConfirmedCount());
        if ($remaining === 0) {
            return -50.0;
        }

        $ratio = $remaining / $capacity;

        return match (true) {
            $ratio >= 0.5 => 14.0,
            $ratio >= 0.2 => 8.0,
            default => 4.0,
        };
    }

    private function scoreFreshness(Event $event): float
    {
        $date = $event->getEventDate();
        if ($date === null) {
            return 0.0;
        }

        $days = (int) floor((($date->getTimestamp()) - time()) / 86400);

        return match (true) {
            $days < 0 => -20.0,
            $days <= 3 => 18.0,
            $days <= 10 => 14.0,
            $days <= 30 => 8.0,
            default => 3.0,
        };
    }

    /**
     * @return string[]
     */
    private function tokenize(string $value): array
    {
        $value = $this->normalizeText($value);
        if ($value === '') {
            return [];
        }

        $parts = preg_split('/[^\p{L}\p{N}]+/u', $value) ?: [];

        return array_values(array_unique(array_filter($parts, static fn (string $part): bool => mb_strlen($part) >= 3)));
    }

    /**
     * @return string[]
     */
    private function expandIntentKeywords(string $need): array
    {
        $normalized = $this->normalizeText($need);
        if ($normalized === '') {
            return [];
        }

        $keywords = [];
        $groups = [
            'online' => ['online', 'virtual', 'remote', 'webinar', 'digital', 'livestream', 'live stream', 'zoom', 'teams', 'distance'],
            'training' => ['training', 'learn', 'learning', 'course', 'beginner', 'formation', 'tutorial', 'coaching', 'bootcamp'],
            'workshop' => ['workshop', 'hands on', 'practical', 'atelier', 'practice'],
            'conference' => ['conference', 'talk', 'speaker', 'speakers', 'forum', 'summit', 'meetup', 'meet up', 'panel'],
            'fair' => ['fair', 'expo', 'exhibition', 'market day', 'salon', 'showcase'],
            'agriculture' => ['agriculture', 'agricultural', 'farming', 'farm', 'farmer', 'agronomy', 'crop', 'crops', 'cultivation'],
            'irrigation' => ['irrigation', 'watering', 'water', 'drip', 'sprinkler', 'water management'],
            'beginner' => ['beginner', 'basic', 'basics', 'intro', 'introduction', 'starter', 'first time'],
        ];

        foreach ($groups as $canonical => $variants) {
            foreach ($variants as $variant) {
                if ($this->matchesNeedVariant($normalized, $variant)) {
                    $keywords[] = $canonical;
                    $keywords = array_merge($keywords, array_map(fn (string $item): string => $this->normalizeText($item), $variants));
                    break;
                }
            }
        }

        $stopWords = [
            'i', 'want', 'something', 'need', 'looking', 'for', 'would', 'like', 'show', 'me', 'to',
            'the', 'and', 'that', 'near', 'with', 'about', 'please', 'can', 'you', 'find', 'event', 'events',
        ];

        foreach ($this->tokenize($normalized) as $token) {
            if (!in_array($token, $stopWords, true)) {
                $keywords[] = $token;
            }
        }

        return array_values(array_unique(array_filter($keywords)));
    }

    private function matchesNeedVariant(string $normalizedNeed, string $variant): bool
    {
        $variant = $this->normalizeText($variant);
        if ($variant === '') {
            return false;
        }

        if (str_contains($normalizedNeed, $variant)) {
            return true;
        }

        $needTokens = $this->tokenize($normalizedNeed);
        $variantTokens = $this->tokenize($variant);

        foreach ($needTokens as $needToken) {
            foreach ($variantTokens as $variantToken) {
                if ($this->tokensApproximatelyMatch($needToken, $variantToken)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function matchesIntentSignal(string $text, string $signal): bool
    {
        $text = $this->normalizeText($text);
        $signal = $this->normalizeText($signal);

        if ($text === '' || $signal === '') {
            return false;
        }

        if (str_contains($text, $signal)) {
            return true;
        }

        $textTokens = $this->tokenize($text);
        $signalTokens = $this->tokenize($signal);

        if ($signalTokens === []) {
            $signalTokens = [$signal];
        }

        foreach ($signalTokens as $signalToken) {
            foreach ($textTokens as $textToken) {
                if ($this->tokensApproximatelyMatch($signalToken, $textToken)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string[] $haystackTokens
     */
    private function scoreFuzzyIntentSignal(string $signal, array $haystackTokens): float
    {
        $signalTokens = $this->tokenize($signal);
        if ($signalTokens === []) {
            $signalTokens = [$this->normalizeText($signal)];
        }

        $score = 0.0;
        foreach ($signalTokens as $signalToken) {
            foreach ($haystackTokens as $haystackToken) {
                if ($this->tokensApproximatelyMatch($signalToken, $haystackToken)) {
                    $score += 2.5;
                    break;
                }
            }
        }

        return min(8.0, $score);
    }

    private function tokensApproximatelyMatch(string $left, string $right): bool
    {
        $left = $this->normalizeToken($left);
        $right = $this->normalizeToken($right);

        if ($left === '' || $right === '') {
            return false;
        }

        if ($left === $right) {
            return true;
        }

        if (mb_strlen($left) >= 4 && mb_strlen($right) >= 4 && (str_contains($left, $right) || str_contains($right, $left))) {
            return true;
        }

        if (levenshtein($left, $right) <= 2) {
            return true;
        }

        similar_text($left, $right, $percent);

        return $percent >= 78;
    }

    private function normalizeToken(string $token): string
    {
        $token = $this->normalizeText($token);
        if ($token === '') {
            return '';
        }

        foreach (['ations', 'ation', 'ments', 'ment', 'ings', 'ing', 'ers', 'er', 'ies', 'ied', 'ed', 'es', 's'] as $suffix) {
            if (mb_strlen($token) > mb_strlen($suffix) + 2 && str_ends_with($token, $suffix)) {
                return mb_substr($token, 0, mb_strlen($token) - mb_strlen($suffix));
            }
        }

        return $token;
    }

    private function normalizeText(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }
}
