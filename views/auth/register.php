<?php
/**
 * @var \App\Core\View $this
 * @var array{name: string, email: string} $old
 * @var array<string, string> $errors
 */
?>
<section class="auth-card panel">
    <h1>Create account</h1>

    <form method="post" action="/register" novalidate>
        <?= $this->csrfField() ?>

        <div class="field<?= isset($errors['name']) ? ' has-error' : '' ?>">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="<?= $this->e($old['name']) ?>" autocomplete="name" maxlength="60" required autofocus>
            <?php if (isset($errors['name'])): ?><p class="field-error"><?= $this->e($errors['name']) ?></p><?php endif; ?>
        </div>

        <div class="field<?= isset($errors['email']) ? ' has-error' : '' ?>">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= $this->e($old['email']) ?>" autocomplete="email" maxlength="190" required>
            <?php if (isset($errors['email'])): ?><p class="field-error"><?= $this->e($errors['email']) ?></p><?php endif; ?>
        </div>

        <div class="field<?= isset($errors['password']) ? ' has-error' : '' ?>">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="new-password" minlength="8" maxlength="72" required>
            <?php if (isset($errors['password'])): ?>
                <p class="field-error"><?= $this->e($errors['password']) ?></p>
            <?php else: ?>
                <p class="hint">8 to 72 characters.</p>
            <?php endif; ?>
        </div>

        <div class="field<?= isset($errors['password_confirmation']) ? ' has-error' : '' ?>">
            <label for="password_confirmation">Confirm password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" maxlength="72" required>
            <?php if (isset($errors['password_confirmation'])): ?><p class="field-error"><?= $this->e($errors['password_confirmation']) ?></p><?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Create account</button>
    </form>

    <p class="muted center">Already registered? <a href="/login">Sign in</a></p>
</section>
