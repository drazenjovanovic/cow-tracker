<?php
/**
 * @var \App\Core\View $this
 * @var \App\Domain\EventFilter $filter
 * @var array<string, mixed> $filterQuery
 * @var list<array{id: int, name: string, email: string}> $users
 * @var list<\App\Domain\EventAction> $actions
 * @var list<array<string, mixed>> $events
 * @var int $total
 * @var int $page
 * @var int $pages
 * @var string $timezone
 */

use App\Domain\EventTarget;
?>
<h1>Statistics</h1>
<p class="muted">Every recorded event, newest first. Times are in <?= $this->e($timezone) ?>.</p>

<form class="panel filters" method="get" action="/admin/stats">
    <div class="field">
        <label for="date_from">From date</label>
        <input type="date" id="date_from" name="date_from" value="<?= $this->e($filter->dateFrom?->format('Y-m-d') ?? '') ?>">
    </div>

    <div class="field">
        <label for="date_to">To date</label>
        <input type="date" id="date_to" name="date_to" value="<?= $this->e($filter->dateTo?->format('Y-m-d') ?? '') ?>">
    </div>

    <div class="field">
        <label for="user_id">User</label>
        <select id="user_id" name="user_id">
            <option value="">All users</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>"<?= $filter->userId === $u['id'] ? ' selected' : '' ?>>
                    <?= $this->e($u['name']) ?> (<?= $this->e($u['email']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <fieldset class="field">
        <legend>Actions</legend>
        <div class="checks">
            <?php foreach ($actions as $action): ?>
                <label class="check">
                    <input type="checkbox" name="actions[]" value="<?= $this->e($action->value) ?>"<?= in_array($action, $filter->actions, true) ? ' checked' : '' ?>>
                    <?= $this->e($action->value) ?>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <div class="filter-buttons">
        <button type="submit" class="btn btn-primary">Apply filters</button>
        <a class="btn" href="/admin/stats">Reset</a>
    </div>
</form>

<p class="result-count"><strong><?= number_format($total) ?></strong> <?= $total === 1 ? 'event' : 'events' ?> found<?= $pages > 1 ? ' · page ' . $page . ' of ' . $pages : '' ?></p>

<div class="table-wrap">
    <table class="table">
        <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">Time</th>
            <th scope="col">User</th>
            <th scope="col">Action</th>
            <th scope="col">Details</th>
            <th scope="col">IP</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($events === []): ?>
            <tr><td colspan="6" class="empty">No events match these filters.</td></tr>
        <?php endif; ?>
        <?php foreach ($events as $event): ?>
            <tr>
                <td class="num"><?= (int) $event['id'] ?></td>
                <td class="nowrap"><?= $this->e($event['created_at']) ?></td>
                <td>
                    <?php if ($event['user_name'] !== null): ?>
                        <?= $this->e($event['user_name']) ?>
                        <span class="muted small"><?= $this->e($event['user_email']) ?></span>
                    <?php else: ?>
                        <span class="muted">—</span>
                    <?php endif; ?>
                </td>
                <td><span class="pill pill-<?= $this->e($event['action']) ?>"><?= $this->e($event['action']) ?></span></td>
                <td><?= $this->e($event['target'] !== null ? (EventTarget::tryFrom($event['target'])?->label() ?? $event['target']) : '—') ?></td>
                <td class="muted nowrap"><?= $this->e($event['ip'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= $this->partial('partials/pagination', [
    'page' => $page,
    'pages' => $pages,
    'baseUrl' => '/admin/stats',
    'query' => $filterQuery,
]) ?>
