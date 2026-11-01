<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Service\Auth;

/** Must run after AuthMiddleware. */
final class AdminMiddleware implements Middleware
{
    public function __construct(private readonly Auth $auth)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        if ($this->auth->user()?->isAdmin() !== true) {
            throw new HttpException(403, 'This page is available to administrators only.');
        }

        return $next($request);
    }
}
