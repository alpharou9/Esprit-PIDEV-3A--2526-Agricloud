<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserManager;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    private UserManager $manager;

    protected function setUp(): void
    {
        $this->manager = new UserManager();
    }

    // Test 1: Valid user passes validation
    public function testValidUserPassesValidation(): void
    {
        $user = new User();
        $user->setName('Farouk Nakkach');
        $user->setEmail('farouk@agricloud.tn');

        $this->assertTrue($this->manager->validate($user));
    }

    // Test 2: Empty name throws exception
    public function testEmptyNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name is required.');

        $user = new User();
        $user->setName('');
        $user->setEmail('farouk@agricloud.tn');

        $this->manager->validate($user);
    }

    // Test 3: Name too short throws exception
    public function testNameTooShortThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name must be at least 2 characters.');

        $user = new User();
        $user->setName('A');
        $user->setEmail('farouk@agricloud.tn');

        $this->manager->validate($user);
    }

    // Test 4: Invalid email throws exception
    public function testInvalidEmailThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address.');

        $user = new User();
        $user->setName('Farouk Nakkach');
        $user->setEmail('not-an-email');

        $this->manager->validate($user);
    }

    // Test 5: Blocked user cannot login
    public function testBlockedUserCannotLogin(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Account is blocked.');

        $user = new User();
        $user->setName('Farouk Nakkach');
        $user->setEmail('farouk@agricloud.tn');
        $user->setStatus('blocked');

        $this->manager->canLogin($user);
    }

    // Test 6: Password shorter than 8 characters is rejected
    public function testWeakPasswordThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters.');

        $this->manager->hasStrongPassword('abc');
    }
}
