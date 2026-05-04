<?php

namespace App\Service;

class ChatbotService
{
    public function __construct(
        private readonly RecipeAssistantService $recipeAssistantService,
    ) {
    }

    /**
     * Simple keyword matching keeps the assistant predictable and easy to maintain.
     */
    public function reply(string $message): array
    {
        $normalized = $this->normalize($message);

        if ($normalized === '') {
            return [
                'reply' => 'Ask me about products, cart, orders, farms, events, blog posts, your account, or ask for a recipe and I will guide you.',
            ];
        }

        $eventContext = $this->extractEventContext($message);
        if ($eventContext !== null) {
            return [
                'reply' => $this->replyToEventQuestion($eventContext),
            ];
        }

        if ($this->recipeAssistantService->isRecipeRequest($normalized)) {
            return [
                'reply' => 'Here are several simple recipes I can prepare with marketplace products. Choose a dish and I will check the ingredients for you.',
                'recipes' => $this->recipeAssistantService->getRecipeSuggestions(),
            ];
        }

        $recipeSlug = $this->recipeAssistantService->findRecipeSlugFromMessage($normalized);
        if ($recipeSlug !== null) {
            $preview = $this->recipeAssistantService->buildRecipePreview($recipeSlug);

            if ($preview !== null) {
                $reply = $preview['missing'] === []
                    ? sprintf('Great choice. I found all the ingredients for %s.', $preview['name'])
                    : sprintf('I found part of the ingredients for %s. Some items are still missing.', $preview['name']);

                return [
                    'reply' => $reply,
                    'recipePreview' => $preview,
                ];
            }
        }

        $responses = [
            [
                'keywords' => ['product', 'products', 'sell', 'marketplace', 'price'],
                'answer' => 'You can browse products in Marketplace, open a product page for details, and farmers can add new items from My Products. Prices are stored in TND, and the interface can also show converted values.',
            ],
            [
                'keywords' => ['cart', 'buy', 'checkout'],
                'answer' => 'Add items from the product page, review them in Cart, and continue to checkout to place your order. If a product is out of stock, the buy action will be disabled.',
            ],
            [
                'keywords' => ['order', 'orders', 'status', 'delivery', 'confirmed', 'shipped'],
                'answer' => 'You can track your orders from the Orders page. The platform uses the statuses pending, confirmed, preparing, shipped, delivered, and cancelled, and the order page shows them as a progress timeline.',
            ],
            [
                'keywords' => ['farm', 'farms', 'field', 'fields'],
                'answer' => 'The Farms section helps farmers manage farms and fields. When you create a product, you can optionally link it to one of your farms.',
            ],
            [
                'keywords' => ['event', 'events', 'registration'],
                'answer' => 'The Events area lets you explore upcoming events, register when available, and manage your own event activity from the dashboard links.',
            ],
            [
                'keywords' => ['agriculture', 'farming', 'farmer', 'crop', 'irrigation', 'soil', 'harvest'],
                'answer' => 'AgriBot can help with basic agriculture guidance, but for exact technical or field decisions you should still confirm with a qualified agronomy source or your local expert.',
            ],
            [
                'keywords' => ['blog', 'post', 'comment', 'comments'],
                'answer' => 'The Blog section lets you read posts, leave comments when allowed, and manage your own posts if you have the right access in the application.',
            ],
            [
                'keywords' => ['profile', 'account', 'name', 'email', 'password'],
                'answer' => 'You can open your profile from the top-right area of the app to review or update your account information.',
            ],
            [
                'keywords' => ['login', 'register', 'sign up', 'sign in'],
                'answer' => 'New users can register from the authentication screens, and existing users can log in with their account credentials. This project also includes guest and face-auth flows in the auth module.',
            ],
            [
                'keywords' => ['ai', 'description', 'generate'],
                'answer' => 'The product form includes an AI assistant that can draft a product name, category, unit, and description based on the information you already typed.',
            ],
            [
                'keywords' => ['contact', 'help', 'support'],
                'answer' => 'I can help with common platform questions here. For project-specific data issues, an admin or your team maintainer should review the affected module directly.',
            ],
        ];

        foreach ($responses as $response) {
            if ($this->containsAny($normalized, $response['keywords'])) {
                return ['reply' => $response['answer']];
            }
        }

        return [
            'reply' => 'I am not sure about that yet. Try asking about products, cart, checkout, orders, farms, events, blog, profile, or ask me to suggest a recipe.',
        ];
    }

    /**
     * @return array{title: string, location: string, date: string, category: string, question: string}|null
     */
    private function extractEventContext(string $message): ?array
    {
        if (preg_match('/Event:\s*(.*?)\.\s*Location:\s*(.*?)\.\s*Date:\s*(.*?)\.\s*Category:\s*(.*?)\.\s*Question:\s*(.*)$/si', $message, $matches) !== 1) {
            return null;
        }

        return [
            'title' => trim($matches[1]),
            'location' => trim($matches[2]),
            'date' => trim($matches[3]),
            'category' => trim($matches[4]),
            'question' => trim($matches[5]),
        ];
    }

    /**
     * @param array{title: string, location: string, date: string, category: string, question: string} $context
     */
    private function replyToEventQuestion(array $context): string
    {
        $question = $this->normalize($context['question']);
        $title = $context['title'] !== '' ? $context['title'] : 'this event';
        $location = $context['location'] !== '' ? $context['location'] : 'the listed venue';
        $date = $context['date'] !== '' ? $context['date'] : 'the scheduled date';
        $category = $context['category'] !== '' ? $context['category'] : 'event';

        if ($this->containsAny($question, ['where', 'location', 'place', 'venue', 'map', 'address'])) {
            return sprintf('%s takes place at %s. You can use the Maps button or the QR code on the ticket to open the map search for that location.', $title, $location);
        }

        if ($this->containsAny($question, ['when', 'date', 'time', 'start'])) {
            return sprintf('%s is scheduled for %s.', $title, $date);
        }

        if ($this->containsAny($question, ['register', 'registration', 'join', 'ticket', 'email'])) {
            return sprintf('If registration is open, you can use the Register button on the event page. After registration, the system sends your ticket by email and the ticket QR code opens the map search for %s.', $location);
        }

        if ($this->containsAny($question, ['bring', 'prepare', 'need', 'carry'])) {
            return sprintf('For %s, it is a good idea to bring the essentials for a %s: your phone, easy-to-carry notes, and anything specific mentioned by the organizer. You should also keep your email ticket ready in case it is checked on arrival.', $title, $category);
        }

        if ($this->containsAny($question, ['what is', 'about', 'category', 'type'])) {
            return sprintf('%s is listed as a %s event and is scheduled for %s in %s.', $title, $category, $date, $location);
        }

        return sprintf('Here is the main event info I have: %s is a %s event scheduled for %s in %s. You can register from the page when registration is open, and your email ticket includes a QR code that opens the venue map.', $title, $category, $date, $location);
    }

    private function containsAny(string $message, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $message): string
    {
        $normalized = mb_strtolower(trim($message));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';

        return $normalized;
    }
}
