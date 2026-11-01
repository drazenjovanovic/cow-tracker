<?php
/**
 * @var \App\Core\View $this
 * @var int $status
 * @var string $heading
 * @var string $message
 */
?>
<section class="panel error-page">
    <p class="error-code"><?= (int) $status ?></p>
    <h1><?= $this->e($heading) ?></h1>
    <?php if ($message !== ''): ?><p><?= $this->e($message) ?></p><?php endif; ?>
    <p><a class="btn" href="/">Back to start</a></p>
</section>
