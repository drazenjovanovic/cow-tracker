<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Domain\Role;
use App\Repository\EmailAlreadyExists;
use App\Repository\UserRepository;
use Tests\Support\IntegrationTestCase;

final class UserRepositoryTest extends IntegrationTestCase
{
    private UserRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new UserRepository(self::db());
    }

    public function testCreateAndFindById(): void
    {
        $user = $this->repo->create('Alice', 'alice@example.com', 'hash123');

        $found = $this->repo->findById($user->id);

        $this->assertNotNull($found);
        $this->assertSame($user->id, $found->id);
        $this->assertSame('Alice', $found->name);
        $this->assertSame('alice@example.com', $found->email);
        $this->assertSame('hash123', $found->passwordHash);
        $this->assertSame(Role::User, $found->role);
        $this->assertNull($found->emailVerifiedAt);
    }

    public function testFindByIdReturnsNullForMissing(): void
    {
        $this->assertNull($this->repo->findById(999999));
    }

    public function testFindByEmail(): void
    {
        $this->repo->create('Bob', 'bob@example.com', 'hash');

        $found = $this->repo->findByEmail('bob@example.com');

        $this->assertNotNull($found);
        $this->assertSame('Bob', $found->name);
        $this->assertSame('bob@example.com', $found->email);
    }

    public function testFindByEmailReturnsNullForMissing(): void
    {
        $this->assertNull($this->repo->findByEmail('nobody@example.com'));
    }

    public function testEmailExistsReturnsTrueForExisting(): void
    {
        $this->repo->create('Carol', 'carol@example.com', 'hash');

        $this->assertTrue($this->repo->emailExists('carol@example.com'));
    }

    public function testEmailExistsReturnsFalseForMissing(): void
    {
        $this->assertFalse($this->repo->emailExists('nobody@example.com'));
    }

    public function testCreateThrowsOnDuplicateEmail(): void
    {
        $this->repo->create('Dave', 'dave@example.com', 'hash');

        $this->expectException(EmailAlreadyExists::class);
        $this->repo->create('Dave2', 'dave@example.com', 'hash2');
    }

    public function testCreateWithAdminRole(): void
    {
        $user = $this->repo->create('Eve', 'eve@example.com', 'hash', Role::Admin);

        $this->assertSame(Role::Admin, $user->role);

        $found = $this->repo->findById($user->id);
        $this->assertNotNull($found);
        $this->assertSame(Role::Admin, $found->role);
    }

    public function testUpdatePasswordHash(): void
    {
        $user = $this->repo->create('Frank', 'frank@example.com', 'oldhash');

        $this->repo->updatePasswordHash($user->id, 'newhash');

        $found = $this->repo->findById($user->id);
        $this->assertNotNull($found);
        $this->assertSame('newhash', $found->passwordHash);
    }

    public function testMarkEmailVerified(): void
    {
        $user = $this->repo->create('Grace', 'grace@example.com', 'hash');
        $this->assertNull($user->emailVerifiedAt);

        $this->repo->markEmailVerified($user->id);

        $found = $this->repo->findById($user->id);
        $this->assertNotNull($found);
        $this->assertNotNull($found->emailVerifiedAt);
    }

    public function testListForSelectOrdersByName(): void
    {
        $this->repo->create('Zara', 'zara@example.com', 'hash');
        $this->repo->create('Alan', 'alan@example.com', 'hash');

        $list = $this->repo->listForSelect();

        $this->assertCount(2, $list);
        $this->assertSame(['id', 'name', 'email'], array_keys($list[0]));
        $this->assertSame('Alan', $list[0]['name']);
        $this->assertSame('Zara', $list[1]['name']);
    }

    public function testCountAdmins(): void
    {
        $this->repo->create('User1', 'user1@example.com', 'hash', Role::User);
        $this->repo->create('Admin1', 'admin1@example.com', 'hash', Role::Admin);
        $this->repo->create('Admin2', 'admin2@example.com', 'hash', Role::Admin);

        $this->assertSame(2, $this->repo->countAdmins());
    }
}
