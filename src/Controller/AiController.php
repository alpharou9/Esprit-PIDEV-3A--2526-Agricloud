<?php

namespace App\Controller;

use App\Service\AiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AiController extends AbstractController
{
    #[Route('/ai/product-description', name: 'ai_product_description', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function productDescription(Request $request, AiService $aiService): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return $this->json(['error' => 'Invalid JSON payload.'], 400);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $category = trim((string) ($payload['category'] ?? '')) ?: null;
        $unit = trim((string) ($payload['unit'] ?? '')) ?: null;
        $price = trim((string) ($payload['price'] ?? '')) ?: null;
        $origin = trim((string) ($payload['origin'] ?? '')) ?: null;
        $stock = trim((string) ($payload['stock'] ?? '')) ?: null;
        $description = trim((string) ($payload['description'] ?? '')) ?: null;

        if ($name === '' && $category === null && $description === null) {
            return $this->json(['error' => 'Enter at least a name, category, or description hint first.'], 422);
        }

        try {
            $draft = $aiService->generateProductDraft(
                $name,
                $category,
                $unit,
                $price,
                $origin,
                $stock,
                $description,
            );
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 503);
        }

        return $this->json($draft);
    }
}
