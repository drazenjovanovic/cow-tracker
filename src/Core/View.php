<?php

declare(strict_types=1);

namespace App\Core;

use App\Domain\User;
use App\Service\Auth;

/**
 * Renders plain PHP templates. Inside a template `$this` is this View, so templates can call
 * $this->e(), $this->csrfField(), $this->user(), $this->partial() ...
 */
final class View
{
    private string $currentPath = '/';

    public function __construct(
        private readonly string $viewsPath,
        private readonly Auth $auth,
        private readonly Csrf $csrf,
        private readonly SessionInterface $session,
        private readonly string $appName,
    ) {
    }

    public function setCurrentPath(string $path): void
    {
        $this->currentPath = $path;
    }

    /** Render a template wrapped in the main layout. */
    public function render(string $template, array $data = []): string
    {
        $content = $this->capture($template, $data);

        return $this->capture('layout', $data + ['content' => $content]);
    }

    /** Render a template without a layout. */
    public function partial(string $template, array $data = []): string
    {
        return $this->capture($template, $data);
    }

    public function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function appName(): string
    {
        return $this->appName;
    }

    public function user(): ?User
    {
        return $this->auth->user();
    }

    public function isActive(string $path): bool
    {
        return $this->currentPath === $path;
    }

    public function csrfToken(): string
    {
        return $this->csrf->token();
    }

    public function csrfField(): string
    {
        return '<input type="hidden" name="_csrf" value="' . $this->e($this->csrf->token()) . '">';
    }

    /** @return list<array{type: string, text: string}> */
    public function messages(): array
    {
        return $this->session->pullMessages();
    }

    /** @param array<string, mixed> $data */
    private function capture(string $template, array $data): string
    {
        $file = $this->viewsPath . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$template}");
        }

        $render = function (string $__file, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();
            try {
                require $__file;

                return (string) ob_get_clean();
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }
        };

        return $render->call($this, $file, $data);
    }
}
