<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Domain\EventAction;
use App\Domain\EventFilter;
use App\Repository\EventRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Service\ReportService;
use App\Support\DateInput;
use App\Support\LineChart;
use DateTimeImmutable;

final class AdminController extends Controller
{
    private const PER_PAGE = 25;
    private const DEFAULT_RANGE_DAYS = 30;
    private const MAX_RANGE_DAYS = 366;

    /** Chart/legend definition: metric key => [label, CSS class, marker shape]. */
    private const SERIES = [
        'view_a' => ['Page A views', 'series-1', 'circle'],
        'view_b' => ['Page B views', 'series-2', 'square'],
        'click_cow' => ['Clicks "Buy a cow"', 'series-3', 'triangle'],
        'click_download' => ['Clicks "Download"', 'series-4', 'diamond'],
    ];

    public function __construct(
        View $view,
        private readonly EventRepositoryInterface $events,
        private readonly UserRepositoryInterface $users,
        private readonly ReportService $reports,
        private readonly string $timezone,
    ) {
        parent::__construct($view);
    }

    public function stats(Request $request): Response
    {
        $filter = EventFilter::fromQuery($request->query);

        $total = $this->events->count($filter);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = $this->intParam($request->query['page'] ?? null, 1);
        $page = min(max(1, $page), $pages);

        return $this->render('admin/stats', [
            'title' => 'Statistics',
            'filter' => $filter,
            'filterQuery' => $filter->toQuery(),
            'users' => $this->users->listForSelect(),
            'actions' => EventAction::cases(),
            'events' => $this->events->search($filter, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'timezone' => $this->timezone,
        ]);
    }

    public function reports(Request $request): Response
    {
        [$from, $to] = $this->resolveRange($request->query);
        $report = $this->reports->daily($from, $to);

        $dates = array_column($report['rows'], 'date');
        $series = [];
        $legend = [];
        foreach (self::SERIES as $metric => [$label, $class, $marker]) {
            $series[] = ['label' => $label, 'values' => array_column($report['rows'], $metric), 'class' => $class, 'marker' => $marker];
            $legend[] = ['label' => $label, 'glyph' => LineChart::legendGlyph($marker, $class)];
        }

        $description = sprintf(
            'Daily counts from %s to %s: %d Page A views, %d Page B views, %d "Buy a cow" clicks, %d "Download" clicks.',
            $from->format('Y-m-d'),
            $to->format('Y-m-d'),
            $report['totals']['view_a'],
            $report['totals']['view_b'],
            $report['totals']['click_cow'],
            $report['totals']['click_download'],
        );

        $today = $this->today();

        return $this->render('admin/reports', [
            'title' => 'Reports',
            'from' => $from,
            'to' => $to,
            'rows' => array_reverse($report['rows']), // table: newest first
            'totals' => $report['totals'],
            'chart' => LineChart::render($dates, $series, 'Activity per day', $description),
            'legend' => $legend,
            'hasActivity' => array_sum($report['totals']) > 0,
            'presets' => array_map(
                static fn (int $days): array => [
                    'label' => 'Last ' . $days . ' days',
                    'from' => $today->modify('-' . ($days - 1) . ' days')->format('Y-m-d'),
                    'to' => $today->format('Y-m-d'),
                ],
                [7, 30, 90],
            ),
            'maxDays' => self::MAX_RANGE_DAYS,
        ]);
    }

    /**
     * Report date range from the query string; defaults to the last 30 days, never in the future,
     * never longer than MAX_RANGE_DAYS (the start moves forward if it would be).
     *
     * @param array<string, mixed> $query
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    private function resolveRange(array $query): array
    {
        $today = $this->today();

        $to = DateInput::parse($query['to'] ?? null) ?? $today;
        $to = min($to, $today);
        $from = DateInput::parse($query['from'] ?? null) ?? $to->modify('-' . (self::DEFAULT_RANGE_DAYS - 1) . ' days');
        if ($from > $to) {
            $from = $to;
        }

        $earliest = $to->modify('-' . (self::MAX_RANGE_DAYS - 1) . ' days');

        return [max($from, $earliest), $to];
    }

    private function today(): DateTimeImmutable
    {
        return new DateTimeImmutable('today');
    }

    private function intParam(mixed $value, int $default): int
    {
        return is_string($value) && ctype_digit($value) ? (int) $value : $default;
    }
}
