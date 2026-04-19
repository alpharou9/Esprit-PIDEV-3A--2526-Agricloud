<?php

namespace App\Service;

class ChatbotService
{
    /**
     * Simple keyword matching keeps the assistant predictable and easy to maintain.
     */
    public function reply(string $message): string
    {
        $normalized = $this->normalize($message);

        if ($normalized === '') {
            return 'Ask me about products, cart, orders, farms, events, blog posts, or your account and I will guide you.';
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
                return $response['answer'];
            }
        }

        return 'I am not sure about that yet. Try asking about products, cart, checkout, orders, farms, events, blog, profile, or account actions on the platform.';
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
