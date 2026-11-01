<?php
/**
 * @var \App\Core\View $this
 * @var DateTimeImmutable $from
 * @var DateTimeImmutable $to
 * @var list<array{date: string, view_a: int, view_b: int, click_cow: int, click_download: int}> $rows
 * @var array{view_a: int, view_b: int, click_cow: int, click_download: int} $totals
 * @var string $chart       Trusted, pre-escaped SVG markup from LineChart
 * @var list<array{label: string, glyph: string}> $legend
 * @var bool $hasActivity
 * @var list<array{label: string, from: string, to: string}> $presets
 * @var int $maxDays
 */
?>
<h1>Reports</h1>
<p class="muted">Page views and button clicks per day.</p>

<form class="panel filters" method="get" action="/admin/reports">
    <div class="field">
        <label for="from">From</label>
        <input type="date" id="from" name="from" value="<?= $this->e($from->format('Y-m-d')) ?>">
    </div>
    <div class="field">
        <label for="to">To</label>
        <input type="date" id="to" name="to" value="<?= $this->e($to->format('Y-m-d')) ?>" max="<?= $this->e((new DateTimeImmutable('today'))->format('Y-m-d')) ?>">
    </div>
    <div class="filter-buttons">
        <button type="submit" class="btn btn-primary">Show report</button>
        <?php foreach ($presets as $preset): ?>
            <a class="btn" href="/admin/reports?<?= $this->e(http_build_query(['from' => $preset['from'], 'to' => $preset['to']])) ?>"><?= $this->e($preset['label']) ?></a>
        <?php endforeach; ?>
    </div>
    <p class="hint">Ranges are limited to <?= (int) $maxDays ?> days.</p>
</form>

<section class="panel" aria-labelledby="graph-heading">
    <h2 id="graph-heading">Graph</h2>

    <ul class="legend">
        <?php foreach ($legend as $item): ?>
            <li><?= $item['glyph'] ?> <?= $this->e($item['label']) ?></li>
        <?php endforeach; ?>
    </ul>

    <?= $chart ?>

    <?php if (!$hasActivity): ?>
        <p class="muted center">No activity recorded in this period.</p>
    <?php endif; ?>
</section>

<section class="panel" aria-labelledby="table-heading">
    <h2 id="table-heading">Table</h2>

    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr>
                <th scope="col">Date</th>
                <th scope="col" class="num">Page A views</th>
                <th scope="col" class="num">Page B views</th>
                <th scope="col" class="num">Clicks “Buy a cow”</th>
                <th scope="col" class="num">Clicks “Download”</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td class="nowrap"><?= $this->e($row['date']) ?></td>
                    <td class="num"><?= $row['view_a'] ?></td>
                    <td class="num"><?= $row['view_b'] ?></td>
                    <td class="num"><?= $row['click_cow'] ?></td>
                    <td class="num"><?= $row['click_download'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
            <tr>
                <th scope="row">Total</th>
                <td class="num"><?= $totals['view_a'] ?></td>
                <td class="num"><?= $totals['view_b'] ?></td>
                <td class="num"><?= $totals['click_cow'] ?></td>
                <td class="num"><?= $totals['click_download'] ?></td>
            </tr>
            </tfoot>
        </table>
    </div>
</section>
