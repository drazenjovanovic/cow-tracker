<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\VerifiedMiddleware;
use PHPUnit\Framework\TestCase;
use Tests\Support\AuthBuilder;

final class VerifiedMiddlewareTest extends TestCase
{
    private static function next(): callable
    {
        return static fn (Request $r): Response => Response::html('ok');
    }

    public function testVerifiedUserPassesThrough(): void
    {
        $auth = (new AuthBuilder())->withVerifiedUser()->build();
        $mw   = new VerifiedMiddleware($auth);

        $response = $mw->handle(new Request('GET', '/page-a'), self::next());

        $this->assertSame(200, $response->status);
    }

    public function testUnverifiedUserIsRedirectedToVerifyEmail(): void
    {
        $auth = (new AuthBuilder())->withUnverifiedUser()->build();
        $mw   = new VerifiedMiddleware($auth);

        $response = $mw->handle(new Request('GET', '/page-a'), self::next());

        $this->assertSame(302, $response->status);
        $this->assertTrue($response->hasHeader('Location'));
    }

    public function testUnauthenticatedUserPassesThrough(): void
    {
        // VerifiedMiddleware only acts on logged-in unverified users;
        // unauthenticated users are handled by AuthMiddleware earlier in the stack.
        $auth = (new AuthBuilder())->build();
        $mw   = new VerifiedMiddleware($auth);

        $response = $mw->handle(new Request('GET', '/page-a'), self::next());

        $this->assertSame(200, $response->status);
    }
}
