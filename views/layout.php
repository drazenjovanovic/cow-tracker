<?php
/**
 * @var \App\Core\View $this
 * @var string $content
 */
$title = $title ?? '';
$user = $this->user();
$navItems = [['/page-a', 'Page A'], ['/page-b', 'Page B']];
if ($user !== null && $user->isAdmin()) {
    $navItems[] = ['/admin/stats', 'Statistics'];
    $navItems[] = ['/admin/reports', 'Reports'];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= $this->e($this->csrfToken()) ?>">
    <title><?= $title !== '' ? $this->e($title) . ' · ' : '' ?><?= $this->e($this->appName()) ?></title>
    <link rel="stylesheet" href="/assets/style.css">
    <script src="/assets/app.js" defer></script>
</head>
<body>
<header class="site-header">
    <div class="container bar">
        <a class="brand" href="/">🐄 <?= $this->e($this->appName()) ?></a>

        <?php if ($user !== null): ?>
            <nav class="nav" aria-label="Main">
                <?php foreach ($navItems as [$href, $label]): ?>
                    <a href="<?= $this->e($href) ?>"<?= $this->isActive($href) ? ' aria-current="page"' : '' ?>><?= $this->e($label) ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="who">
                <span class="who-name"><?= $this->e($user->name) ?></span>
                <span class="badge badge-<?= $this->e($user->role->value) ?>"><?= $this->e($user->role->value) ?></span>
                <form method="post" action="/logout">
                    <?= $this->csrfField() ?>
                    <button type="submit" class="btn btn-small">Sign out</button>
                </form>
            </div>
        <?php else: ?>
            <nav class="nav" aria-label="Account">
                <a href="/login"<?= $this->isActive('/login') ? ' aria-current="page"' : '' ?>>Sign in</a>
                <a href="/register"<?= $this->isActive('/register') ? ' aria-current="page"' : '' ?>>Register</a>
            </nav>
        <?php endif; ?>
    </div>
</header>

<main class="container">
    <?php foreach ($this->messages() as $message): ?>
        <div class="alert alert-<?= $this->e($message['type']) ?>" role="status"><?= $this->e($message['text']) ?></div>
    <?php endforeach; ?>

    <?= $content ?>
</main>
</body>
</html>
