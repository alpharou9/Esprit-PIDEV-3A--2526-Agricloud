<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->getStatus() === 'blocked') {
            throw new CustomUserMessageAccountStatusException(
                'Your account has been blocked. Please contact an administrator.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // nothing needed post-auth
    }
}
