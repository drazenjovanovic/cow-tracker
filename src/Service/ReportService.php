<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\EventRepositoryInterface;
use DateTimeImmutable;

final class ReportService
{
    public const ZERO = ['view_a' => 0, 'view_b' => 0, 'click_cow' => 0, 'click_download' => 0];

    public function __construct(private readonly EventRepositoryInterface $events)
    {
    }

    /**
     * One row per day in [from, to] (inclusive, ascending), zero-filled for days without activity.
     *
     * @return array{
     *     rows: list<array{date: string, view_a: int, view_b: int, click_cow: int, click_download: int}>,
     *     totals: array{view_a: int, view_b: int, click_cow: int, click_download: int}
     * }
     */
    public function daily(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $counts = $this->events->dailyCounts($from, $to);

        $rows = [];
        $totals = self::ZERO;
        for ($day = $from->setTime(0, 0); $day <= $to; $day = $day->modify('+1 day')) {
            $key = $day->format('Y-m-d');
            $row = $counts[$key] ?? self::ZERO;
            $rows[] = ['date' => $key] + $row;
            foreach ($totals as $metric => $sum) {
                $totals[$metric] = $sum + $row[$metric];
            }
        }

        return ['rows' => $rows, 'totals' => $totals];
    }
}
