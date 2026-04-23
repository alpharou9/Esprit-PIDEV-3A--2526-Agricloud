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
        $haystack = $this->normalizeText(implode(' ', array_filter([
            $event->getTitle(),
            $event->getCategory(),
            $event->getDescription(),
            $event->getLocation(),
        ])));

        $keywords = $this->expandIntentKeywords($need);
        if ($keywords === []) {
            return 0.0;
        }

        $score = 0.0;
        foreach ($keywords as $keyword) {
            if (str_contains($haystack, $keyword)) {
                $score += in_array($keyword, ['online', 'virtual', 'webinar', 'remote'], true) ? 18 : 14;
                continue;
            }

            similar_text($keyword, $haystack, $percent);
            if ($percent >= 35) {
                $score += 4;
            }
        }

        return min(40, $score);
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
            'online' => ['online', 'virtual', 'remote', 'webinar', 'digital', 'livestream', 'live stream'],
            'training' => ['training', 'learn', 'learning', 'course', 'beginner', 'formation'],
            'workshop' => ['workshop', 'hands on', 'practical'],
            'conference' => ['conference', 'talk', 'speaker', 'speakers', 'forum'],
            'fair' => ['fair', 'expo', 'exhibition', 'market day'],
            'agriculture' => ['agriculture', 'agricultural', 'farming', 'farm', 'farmer'],
            'irrigation' => ['irrigation', 'watering', 'water', 'drip'],
        ];

        foreach ($groups as $canonical => $variants) {
            foreach ($variants as $variant) {
                if (str_contains($normalized, $this->normalizeText($variant))) {
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

    private function normalizeText(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }
}
