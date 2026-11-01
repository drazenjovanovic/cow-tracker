<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Response;
use App\Core\View;

abstract class Controller
{
    public function __construct(protected readonly View $view)
    {
    }

    /** @param array<string, mixed> $data */
    protected function render(string $template, array $data = [], int $status = 200): Response
    {
        return Response::html($this->view->render($template, $data), $status);
    }
}
