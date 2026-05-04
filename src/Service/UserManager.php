<?php

namespace App\Service;

use App\Entity\User;

/**
 * UserManager encapsulates the business rules for the User entity.
 * All validation methods throw \InvalidArgumentException on failure
 * so they are easily testable with PHPUnit.
 */
class UserManager
{
    /**
     * Validate a User object against all business rules.
     * Returns true if valid, throws \InvalidArgumentException otherwise.
     *
     * Business rules:
     *  1. Name is required (2–100 characters, letters/spaces/hyphens only)
     *  2. Email is required and must be a valid address
     *  3. Password (when provided) must be at least 6 characters,
     *     contain at least one uppercase letter and one number
     */
    public function validate(User $user): bool
    {
        $this->validateName($user->getName());
        $this->validateEmail($user->getEmail());

        // Password is optional (OAuth users have none), only validate when set
        if ($user->getPassword() !== null && $user->getPassword() !== '') {
            $this->validatePassword($user->getPassword());
        }

        return true;
    }

    /**
     * Rule 1 — Name must be present and 2–100 characters long.
     * Only letters, spaces, hyphens and apostrophes are allowed.
     */
    public function validateName(?string $name): void
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Le nom est obligatoire.');
        }

        if (strlen($name) < 2) {
            throw new \InvalidArgumentException('Le nom doit contenir au moins 2 caractères.');
        }

        if (strlen($name) > 100) {
            throw new \InvalidArgumentException('Le nom ne peut pas dépasser 100 caractères.');
        }

        if (!preg_match('/^[\p{L}\s\-\']+$/u', $name)) {
            throw new \InvalidArgumentException('Le nom ne peut contenir que des lettres, espaces, tirets et apostrophes.');
        }
    }

    /**
     * Rule 2 — Email must be present and well-formed.
     */
    public function validateEmail(?string $email): void
    {
        if (empty($email)) {
            throw new \InvalidArgumentException('L\'email est obligatoire.');
        }

        if (strlen($email) > 150) {
            throw new \InvalidArgumentException('L\'email ne peut pas dépasser 150 caractères.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('L\'adresse email est invalide.');
        }
    }

    /**
     * Rule 3 — Password strength (only applied when a password is provided).
     */
    public function validatePassword(?string $password): void
    {
        if (empty($password)) {
            throw new \InvalidArgumentException('Le mot de passe est obligatoire.');
        }

        if (strlen($password) < 6) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins 6 caractères.');
        }

        if (!preg_match('/[A-Z]/', $password)) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins une lettre majuscule.');
        }

        if (!preg_match('/[0-9]/', $password)) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins un chiffre.');
        }
    }
}
