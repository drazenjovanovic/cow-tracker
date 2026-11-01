<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\SessionInterface;
use App\Service\Auth;

final class AuthMiddleware implements Middleware
{
    public function __construct(
        private readonly Auth $auth,
        private readonly SessionInterface $session,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        if ($this->auth->check()) {
            return $next($request);
        }

        if ($request->wantsJson()) {
            throw new HttpException(401, 'Please sign in again.');
        }

        if ($request->method === 'GET') {
            // Server-side value (our own path), so no open-redirect risk when we send the user back.
            $this->session->set('intended', $request->path);
        }
        $this->session->addMessage('info', 'Please sign in to continue.');

        return Response::redirect('/login');
    }
}
