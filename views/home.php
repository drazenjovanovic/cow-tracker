<?php
/** @var \App\Core\View $this */
$user = $this->user();
?>
<h1>Welcome, <?= $this->e($user?->name ?? '') ?></h1>
<p class="muted">Pick a page. Your visits and clicks are recorded.</p>

<div class="cards">
    <a class="card" href="/page-a">
        <h2>Page A</h2>
        <p>Buy a cow.</p>
    </a>
    <a class="card" href="/page-b">
        <h2>Page B</h2>
        <p>Download the installer.</p>
    </a>
    <?php if ($user !== null && $user->isAdmin()): ?>
        <a class="card card-admin" href="/admin/stats">
            <h2>Statistics</h2>
            <p>Browse and filter every recorded event.</p>
        </a>
        <a class="card card-admin" href="/admin/reports">
            <h2>Reports</h2>
            <p>Daily page views and clicks, as a graph and a table.</p>
        </a>
    <?php endif; ?>
</div>
