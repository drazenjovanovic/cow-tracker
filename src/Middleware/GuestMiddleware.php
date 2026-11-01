<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Service\Auth;

/** Keeps signed-in users away from the login and registration forms. */
final class GuestMiddleware implements Middleware
{
    public function __construct(private readonly Auth $auth)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        return $this->auth->check() ? Response::redirect('/') : $next($request);
    }
}
