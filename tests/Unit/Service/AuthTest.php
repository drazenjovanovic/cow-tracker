<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Domain\Role;
use App\Domain\User;
use App\Repository\UserRepositoryInterface;
use App\Service\Auth;
use PHPUnit\Framework\TestCase;
use Tests\Support\ArraySession;

final class AuthTest extends TestCase
{
    private ArraySession $session;
    private UserRepositoryInterface $users;
    private Auth $auth;

    private static function makeUser(int $id = 1, bool $verified = true, Role $role = Role::User): User
    {
        return new User(
            $id,
            'Test',
            'test@example.com',
            password_hash('password123', PASSWORD_DEFAULT),
            $role,
            '2024-01-01 00:00:00',
            $verified ? '2024-01-02 00:00:00' : null,
        );
    }

    protected function setUp(): void
    {
        $this->session = new ArraySession();
        $this->users   = $this->createMock(UserRepositoryInterface::class);
        $this->auth    = new Auth($this->session, $this->users);
    }

    public function testCheckReturnsFalseWhenNoSessionId(): void
    {
        $this->assertFalse($this->auth->check());
    }

    public function testCheckReturnsTrueAfterLogin(): void
    {
        $user = self::makeUser();
        $this->auth->login($user);

        $this->assertTrue($this->auth->check());
    }

    public function testUserReturnsNullBeforeLogin(): void
    {
        $this->assertNull($this->auth->user());
    }

    public function testUserReturnsUserAfterLogin(): void
    {
        $user = self::makeUser();
        $this->auth->login($user);

        $this->assertSame($user, $this->auth->user());
    }

    public function testLoginStoresUserIdInSession(): void
    {
        $user = self::makeUser(42);
        $this->auth->login($user);

        $this->assertSame(42, $this->session->get('user_id'));
    }

    public function testLogoutClearsUser(): void
    {
        $user = self::makeUser();
        $this->auth->login($user);
        $this->auth->logout();

        $this->assertFalse($this->auth->check());
        $this->assertNull($this->auth->user());
    }

    public function testLogoutInvalidatesSession(): void
    {
        $this->session->set('user_id', 1);
        $this->auth->logout();

        $this->assertNull($this->session->get('user_id'));
    }

    public function testAttemptReturnsNullForUnknownEmail(): void
    {
        $this->users->method('findByEmail')->willReturn(null);

        $result = $this->auth->attempt('nobody@example.com', 'password123');

        $this->assertNull($result);
    }

    public function testAttemptReturnsNullForWrongPassword(): void
    {
        $user = self::makeUser();
        $this->users->method('findByEmail')->willReturn($user);

        $result = $this->auth->attempt('test@example.com', 'wrong-password');

        $this->assertNull($result);
    }

    public function testAttemptReturnsUserForCorrectCredentials(): void
    {
        $user = self::makeUser();
        $this->users->method('findByEmail')->willReturn($user);

        $result = $this->auth->attempt('test@example.com', 'password123');

        $this->assertSame($user, $result);
    }

    public function testUserResolvesFromSessionOnNextRequest(): void
    {
        $user = self::makeUser(99);
        $this->session->set('user_id', 99);
        $this->users->method('findById')->with(99)->willReturn($user);

        $resolved = $this->auth->user();

        $this->assertSame($user, $resolved);
    }

    public function testDeletedUserClearsSessionId(): void
    {
        $this->session->set('user_id', 99);
        $this->users->method('findById')->willReturn(null);

        $this->auth->user();

        $this->assertNull($this->session->get('user_id'));
    }
}
