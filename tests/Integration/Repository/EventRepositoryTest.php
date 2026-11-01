<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Domain\EventAction;
use App\Domain\EventFilter;
use App\Domain\EventTarget;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Tests\Support\IntegrationTestCase;

final class EventRepositoryTest extends IntegrationTestCase
{
    private EventRepository $events;
    private UserRepository $users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->events = new EventRepository(self::db());
        $this->users  = new UserRepository(self::db());
    }

    public function testInsertAppearsInSearch(): void
    {
        $user = $this->users->create('Alice', 'alice@example.com', 'hash');

        $this->events->insert($user->id, EventAction::Login, null, '1.2.3.4', 'TestAgent/1.0');

        $rows = $this->events->search(new EventFilter(), 10, 0);
        $this->assertCount(1, $rows);
        $this->assertSame('login', $rows[0]['action']);
        $this->assertSame('alice@example.com', $rows[0]['user_email']);
    }

    public function testSearchReturnsNewestFirst(): void
    {
        $this->events->insert(null, EventAction::Login, null, null, null);
        $this->events->insert(null, EventAction::Registration, null, null, null);

        $rows = $this->events->search(new EventFilter(), 10, 0);

        $this->assertCount(2, $rows);
        $this->assertSame('registration', $rows[0]['action']);
        $this->assertSame('login', $rows[1]['action']);
    }

    public function testSearchFiltersByUserId(): void
    {
        $alice = $this->users->create('Alice', 'alice@example.com', 'hash');
        $bob   = $this->users->create('Bob', 'bob@example.com', 'hash');

        $this->events->insert($alice->id, EventAction::Login, null, null, null);
        $this->events->insert($bob->id, EventAction::Login, null, null, null);

        $filter = new EventFilter(userId: $alice->id);
        $rows   = $this->events->search($filter, 10, 0);

        $this->assertCount(1, $rows);
        $this->assertSame($alice->id, $rows[0]['user_id']);
    }

    public function testSearchFiltersByAction(): void
    {
        $this->events->insert(null, EventAction::Login, null, null, null);
        $this->events->insert(null, EventAction::Registration, null, null, null);
        $this->events->insert(null, EventAction::ViewPage, EventTarget::PageA, null, null);

        $filter = new EventFilter(actions: [EventAction::Login, EventAction::Registration]);
        $rows   = $this->events->search($filter, 10, 0);

        $this->assertCount(2, $rows);
        foreach ($rows as $row) {
            $this->assertContains($row['action'], ['login', 'registration']);
        }
    }

    public function testSearchFiltersByDateRange(): void
    {
        $yesterday = (new DateTimeImmutable('yesterday'));
        $today     = (new DateTimeImmutable('today'));

        $this->events->insert(null, EventAction::Login, null, null, null);

        $filter = new EventFilter(dateFrom: $yesterday, dateTo: $today);
        $this->assertCount(1, $this->events->search($filter, 10, 0));

        $filter = new EventFilter(dateFrom: $today->modify('+1 day'));
        $this->assertCount(0, $this->events->search($filter, 10, 0));
    }

    public function testSearchPagination(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->events->insert(null, EventAction::Login, null, null, null);
        }

        $page1 = $this->events->search(new EventFilter(), 3, 0);
        $page2 = $this->events->search(new EventFilter(), 3, 3);

        $this->assertCount(3, $page1);
        $this->assertCount(2, $page2);
        $this->assertNotSame($page1[0]['id'], $page2[0]['id']);
    }

    public function testCountMatchesSearch(): void
    {
        $user = $this->users->create('Alice', 'alice@example.com', 'hash');

        for ($i = 0; $i < 7; $i++) {
            $this->events->insert($user->id, EventAction::Login, null, null, null);
        }
        $this->events->insert(null, EventAction::Registration, null, null, null);

        $filter = new EventFilter(userId: $user->id);

        $this->assertSame(7, $this->events->count($filter));
        $this->assertCount(7, $this->events->search($filter, 100, 0));
    }

    public function testDailyCountsAggregates(): void
    {
        $this->events->insert(null, EventAction::ViewPage, EventTarget::PageA, null, null);
        $this->events->insert(null, EventAction::ViewPage, EventTarget::PageA, null, null);
        $this->events->insert(null, EventAction::ViewPage, EventTarget::PageB, null, null);
        $this->events->insert(null, EventAction::ButtonClick, EventTarget::BuyCow, null, null);
        $this->events->insert(null, EventAction::Login, null, null, null);

        $today  = new DateTimeImmutable('today');
        $counts = $this->events->dailyCounts($today, $today);
        $row    = $counts[$today->format('Y-m-d')] ?? null;

        $this->assertNotNull($row, 'Expected a row for today in dailyCounts result');
        $this->assertSame(2, $row['view_a']);
        $this->assertSame(1, $row['view_b']);
        $this->assertSame(1, $row['click_cow']);
        $this->assertSame(0, $row['click_download']);
    }

    public function testDailyCountsExcludesDaysOutsideRange(): void
    {
        $this->events->insert(null, EventAction::Login, null, null, null);

        $yesterday = new DateTimeImmutable('yesterday');
        $counts    = $this->events->dailyCounts($yesterday, $yesterday);

        $this->assertArrayNotHasKey((new DateTimeImmutable('today'))->format('Y-m-d'), $counts);
    }

    public function testInsertWithNullUser(): void
    {
        $this->events->insert(null, EventAction::Registration, null, '10.0.0.1', null);

        $rows = $this->events->search(new EventFilter(), 10, 0);
        $this->assertCount(1, $rows);
        $this->assertNull($rows[0]['user_id']);
        $this->assertNull($rows[0]['user_name']);
    }
}
