<?php

namespace App\Service;

use App\Entity\Farm;
use App\Entity\FarmNotification;
use Doctrine\ORM\EntityManagerInterface;

class FarmNotificationService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function createReviewNotification(Farm $farm, string $status): void
    {
        $owner = $farm->getUser();
        if ($owner === null) {
            return;
        }

        $notification = new FarmNotification();
        $notification
            ->setUser($owner)
            ->setFarm($farm)
            ->setType('farm_review')
            ->setStatus($status)
            ->setTitle($status === 'approved' ? 'Farm approved' : 'Farm rejected')
            ->setMessage($status === 'approved'
                ? sprintf('Your farm "%s" has been approved by an administrator.', $farm->getName())
                : sprintf('Your farm "%s" has been rejected by an administrator.', $farm->getName()))
            ->setIsRead(false)
            ->setCreatedAt(new \DateTime());

        $this->entityManager->persist($notification);
    }
}
