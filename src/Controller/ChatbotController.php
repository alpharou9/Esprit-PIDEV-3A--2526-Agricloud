<?php

namespace App\Controller;

use App\Service\ChatbotService;
use App\Service\RecipeAssistantService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/assistant')]
#[IsGranted('ROLE_USER')]
class ChatbotController extends AbstractController
{
    #[Route('', name: 'chatbot_page', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('chatbot/index.html.twig');
    }

    #[Route('/message', name: 'chatbot_message', methods: ['POST'])]
    public function message(Request $request, ChatbotService $chatbotService, RecipeAssistantService $recipeAssistantService): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $message = trim((string) ($payload['message'] ?? ''));
        $recipeSlug = trim((string) ($payload['recipe'] ?? ''));

        if ($recipeSlug !== '') {
            $preview = $recipeAssistantService->buildRecipePreview($recipeSlug);

            if ($preview === null) {
                return $this->json([
                    'ok' => false,
                    'reply' => 'I could not find that recipe anymore.',
                ], Response::HTTP_NOT_FOUND);
            }

            $reply = $preview['missing'] === []
                ? sprintf('Great choice. I found all the ingredients for %s.', $preview['name'])
                : sprintf('I found part of the ingredients for %s. Some items are still missing.', $preview['name']);

            return $this->json([
                'ok' => true,
                'reply' => $reply,
                'recipePreview' => $preview,
            ]);
        }

        if ($message === '') {
            return $this->json([
                'ok' => false,
                'reply' => 'Please type a question before sending your message.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $response = $chatbotService->reply($message);

        return $this->json(array_merge([
            'ok' => true,
        ], $response));
    }

    #[Route('/recipe/add', name: 'chatbot_recipe_add', methods: ['POST'])]
    public function addRecipe(Request $request, RecipeAssistantService $recipeAssistantService): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $recipeSlug = trim((string) ($payload['recipe'] ?? ''));
        $token = (string) ($payload['_token'] ?? '');

        if (!$this->isCsrfTokenValid('chatbot_recipe_add', $token)) {
            return $this->json([
                'ok' => false,
                'reply' => 'The recipe confirmation expired. Please try again.',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($recipeSlug === '') {
            return $this->json([
                'ok' => false,
                'reply' => 'Please choose a recipe before adding ingredients to the cart.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $recipeAssistantService->addRecipeToCart($recipeSlug, $this->getUser());

        return $this->json([
            'ok' => $result['success'],
            'reply' => $result['message'],
            'added' => $result['added'],
            'missing' => $result['missing'],
            'redirectUrl' => $result['success'] ? $this->generateUrl('cart_index') : null,
        ], $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);
    }
}
