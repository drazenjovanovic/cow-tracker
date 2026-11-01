<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Domain\Role;
use App\Middleware\AdminMiddleware;
use PHPUnit\Framework\TestCase;
use Tests\Support\AuthBuilder;

final class AdminMiddlewareTest extends TestCase
{
    private static function next(): callable
    {
        return static fn (Request $r): Response => Response::html('ok');
    }

    public function testAdminPassesThrough(): void
    {
        $auth = (new AuthBuilder())->withVerifiedUser(Role::Admin)->build();
        $mw   = new AdminMiddleware($auth);

        $response = $mw->handle(new Request('GET', '/admin/stats'), self::next());

        $this->assertSame(200, $response->status);
    }

    public function testRegularUserThrows403(): void
    {
        $this->expectException(HttpException::class);

        $auth = (new AuthBuilder())->withVerifiedUser(Role::User)->build();
        $mw   = new AdminMiddleware($auth);

        $mw->handle(new Request('GET', '/admin/stats'), self::next());
    }

    public function testUnauthenticatedThrows403(): void
    {
        $this->expectException(HttpException::class);

        $auth = (new AuthBuilder())->build();
        $mw   = new AdminMiddleware($auth);

        $mw->handle(new Request('GET', '/admin/stats'), self::next());
    }
}
