<?php
/**
 * @var \App\Core\View $this
 */
$user = $this->user();
?>
<section class="auth-card panel">
    <h1>Verify your email</h1>

    <p>We sent a verification link to <strong><?= $this->e($user?->email ?? '') ?></strong>.</p>
    <p class="muted">Check your inbox and click the link to activate your account. The link expires in 24 hours.</p>

    <form method="post" action="/verify-email/resend">
        <?= $this->csrfField() ?>
        <button type="submit" class="btn btn-primary btn-block">Resend verification email</button>
    </form>

    <form method="post" action="/logout" style="margin-top:0.5rem">
        <?= $this->csrfField() ?>
        <button type="submit" class="btn btn-block">Sign out</button>
    </form>
</section>
