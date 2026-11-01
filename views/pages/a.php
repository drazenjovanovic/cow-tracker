<?php
/**
 * @var \App\Core\View $this
 * @var bool $bought
 * @var string $thankYou
 */
?>
<h1>Page A</h1>
<p class="muted">Every cow is hand-picked.</p>

<section class="panel cow-box">
    <?php if ($bought): ?>
        <p class="thanks" id="cow-thanks" role="status"><?= $this->e($thankYou) ?></p>
    <?php else: ?>
        <form method="post" action="/page-a/buy" id="buy-cow-form">
            <?= $this->csrfField() ?>
            <button type="submit" class="btn btn-primary btn-large">Buy a cow</button>
        </form>
        <p class="thanks" id="cow-thanks" role="status" hidden><?= $this->e($thankYou) ?></p>
        <p class="field-error" id="cow-error" role="alert" hidden>Something went wrong. Please try again.</p>
    <?php endif; ?>
</section>
