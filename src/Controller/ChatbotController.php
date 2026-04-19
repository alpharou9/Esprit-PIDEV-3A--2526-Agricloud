<?php

namespace App\Controller;

use App\Service\ChatbotService;
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
    public function message(Request $request, ChatbotService $chatbotService): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $message = trim((string) ($payload['message'] ?? ''));

        if ($message === '') {
            return $this->json([
                'ok' => false,
                'reply' => 'Please type a question before sending your message.',
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'ok' => true,
            'reply' => $chatbotService->reply($message),
        ]);
    }
}
