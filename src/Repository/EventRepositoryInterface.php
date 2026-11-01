<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\EventAction;
use App\Domain\EventFilter;
use App\Domain\EventTarget;
use DateTimeImmutable;

interface EventRepositoryInterface
{
    public function insert(
        ?int $userId,
        EventAction $action,
        ?EventTarget $target,
        ?string $ip,
        ?string $userAgent,
    ): void;

    /** @return list<array{id: int, created_at: string, action: string, target: ?string, ip: ?string, user_id: ?int, user_name: ?string, user_email: ?string}> */
    public function search(EventFilter $filter, int $limit, int $offset): array;

    public function count(EventFilter $filter): int;

    /** @return array<string, array{view_a: int, view_b: int, click_cow: int, click_download: int}> keyed by Y-m-d */
    public function dailyCounts(DateTimeImmutable $from, DateTimeImmutable $to): array;
}
