<?php
/**
 * @var \App\Core\View $this
 * @var string $token
 * @var array<string, string> $errors
 */
?>
<section class="auth-card panel">
    <h1>Set new password</h1>

    <form method="post" action="/reset-password" novalidate>
        <?= $this->csrfField() ?>
        <input type="hidden" name="token" value="<?= $this->e($token) ?>">

        <div class="field<?= isset($errors['password']) ? ' has-error' : '' ?>">
            <label for="password">New password</label>
            <input type="password" id="password" name="password" autocomplete="new-password" minlength="8" maxlength="72" required autofocus>
            <?php if (isset($errors['password'])): ?>
                <p class="field-error"><?= $this->e($errors['password']) ?></p>
            <?php else: ?>
                <p class="hint">8 to 72 characters.</p>
            <?php endif; ?>
        </div>

        <div class="field<?= isset($errors['password_confirmation']) ? ' has-error' : '' ?>">
            <label for="password_confirmation">Confirm new password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" maxlength="72" required>
            <?php if (isset($errors['password_confirmation'])): ?><p class="field-error"><?= $this->e($errors['password_confirmation']) ?></p><?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Set new password</button>
    </form>
</section>