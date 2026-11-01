<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\AuthMiddleware;
use PHPUnit\Framework\TestCase;
use Tests\Support\ArraySession;
use Tests\Support\AuthBuilder;

final class AuthMiddlewareTest extends TestCase
{
    private static function next(): callable
    {
        return static fn (Request $r): Response => Response::html('ok');
    }

    public function testAuthenticatedUserPassesThrough(): void
    {
        $builder = (new AuthBuilder())->withVerifiedUser();
        $mw = new AuthMiddleware($builder->build(), $builder->session());

        $response = $mw->handle(new Request('GET', '/'), self::next());

        $this->assertSame(200, $response->status);
    }

    public function testUnauthenticatedGetRedirectsToLogin(): void
    {
        $session = new ArraySession();
        $auth    = (new AuthBuilder())->build();
        $mw      = new AuthMiddleware($auth, $session);

        $response = $mw->handle(new Request('GET', '/dashboard'), self::next());

        $this->assertSame(302, $response->status);
        $this->assertTrue($response->hasHeader('Location'));
    }

    public function testUnauthenticatedGetStoresIntendedPath(): void
    {
        $builder = new AuthBuilder();
        $mw      = new AuthMiddleware($builder->build(), $builder->session());

        $mw->handle(new Request('GET', '/dashboard'), self::next());

        $this->assertSame('/dashboard', $builder->session()->get('intended'));
    }

    public function testUnauthenticatedJsonRequestThrows401(): void
    {
        $this->expectException(HttpException::class);

        $builder = new AuthBuilder();
        $mw      = new AuthMiddleware($builder->build(), $builder->session());

        $mw->handle(
            new Request('GET', '/', [], [], ['HTTP_ACCEPT' => 'application/json']),
            self::next(),
        );
    }
}
