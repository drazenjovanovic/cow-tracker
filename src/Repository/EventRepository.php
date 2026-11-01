<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Domain\EventAction;
use App\Domain\EventFilter;
use App\Domain\EventTarget;
use DateTimeImmutable;

final class EventRepository implements EventRepositoryInterface
{
    private const DATETIME = 'Y-m-d H:i:s';

    public function __construct(private readonly Database $db)
    {
    }

    public function insert(
        ?int $userId,
        EventAction $action,
        ?EventTarget $target,
        ?string $ip,
        ?string $userAgent,
    ): void {
        $this->db->execute(
            'INSERT INTO events (user_id, action, target, ip, user_agent, created_at)
             VALUES (:user_id, :action, :target, :ip, :user_agent, :created_at)',
            [
                'user_id'    => $userId,
                'action'     => $action->value,
                'target'     => $target?->value,
                'ip'         => $ip,
                'user_agent' => $userAgent === null ? null : mb_substr($userAgent, 0, 255),
                'created_at' => (new DateTimeImmutable())->format(self::DATETIME),
            ],
        );
    }

    /**
     * @return list<array{id: int, created_at: string, action: string, target: ?string, ip: ?string,
     *                    user_id: ?int, user_name: ?string, user_email: ?string}>
     */
    public function search(EventFilter $filter, int $limit, int $offset): array
    {
        [$where, $params] = $this->buildWhere($filter);

        /** @var list<array{id: int, created_at: string, action: string, target: ?string, ip: ?string, user_id: ?int, user_name: ?string, user_email: ?string}> */
        return $this->db->execute(
            "SELECT e.id, e.created_at, e.action, e.target, e.ip, e.user_id,
                    u.name AS user_name, u.email AS user_email
             FROM events e
             LEFT JOIN users u ON u.id = e.user_id
             {$where}
             ORDER BY e.created_at DESC, e.id DESC
             LIMIT :limit OFFSET :offset",
            $params + ['limit' => $limit, 'offset' => $offset],
        )->fetchAll();
    }

    public function count(EventFilter $filter): int
    {
        [$where, $params] = $this->buildWhere($filter);

        return (int) $this->db->execute(
            "SELECT COUNT(*) FROM events e {$where}",
            $params,
        )->fetchColumn();
    }

    /**
     * Per-day counters for the report. Days without events are absent from the result.
     *
     * @return array<string, array{view_a: int, view_b: int, click_cow: int, click_download: int}> keyed by Y-m-d
     */
    public function dailyCounts(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $stmt = $this->db->execute(
            "SELECT DATE(created_at) AS day,
                    SUM(CASE WHEN action = 'view-page'    AND target = 'page_a'   THEN 1 ELSE 0 END) AS view_a,
                    SUM(CASE WHEN action = 'view-page'    AND target = 'page_b'   THEN 1 ELSE 0 END) AS view_b,
                    SUM(CASE WHEN action = 'button-click' AND target = 'buy_cow'  THEN 1 ELSE 0 END) AS click_cow,
                    SUM(CASE WHEN action = 'button-click' AND target = 'download' THEN 1 ELSE 0 END) AS click_download
             FROM events
             WHERE created_at >= :from AND created_at < :to
             GROUP BY day",
            [
                'from' => $from->setTime(0, 0)->format(self::DATETIME),
                'to'   => $to->setTime(0, 0)->modify('+1 day')->format(self::DATETIME),
            ],
        );

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(string) $row['day']] = [
                'view_a'         => (int) $row['view_a'],
                'view_b'         => (int) $row['view_b'],
                'click_cow'      => (int) $row['click_cow'],
                'click_download' => (int) $row['click_download'],
            ];
        }

        return $result;
    }

    /** @return array{0: string, 1: array<string, int|string>} */
    private function buildWhere(EventFilter $filter): array
    {
        $conditions = [];
        $params     = [];

        if ($filter->dateFrom !== null) {
            $conditions[] = 'e.created_at >= :date_from';
            $params['date_from'] = $filter->dateFrom->setTime(0, 0)->format(self::DATETIME);
        }
        if ($filter->dateTo !== null) {
            $conditions[] = 'e.created_at < :date_to';
            $params['date_to'] = $filter->dateTo->setTime(0, 0)->modify('+1 day')->format(self::DATETIME);
        }
        if ($filter->userId !== null) {
            $conditions[] = 'e.user_id = :user_id';
            $params['user_id'] = $filter->userId;
        }
        if ($filter->actions !== []) {
            $placeholders = [];
            foreach ($filter->actions as $i => $action) {
                $placeholders[]       = ':action' . $i;
                $params['action' . $i] = $action->value;
            }
            $conditions[] = 'e.action IN (' . implode(', ', $placeholders) . ')';
        }

        return [$conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions), $params];
    }

}
