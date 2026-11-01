<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;

/**
 * Server-side multi-series line chart rendered as inline SVG (no JavaScript, no external libraries).
 * Colors come from CSS classes (.series-1 ... .series-4), so the chart follows the page theme.
 */
final class LineChart
{
    private const WIDTH = 960;
    private const HEIGHT = 360;
    private const MARGIN_LEFT = 52;
    private const MARGIN_RIGHT = 16;
    private const MARGIN_TOP = 16;
    private const MARGIN_BOTTOM = 48;
    private const MAX_MARKED_POINTS = 31; // beyond this, markers just add clutter

    /**
     * @param list<string> $dates ISO dates (Y-m-d), ascending, one per x position
     * @param list<array{label: string, values: list<int>, class: string, marker: string}> $series
     */
    public static function render(array $dates, array $series, string $title, string $description): string
    {
        $count = count($dates);
        $max = 0;
        foreach ($series as $s) {
            $max = max($max, ...($s['values'] ?: [0]));
        }
        [$top, $step] = self::scale($max);

        $plotW = self::WIDTH - self::MARGIN_LEFT - self::MARGIN_RIGHT;
        $plotH = self::HEIGHT - self::MARGIN_TOP - self::MARGIN_BOTTOM;
        $baseY = self::MARGIN_TOP + $plotH;

        $x = static fn (int $i): float => $count > 1
            ? self::MARGIN_LEFT + $i * $plotW / ($count - 1)
            : self::MARGIN_LEFT + $plotW / 2;
        $y = static fn (int $v): float => $baseY - ($top > 0 ? $v / $top * $plotH : 0);

        $out = sprintf(
            '<svg class="chart" viewBox="0 0 %d %d" role="img" aria-labelledby="chart-title chart-desc" xmlns="http://www.w3.org/2000/svg">',
            self::WIDTH,
            self::HEIGHT,
        );
        $out .= '<title id="chart-title">' . self::e($title) . '</title>';
        $out .= '<desc id="chart-desc">' . self::e($description) . '</desc>';

        // Horizontal grid + y axis labels
        for ($tick = 0; $tick <= $top; $tick += $step) {
            $ty = round($y($tick), 1);
            $out .= sprintf(
                '<line class="grid%s" x1="%d" y1="%s" x2="%d" y2="%s"/>',
                $tick === 0 ? ' grid-base' : '',
                self::MARGIN_LEFT,
                $ty,
                self::WIDTH - self::MARGIN_RIGHT,
                $ty,
            );
            $out .= sprintf(
                '<text class="axis-label" x="%d" y="%s" text-anchor="end" dominant-baseline="middle">%d</text>',
                self::MARGIN_LEFT - 8,
                $ty,
                $tick,
            );
        }

        // X axis labels (thinned out so they never overlap)
        $labelEvery = max(1, (int) ceil($count / 10));
        foreach ($dates as $i => $date) {
            if ($i % $labelEvery !== 0) {
                continue;
            }
            $out .= sprintf(
                '<text class="axis-label" x="%s" y="%d" text-anchor="middle">%s</text>',
                round($x($i), 1),
                $baseY + 20,
                self::e((new DateTimeImmutable($date))->format('M j')),
            );
        }
        $out .= sprintf(
            '<text class="axis-title" x="%d" y="%d" text-anchor="middle">Date</text>',
            (int) (self::MARGIN_LEFT + $plotW / 2),
            self::HEIGHT - 6,
        );
        $out .= sprintf(
            '<text class="axis-title" transform="translate(14 %d) rotate(-90)" text-anchor="middle">Count</text>',
            (int) (self::MARGIN_TOP + $plotH / 2),
        );

        // Series
        foreach ($series as $s) {
            $points = [];
            foreach ($s['values'] as $i => $value) {
                $points[] = round($x($i), 1) . ',' . round($y($value), 1);
            }
            $out .= '<g class="series ' . self::e($s['class']) . '">';
            if ($count > 1) {
                $out .= '<polyline class="line" points="' . implode(' ', $points) . '"/>';
            }
            if ($count <= self::MAX_MARKED_POINTS) {
                foreach ($s['values'] as $i => $value) {
                    $out .= self::marker($s['marker'], round($x($i), 1), round($y($value), 1));
                }
            }
            $out .= '</g>';
        }

        // Invisible hover columns: one native tooltip per day listing every series.
        $columnW = $plotW / max($count, 1);
        foreach ($dates as $i => $date) {
            $lines = [(new DateTimeImmutable($date))->format('D, j M Y')];
            foreach ($series as $s) {
                $lines[] = $s['label'] . ': ' . $s['values'][$i];
            }
            $out .= sprintf(
                '<rect class="hit" x="%s" y="%d" width="%s" height="%d"><title>%s</title></rect>',
                round($x($i) - $columnW / 2, 1),
                self::MARGIN_TOP,
                round($columnW, 1),
                $plotH,
                self::e(implode("\n", $lines)),
            );
        }

        return $out . '</svg>';
    }

    /** Small standalone marker glyph for the HTML legend. */
    public static function legendGlyph(string $shape, string $class): string
    {
        return '<svg class="glyph series ' . self::e($class) . '" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true">'
            . '<line class="line" x1="0" y1="8" x2="16" y2="8"/>' . self::marker($shape, 8, 8) . '</svg>';
    }

    /** @return array{0: int, 1: int} [axis maximum, tick step] with whole-number ticks */
    private static function scale(int $max): array
    {
        if ($max <= 0) {
            return [4, 1];
        }

        $raw = $max / 5;
        $magnitude = 10 ** floor(log10($raw));
        $residual = $raw / $magnitude;
        $nice = match (true) {
            $residual <= 1 => 1,
            $residual <= 2 => 2,
            $residual <= 5 => 5,
            default => 10,
        };
        $step = max(1, (int) ceil($nice * $magnitude));

        return [(int) (ceil($max / $step) * $step), $step];
    }

    private static function marker(string $shape, float|int $cx, float|int $cy): string
    {
        return match ($shape) {
            'square' => sprintf('<rect class="marker" x="%s" y="%s" width="7" height="7"/>', $cx - 3.5, $cy - 3.5),
            'triangle' => sprintf(
                '<polygon class="marker" points="%s,%s %s,%s %s,%s"/>',
                $cx,
                $cy - 4.5,
                $cx + 4.5,
                $cy + 3.5,
                $cx - 4.5,
                $cy + 3.5,
            ),
            'diamond' => sprintf(
                '<polygon class="marker" points="%s,%s %s,%s %s,%s %s,%s"/>',
                $cx,
                $cy - 5,
                $cx + 5,
                $cy,
                $cx,
                $cy + 5,
                $cx - 5,
                $cy,
            ),
            default => sprintf('<circle class="marker" cx="%s" cy="%s" r="3.5"/>', $cx, $cy),
        };
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
