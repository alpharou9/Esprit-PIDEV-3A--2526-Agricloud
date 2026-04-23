<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    #[Route('/unread', name: 'notifications_unread', methods: ['GET'])]
    public function unread(NotificationRepository $repo): JsonResponse
    {
        $user  = $this->getUser();
        $count = $repo->countUnreadForUser($user);
        $items = $repo->findRecentForUser($user, 10);

        $data = array_map(fn(Notification $n) => [
            'id'        => $n->getId(),
            'message'   => $n->getMessage(),
            'link'      => $n->getLink(),
            'type'      => $n->getType(),
            'isRead'    => $n->isRead(),
            'createdAt' => $n->getCreatedAt()->format('Y-m-d H:i'),
        ], $items);

        return $this->json(['count' => $count, 'items' => $data]);
    }

    #[Route('/{id}/read', name: 'notification_read', methods: ['POST'])]
    public function markRead(Notification $notification, EntityManagerInterface $em): JsonResponse
    {
        if ($notification->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $notification->setIsRead(true);
        $em->flush();

        return $this->json(['ok' => true]);
    }

    #[Route('/read-all', name: 'notifications_read_all', methods: ['POST'])]
    public function markAllRead(NotificationRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        foreach ($repo->findUnreadForUser($this->getUser()) as $n) {
            $n->setIsRead(true);
        }
        $em->flush();

        return $this->json(['ok' => true]);
    }
}
