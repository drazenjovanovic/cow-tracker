<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\GuestMiddleware;
use PHPUnit\Framework\TestCase;
use Tests\Support\AuthBuilder;

final class GuestMiddlewareTest extends TestCase
{
    private static function next(): callable
    {
        return static fn (Request $r): Response => Response::html('ok');
    }

    public function testGuestPassesThrough(): void
    {
        $auth = (new AuthBuilder())->build();
        $mw   = new GuestMiddleware($auth);

        $response = $mw->handle(new Request('GET', '/login'), self::next());

        $this->assertSame(200, $response->status);
    }

    public function testSignedInUserIsRedirectedToHome(): void
    {
        $auth = (new AuthBuilder())->withVerifiedUser()->build();
        $mw   = new GuestMiddleware($auth);

        $response = $mw->handle(new Request('GET', '/login'), self::next());

        $this->assertSame(302, $response->status);
        $this->assertTrue($response->hasHeader('Location'));
    }
}
