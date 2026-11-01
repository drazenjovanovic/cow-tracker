<?php
/**
 * @var \App\Core\View $this
 * @var int $page
 * @var int $pages
 * @var string $baseUrl
 * @var array<string, mixed> $query
 */
if ($pages <= 1) {
    return;
}

$link = static fn (int $p): string => $baseUrl . '?' . http_build_query($query + ['page' => $p]);

// Window of page numbers around the current one, always including first and last.
$numbers = array_unique([1, ...range(max(1, $page - 2), min($pages, $page + 2)), $pages]);
sort($numbers);
?>
<nav class="pagination" aria-label="Pagination">
    <?php if ($page > 1): ?>
        <a class="btn btn-small" href="<?= $this->e($link($page - 1)) ?>" rel="prev">&larr; Prev</a>
    <?php endif; ?>

    <?php $previous = 0; foreach ($numbers as $n): ?>
        <?php if ($n - $previous > 1): ?><span class="gap">…</span><?php endif; ?>
        <?php if ($n === $page): ?>
            <span class="btn btn-small btn-current" aria-current="page"><?= $n ?></span>
        <?php else: ?>
            <a class="btn btn-small" href="<?= $this->e($link($n)) ?>"><?= $n ?></a>
        <?php endif; ?>
        <?php $previous = $n; ?>
    <?php endforeach; ?>

    <?php if ($page < $pages): ?>
        <a class="btn btn-small" href="<?= $this->e($link($page + 1)) ?>" rel="next">Next &rarr;</a>
    <?php endif; ?>
</nav>
