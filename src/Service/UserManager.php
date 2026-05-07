<?php

namespace App\Service;

use App\Entity\User;

class UserManager
{
    public function validate(User $user): bool
    {
        if (empty($user->getName())) {
            throw new \InvalidArgumentException('Name is required.');
        }

        if (strlen($user->getName()) < 2) {
            throw new \InvalidArgumentException('Name must be at least 2 characters.');
        }

        if (empty($user->getEmail())) {
            throw new \InvalidArgumentException('Email is required.');
        }

        if (!filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address.');
        }

        return true;
    }

    public function isBlocked(User $user): bool
    {
        return $user->getStatus() === 'blocked';
    }

    public function canLogin(User $user): bool
    {
        if ($this->isBlocked($user)) {
            throw new \RuntimeException('Account is blocked.');
        }
        return true;
    }

    public function hasStrongPassword(string $password): bool
    {
        if (strlen($password) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters.');
        }
        return true;
    }
}
