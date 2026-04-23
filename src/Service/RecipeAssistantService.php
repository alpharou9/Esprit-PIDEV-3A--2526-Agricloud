<?php

namespace App\Service;

use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartItemRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class RecipeAssistantService
{
    /**
     * Fixed recipes keep the first version simple and predictable.
     */
    private const RECIPES = [
        'garden-salad' => [
            'name' => 'Garden Salad',
            'description' => 'A fresh salad built around simple farm vegetables.',
            'ingredients' => [
                ['label' => 'Tomatoes', 'terms' => ['tomato', 'tomate']],
                ['label' => 'Peppers', 'terms' => ['pepper', 'poivron']],
                ['label' => 'Parsley', 'terms' => ['parsley', 'persil']],
            ],
        ],
        'summer-salad' => [
            'name' => 'Summer Cucumber Salad',
            'description' => 'A light summer salad with crunchy vegetables and herbs.',
            'ingredients' => [
                ['label' => 'Cucumbers', 'terms' => ['cucumber', 'concombre']],
                ['label' => 'Tomatoes', 'terms' => ['tomato', 'tomate']],
                ['label' => 'Mint', 'terms' => ['mint', 'menthe']],
            ],
        ],
        'herbed-chicken' => [
            'name' => 'Herbed Chicken Plate',
            'description' => 'A simple chicken recipe with herbs and vegetables.',
            'ingredients' => [
                ['label' => 'Chicken', 'terms' => ['chicken', 'poulet']],
                ['label' => 'Peppers', 'terms' => ['pepper', 'poivron']],
                ['label' => 'Parsley', 'terms' => ['parsley', 'persil']],
            ],
        ],
        'roasted-chicken-tray' => [
            'name' => 'Roasted Chicken Tray',
            'description' => 'A hearty tray of chicken and root vegetables.',
            'ingredients' => [
                ['label' => 'Chicken', 'terms' => ['chicken', 'poulet']],
                ['label' => 'Potatoes', 'terms' => ['potato', 'pomme de terre', 'aardappel']],
                ['label' => 'Carrots', 'terms' => ['carrot', 'carotte']],
                ['label' => 'Onions', 'terms' => ['onion', 'oignon']],
            ],
        ],
        'farm-breakfast' => [
            'name' => 'Farm Breakfast Bowl',
            'description' => 'A quick breakfast idea with dairy and fruit.',
            'ingredients' => [
                ['label' => 'Milk', 'terms' => ['milk', 'lait']],
                ['label' => 'Yogurt', 'terms' => ['yogurt', 'yaourt', 'yaought']],
                ['label' => 'Raspberries', 'terms' => ['raspberry', 'framboise']],
            ],
        ],
        'fruit-yogurt-cup' => [
            'name' => 'Fruit Yogurt Cup',
            'description' => 'A quick snack bowl with yogurt and seasonal fruit.',
            'ingredients' => [
                ['label' => 'Yogurt', 'terms' => ['yogurt', 'yaourt', 'yaought']],
                ['label' => 'Strawberries', 'terms' => ['strawberry', 'fraise']],
                ['label' => 'Apples', 'terms' => ['apple', 'pomme']],
            ],
        ],
        'vegetable-stew' => [
            'name' => 'Farm Vegetable Stew',
            'description' => 'A comforting vegetable stew with everyday market ingredients.',
            'ingredients' => [
                ['label' => 'Potatoes', 'terms' => ['potato', 'pomme de terre', 'aardappel']],
                ['label' => 'Carrots', 'terms' => ['carrot', 'carotte']],
                ['label' => 'Onions', 'terms' => ['onion', 'oignon']],
                ['label' => 'Zucchini', 'terms' => ['zucchini', 'courgette']],
            ],
        ],
        'stuffed-peppers' => [
            'name' => 'Stuffed Peppers',
            'description' => 'A colorful pepper dish with beef and herbs.',
            'ingredients' => [
                ['label' => 'Peppers', 'terms' => ['pepper', 'poivron']],
                ['label' => 'Ground Beef', 'terms' => ['ground beef', 'viande hachee', 'boeuf hache']],
                ['label' => 'Parsley', 'terms' => ['parsley', 'persil']],
                ['label' => 'Tomatoes', 'terms' => ['tomato', 'tomate']],
            ],
        ],
        'omelette-plate' => [
            'name' => 'Farm Omelette Plate',
            'description' => 'A simple omelette served with herbs and vegetables.',
            'ingredients' => [
                ['label' => 'Eggs', 'terms' => ['egg', 'eggs', 'oeuf']],
                ['label' => 'Parsley', 'terms' => ['parsley', 'persil']],
                ['label' => 'Onions', 'terms' => ['onion', 'oignon']],
            ],
        ],
        'goat-cheese-salad' => [
            'name' => 'Goat Cheese Salad',
            'description' => 'A fresh salad with cheese, cucumber, and tomatoes.',
            'ingredients' => [
                ['label' => 'Goat Cheese', 'terms' => ['goat cheese', 'chevre', 'fromage de chevre']],
                ['label' => 'Tomatoes', 'terms' => ['tomato', 'tomate']],
                ['label' => 'Cucumbers', 'terms' => ['cucumber', 'concombre']],
            ],
        ],
        'almond-fruit-mix' => [
            'name' => 'Almond Fruit Mix',
            'description' => 'A healthy snack bowl with nuts and fruit.',
            'ingredients' => [
                ['label' => 'Almonds', 'terms' => ['almond', 'amande']],
                ['label' => 'Apples', 'terms' => ['apple', 'pomme']],
                ['label' => 'Pears', 'terms' => ['pear', 'poire']],
            ],
        ],
        'citrus-fruit-plate' => [
            'name' => 'Citrus Fruit Plate',
            'description' => 'A refreshing fruit plate built around citrus produce.',
            'ingredients' => [
                ['label' => 'Oranges', 'terms' => ['orange']],
                ['label' => 'Lemons', 'terms' => ['lemon', 'citron']],
                ['label' => 'Apples', 'terms' => ['apple', 'pomme']],
            ],
        ],
        'wheat-and-chickpea-bowl' => [
            'name' => 'Wheat and Chickpea Bowl',
            'description' => 'A filling grain bowl with chickpeas and herbs.',
            'ingredients' => [
                ['label' => 'Durum Wheat', 'terms' => ['durum', 'wheat', 'ble']],
                ['label' => 'Chickpeas', 'terms' => ['chickpea', 'pois chiche']],
                ['label' => 'Parsley', 'terms' => ['parsley', 'persil']],
                ['label' => 'Tomatoes', 'terms' => ['tomato', 'tomate']],
            ],
        ],
        'milk-and-berries-smoothie' => [
            'name' => 'Milk and Berries Smoothie',
            'description' => 'A quick breakfast smoothie with milk and fresh berries.',
            'ingredients' => [
                ['label' => 'Milk', 'terms' => ['milk', 'lait']],
                ['label' => 'Strawberries', 'terms' => ['strawberry', 'fraise']],
                ['label' => 'Raspberries', 'terms' => ['raspberry', 'framboise']],
            ],
        ],
    ];

    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly CartItemRepository $cartItemRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function isRecipeRequest(string $message): bool
    {
        foreach (['recipe', 'recipes', 'dish', 'cook', 'meal'] as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    public function findRecipeSlugFromMessage(string $message): ?string
    {
        foreach (self::RECIPES as $slug => $recipe) {
            $haystacks = [
                mb_strtolower($recipe['name']),
                mb_strtolower($recipe['description']),
            ];

            foreach ($recipe['ingredients'] as $ingredient) {
                $haystacks[] = mb_strtolower($ingredient['label']);
                foreach ($ingredient['terms'] as $term) {
                    $haystacks[] = mb_strtolower($term);
                }
            }

            foreach ($haystacks as $haystack) {
                if ($haystack !== '' && str_contains($message, $haystack)) {
                    return $slug;
                }
            }
        }

        return null;
    }

    public function getRecipeSuggestions(): array
    {
        $recipes = [];

        foreach (self::RECIPES as $slug => $recipe) {
            $recipes[] = [
                'slug' => $slug,
                'name' => $recipe['name'],
                'description' => $recipe['description'],
                'ingredientCount' => count($recipe['ingredients']),
            ];
        }

        return $recipes;
    }

    public function buildRecipePreview(string $slug): ?array
    {
        $recipe = self::RECIPES[$slug] ?? null;

        if ($recipe === null) {
            return null;
        }

        $matchedIngredients = [];
        $missingIngredients = [];

        foreach ($recipe['ingredients'] as $ingredient) {
            $product = $this->productRepository->findBestMarketplaceMatchForTerms($ingredient['terms']);

            if ($product instanceof Product) {
                $matchedIngredients[] = [
                    'label' => $ingredient['label'],
                    'productId' => $product->getId(),
                    'productName' => $product->getName(),
                    'productPrice' => $product->getPrice(),
                    'productUnit' => $product->getUnit(),
                ];
            } else {
                $missingIngredients[] = $ingredient['label'];
            }
        }

        return [
            'slug' => $slug,
            'name' => $recipe['name'],
            'description' => $recipe['description'],
            'matches' => $matchedIngredients,
            'missing' => $missingIngredients,
            'canAdd' => $matchedIngredients !== [],
        ];
    }

    public function addRecipeToCart(string $slug, User $user): array
    {
        $preview = $this->buildRecipePreview($slug);

        if ($preview === null) {
            return [
                'success' => false,
                'message' => 'I could not find that recipe anymore.',
                'added' => [],
                'missing' => [],
            ];
        }

        $addedProducts = [];
        $missingIngredients = $preview['missing'];

        foreach ($preview['matches'] as $match) {
            /** @var Product|null $product */
            $product = $this->productRepository->find($match['productId']);

            if (!$product instanceof Product || $product->getQuantity() <= 0) {
                $missingIngredients[] = $match['label'];
                continue;
            }

            $existingItem = $this->cartItemRepository->findOneBy([
                'user' => $user,
                'product' => $product,
            ]);

            $currentQuantity = $existingItem?->getQuantity() ?? 0;

            if ($currentQuantity >= $product->getQuantity()) {
                $missingIngredients[] = $match['label'];
                continue;
            }

            if ($existingItem instanceof CartItem) {
                $existingItem->setQuantity($currentQuantity + 1);
                $existingItem->setUpdatedAt(new \DateTime());
            } else {
                $cartItem = new CartItem();
                $cartItem->setUser($user);
                $cartItem->setProduct($product);
                $cartItem->setQuantity(1);
                $cartItem->setCreatedAt(new \DateTime());
                $this->entityManager->persist($cartItem);
            }

            $addedProducts[] = $product->getName();
        }

        $this->entityManager->flush();

        if ($addedProducts === []) {
            return [
                'success' => false,
                'message' => 'None of the recipe ingredients are available in the marketplace right now.',
                'added' => [],
                'missing' => array_values(array_unique($missingIngredients)),
            ];
        }

        $message = sprintf(
            'I added %d recipe ingredient%s to your cart.',
            count($addedProducts),
            count($addedProducts) > 1 ? 's' : ''
        );

        if ($missingIngredients !== []) {
            $message .= ' Missing: ' . implode(', ', array_values(array_unique($missingIngredients))) . '.';
        }

        return [
            'success' => true,
            'message' => $message,
            'added' => $addedProducts,
            'missing' => array_values(array_unique($missingIngredients)),
        ];
    }
}
