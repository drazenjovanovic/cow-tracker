<?php

declare(strict_types=1);

namespace App\Domain;

use App\Support\DateInput;
use DateTimeImmutable;

/** Validated filter for the statistics table. */
final class EventFilter
{
    /** @param list<EventAction> $actions Empty list means "all actions". */
    public function __construct(
        public readonly ?DateTimeImmutable $dateFrom = null,
        public readonly ?DateTimeImmutable $dateTo = null,
        public readonly ?int $userId = null,
        public readonly array $actions = [],
    ) {
    }

    /** @param array<string, mixed> $query Raw $_GET; anything invalid is ignored. */
    public static function fromQuery(array $query): self
    {
        $from = DateInput::parse($query['date_from'] ?? null);
        $to = DateInput::parse($query['date_to'] ?? null);
        if ($from !== null && $to !== null && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        $userRaw = $query['user_id'] ?? null;
        $userId = is_string($userRaw) && ctype_digit($userRaw) && (int) $userRaw > 0 ? (int) $userRaw : null;

        $actions = [];
        $actionsRaw = $query['actions'] ?? [];
        foreach (is_array($actionsRaw) ? $actionsRaw : [] as $value) {
            $action = is_string($value) ? EventAction::tryFrom($value) : null;
            if ($action !== null && !in_array($action, $actions, true)) {
                $actions[] = $action;
            }
        }

        return new self($from, $to, $userId, $actions);
    }

    /** @return array<string, mixed> Query-string params that reproduce this filter (for pagination links). */
    public function toQuery(): array
    {
        return array_filter([
            'date_from' => $this->dateFrom?->format('Y-m-d'),
            'date_to' => $this->dateTo?->format('Y-m-d'),
            'user_id' => $this->userId,
            'actions' => array_map(static fn (EventAction $a): string => $a->value, $this->actions),
        ], static fn (mixed $v): bool => $v !== null && $v !== []);
    }
}
