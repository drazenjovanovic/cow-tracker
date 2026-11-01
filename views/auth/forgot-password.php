<?php
/**
 * @var \App\Core\View $this
 * @var ?string $error
 */
?>
<section class="auth-card panel">
    <h1>Reset password</h1>
    <p class="muted">Enter your email and we'll send you a reset link.</p>

    <?php if ($error !== null): ?>
        <div class="alert alert-error" role="alert"><?= $this->e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/forgot-password" novalidate>
        <?= $this->csrfField() ?>

        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" autocomplete="email" required autofocus>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Send reset link</button>
    </form>

    <p class="muted center"><a href="/login">Back to sign in</a></p>
</section>