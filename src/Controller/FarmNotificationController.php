<?php

namespace App\Controller;

use App\Entity\FarmNotification;
use App\Repository\FarmNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/farm-notifications')]
#[IsGranted('ROLE_USER')]
class FarmNotificationController extends AbstractController
{
    #[Route('/button', name: 'farm_notification_button', methods: ['GET'])]
    public function button(FarmNotificationRepository $repository): Response
    {
        $user = $this->getUser();
        if ($user === null) {
            return new Response('');
        }

        return $this->render('farm_notification/_button.html.twig', [
            'notifications' => $repository->findRecentForUser($user),
            'unreadCount' => $repository->countUnreadForUser($user),
        ]);
    }

    #[Route('', name: 'farm_notification_index', methods: ['GET'])]
    public function index(FarmNotificationRepository $repository): Response
    {
        $user = $this->getUser();
        if ($user === null) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('farm_notification/index.html.twig', [
            'notifications' => $repository->findRecentForUser($user, 50),
        ]);
    }

    #[Route('/{id}/open', name: 'farm_notification_open', methods: ['GET'])]
    public function open(FarmNotification $notification, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($user === null || $notification->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if (!$notification->isRead()) {
            $notification->setIsRead(true);
            $entityManager->flush();
        }

        return $this->redirectToRoute('farm_show', [
            'id' => $notification->getFarm()->getId(),
        ]);
    }
}
