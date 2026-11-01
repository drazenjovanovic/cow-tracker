<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Role;
use App\Domain\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testFromRowMapsAllFields(): void
    {
        $user = User::fromRow([
            'id' => '7',
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password_hash' => '$2y$hash',
            'role' => 'user',
            'created_at' => '2024-01-01 10:00:00',
            'email_verified_at' => '2024-01-02 08:00:00',
        ]);

        $this->assertSame(7, $user->id);
        $this->assertSame('Alice', $user->name);
        $this->assertSame('alice@example.com', $user->email);
        $this->assertSame(Role::User, $user->role);
        $this->assertSame('2024-01-02 08:00:00', $user->emailVerifiedAt);
    }

    public function testFromRowNullEmailVerifiedAt(): void
    {
        $user = User::fromRow([
            'id' => '1', 'name' => 'Bob', 'email' => 'b@b.com',
            'password_hash' => 'h', 'role' => 'user',
            'created_at' => '2024-01-01 00:00:00',
            'email_verified_at' => '',
        ]);

        $this->assertNull($user->emailVerifiedAt);
        $this->assertFalse($user->isVerified());
    }

    public function testFromRowMissingEmailVerifiedAtIsNull(): void
    {
        $user = User::fromRow([
            'id' => '1', 'name' => 'Bob', 'email' => 'b@b.com',
            'password_hash' => 'h', 'role' => 'admin',
            'created_at' => '2024-01-01 00:00:00',
        ]);

        $this->assertNull($user->emailVerifiedAt);
    }

    public function testIsVerifiedReturnsTrueWhenVerifiedAtIsSet(): void
    {
        $user = new User(1, 'A', 'a@a.com', 'h', Role::User, '2024-01-01', '2024-01-02');

        $this->assertTrue($user->isVerified());
    }

    public function testIsVerifiedReturnsFalseWhenVerifiedAtIsNull(): void
    {
        $user = new User(1, 'A', 'a@a.com', 'h', Role::User, '2024-01-01');

        $this->assertFalse($user->isVerified());
    }

    public function testIsAdminReturnsTrueForAdminRole(): void
    {
        $user = new User(1, 'A', 'a@a.com', 'h', Role::Admin, '2024-01-01');

        $this->assertTrue($user->isAdmin());
    }

    public function testIsAdminReturnsFalseForUserRole(): void
    {
        $user = new User(1, 'A', 'a@a.com', 'h', Role::User, '2024-01-01');

        $this->assertFalse($user->isAdmin());
    }
}
