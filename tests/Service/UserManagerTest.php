<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserManager;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UserManager.
 *
 * Business rules tested:
 *  1. Name is required (2–100 chars, letters/spaces/hyphens/apostrophes only)
 *  2. Email is required and must be valid
 *  3. Password (when set) must be ≥6 chars, contain ≥1 uppercase and ≥1 digit
 *
 * Run with: php bin/phpunit tests/Service/UserManagerTest.php
 */
class UserManagerTest extends TestCase
{
    private UserManager $manager;

    protected function setUp(): void
    {
        $this->manager = new UserManager();
    }

    // ────────────────────────────────────────────────────────────
    // VALID USER
    // ────────────────────────────────────────────────────────────

    public function testValidUserPassesValidation(): void
    {
        $user = new User();
        $user->setName('Farouk Ben Salem');
        $user->setEmail('farouk@agricloud.tn');

        $this->assertTrue($this->manager->validate($user));
    }

    public function testValidUserWithPasswordPassesValidation(): void
    {
        $user = new User();
        $user->setName('Farouk Ben Salem');
        $user->setEmail('farouk@agricloud.tn');
        $user->setPassword('Secure1Pass');

        $this->assertTrue($this->manager->validate($user));
    }

    // ────────────────────────────────────────────────────────────
    // RULE 1 — NAME
    // ────────────────────────────────────────────────────────────

    public function testEmptyNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom est obligatoire');

        $this->manager->validateName('');
    }

    public function testNullNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom est obligatoire');

        $this->manager->validateName(null);
    }

    public function testNameTooShortThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('au moins 2 caractères');

        $this->manager->validateName('A');
    }

    public function testNameTooLongThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('100 caractères');

        $this->manager->validateName(str_repeat('A', 101));
    }

    public function testNameWithInvalidCharactersThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('lettres');

        $this->manager->validateName('F@rouk123!');
    }

    public function testValidNameWithHyphenPasses(): void
    {
        // Should not throw — hyphens are allowed
        $this->manager->validateName('Jean-Pierre');
        $this->assertTrue(true);
    }

    public function testValidNameWithApostrophePasses(): void
    {
        $this->manager->validateName("O'Brien");
        $this->assertTrue(true);
    }

    // ────────────────────────────────────────────────────────────
    // RULE 2 — EMAIL
    // ────────────────────────────────────────────────────────────

    public function testEmptyEmailThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('email est obligatoire');

        $this->manager->validateEmail('');
    }

    public function testInvalidEmailFormatThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('invalide');

        $this->manager->validateEmail('not-an-email');
    }

    public function testEmailMissingAtSignThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->manager->validateEmail('faroukagricloud.tn');
    }

    public function testEmailTooLongThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('150 caractères');

        $longEmail = str_repeat('a', 146) . '@b.tn'; // 151 chars total — over the 150 limit
        $this->manager->validateEmail($longEmail);
    }

    public function testValidEmailPasses(): void
    {
        $this->manager->validateEmail('user@example.com');
        $this->assertTrue(true);
    }

    // ────────────────────────────────────────────────────────────
    // RULE 3 — PASSWORD
    // ────────────────────────────────────────────────────────────

    public function testEmptyPasswordThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('obligatoire');

        $this->manager->validatePassword('');
    }

    public function testPasswordTooShortThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('6 caractères');

        $this->manager->validatePassword('Ab1');
    }

    public function testPasswordWithoutUppercaseThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('majuscule');

        $this->manager->validatePassword('password1');
    }

    public function testPasswordWithoutDigitThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('chiffre');

        $this->manager->validatePassword('Password');
    }

    public function testValidPasswordPasses(): void
    {
        $this->manager->validatePassword('Secure1');
        $this->assertTrue(true);
    }

    // ────────────────────────────────────────────────────────────
    // EDGE CASES
    // ────────────────────────────────────────────────────────────

    /**
     * OAuth users have no password — validate() must not fail for them.
     */
    public function testUserWithNoPasswordPassesValidation(): void
    {
        $user = new User();
        $user->setName('Google User');
        $user->setEmail('googleuser@gmail.com');
        // password is null — OAuth account

        $this->assertTrue($this->manager->validate($user));
    }

    /**
     * Name that is exactly the minimum length (2 chars) should pass.
     */
    public function testNameAtMinimumLengthPasses(): void
    {
        $this->manager->validateName('Li');
        $this->assertTrue(true);
    }

    /**
     * Name that is exactly the maximum length (100 chars) should pass.
     */
    public function testNameAtMaximumLengthPasses(): void
    {
        $this->manager->validateName(str_repeat('A', 100));
        $this->assertTrue(true);
    }
}
