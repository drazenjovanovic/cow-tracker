<?php
/**
 * @var \App\Core\View $this
 * @var string $email
 * @var ?string $error
 */
?>
<section class="auth-card panel">
    <h1>Sign in</h1>

    <?php if ($error !== null): ?>
        <div class="alert alert-error" role="alert"><?= $this->e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/login" novalidate>
        <?= $this->csrfField() ?>

        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= $this->e($email) ?>" autocomplete="email" required autofocus>
        </div>

        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Sign in</button>
    </form>

    <p class="muted center"><a href="/forgot-password">Forgot your password?</a></p>
    <p class="muted center">No account yet? <a href="/register">Create one</a></p>
</section>
