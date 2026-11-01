<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;

/** Rejects every POST that doesn't carry a valid token (form field `_csrf` or `X-CSRF-Token` header). */
final class CsrfMiddleware implements Middleware
{
    public function __construct(private readonly Csrf $csrf)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        if ($request->method === 'POST') {
            $token = $request->post['_csrf'] ?? $request->header('X-CSRF-Token');

            if (!$this->csrf->isValid(is_string($token) ? $token : null)) {
                throw new HttpException(403, 'Your session expired or the form is no longer valid. Please reload the page and try again.');
            }
        }

        return $next($request);
    }
}
