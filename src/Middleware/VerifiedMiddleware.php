<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Service\Auth;

final class VerifiedMiddleware implements Middleware
{
    public function __construct(private readonly Auth $auth)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $user = $this->auth->user();
        if ($user !== null && !$user->isVerified()) {
            return Response::redirect('/verify-email');
        }

        return $next($request);
    }
}
