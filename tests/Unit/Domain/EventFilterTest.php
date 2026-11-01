<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\EventAction;
use App\Domain\EventFilter;
use PHPUnit\Framework\TestCase;

final class EventFilterTest extends TestCase
{
    public function testEmptyQueryProducesNoConstraints(): void
    {
        $f = EventFilter::fromQuery([]);

        $this->assertNull($f->dateFrom);
        $this->assertNull($f->dateTo);
        $this->assertNull($f->userId);
        $this->assertSame([], $f->actions);
    }

    public function testValidDatesAreParsed(): void
    {
        $f = EventFilter::fromQuery(['date_from' => '2024-01-01', 'date_to' => '2024-06-30']);

        $this->assertSame('2024-01-01', $f->dateFrom?->format('Y-m-d'));
        $this->assertSame('2024-06-30', $f->dateTo?->format('Y-m-d'));
    }

    public function testInvertedDateRangeIsSwapped(): void
    {
        $f = EventFilter::fromQuery(['date_from' => '2024-12-31', 'date_to' => '2024-01-01']);

        $this->assertSame('2024-01-01', $f->dateFrom?->format('Y-m-d'));
        $this->assertSame('2024-12-31', $f->dateTo?->format('Y-m-d'));
    }

    public function testGarbageDatesAreIgnored(): void
    {
        $f = EventFilter::fromQuery(['date_from' => 'not-a-date', 'date_to' => '2024-13-01']);

        $this->assertNull($f->dateFrom);
        $this->assertNull($f->dateTo);
    }

    public function testValidUserIdIsParsed(): void
    {
        $f = EventFilter::fromQuery(['user_id' => '42']);

        $this->assertSame(42, $f->userId);
    }

    public function testNonNumericUserIdIsIgnored(): void
    {
        $f = EventFilter::fromQuery(['user_id' => 'abc']);

        $this->assertNull($f->userId);
    }

    public function testZeroUserIdIsIgnored(): void
    {
        $f = EventFilter::fromQuery(['user_id' => '0']);

        $this->assertNull($f->userId);
    }

    public function testValidActionsAreParsed(): void
    {
        $f = EventFilter::fromQuery(['actions' => ['login', 'logout']]);

        $this->assertSame([EventAction::Login, EventAction::Logout], $f->actions);
    }

    public function testUnknownActionsAreIgnored(): void
    {
        $f = EventFilter::fromQuery(['actions' => ['login', 'hack']]);

        $this->assertSame([EventAction::Login], $f->actions);
    }

    public function testDuplicateActionsAreDeduped(): void
    {
        $f = EventFilter::fromQuery(['actions' => ['login', 'login']]);

        $this->assertCount(1, $f->actions);
    }

    public function testToQueryRoundTrips(): void
    {
        $f = EventFilter::fromQuery([
            'date_from' => '2024-03-01',
            'date_to'   => '2024-03-31',
            'user_id'   => '5',
            'actions'   => ['login'],
        ]);

        $q = $f->toQuery();

        $this->assertSame('2024-03-01', $q['date_from']);
        $this->assertSame('2024-03-31', $q['date_to']);
        $this->assertSame(5, $q['user_id']);
        $this->assertSame(['login'], $q['actions']);
    }

    public function testToQueryOmitsEmptyFields(): void
    {
        $f = EventFilter::fromQuery([]);

        $this->assertSame([], $f->toQuery());
    }
}
