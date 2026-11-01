<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Core\Csrf;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\CsrfMiddleware;
use PHPUnit\Framework\TestCase;
use Tests\Support\ArraySession;

final class CsrfMiddlewareTest extends TestCase
{
    private function makeMiddleware(): array
    {
        $session = new ArraySession();
        $csrf    = new Csrf($session);

        return [$csrf, new CsrfMiddleware($csrf)];
    }

    private static function next(): callable
    {
        return static fn (Request $r): Response => Response::html('ok');
    }

    public function testGetRequestPassesThrough(): void
    {
        [, $mw] = $this->makeMiddleware();
        $r = new Request('GET', '/');

        $response = $mw->handle($r, self::next());

        $this->assertSame(200, $response->status);
    }

    public function testPostWithValidFormFieldPasses(): void
    {
        [$csrf, $mw] = $this->makeMiddleware();
        $token = $csrf->token();
        $r = new Request('POST', '/submit', [], ['_csrf' => $token]);

        $response = $mw->handle($r, self::next());

        $this->assertSame(200, $response->status);
    }

    public function testPostWithValidHeaderPasses(): void
    {
        [$csrf, $mw] = $this->makeMiddleware();
        $token = $csrf->token();
        $r = new Request('POST', '/submit', [], [], ['HTTP_X_CSRF_TOKEN' => $token]);

        $response = $mw->handle($r, self::next());

        $this->assertSame(200, $response->status);
    }

    public function testPostWithoutTokenThrows403(): void
    {
        [, $mw] = $this->makeMiddleware();
        $r = new Request('POST', '/submit', [], []);

        $this->expectException(HttpException::class);
        $mw->handle($r, self::next());
    }

    public function testPostWithWrongTokenThrows403(): void
    {
        [, $mw] = $this->makeMiddleware();
        $r = new Request('POST', '/submit', [], ['_csrf' => 'bad-token']);

        $this->expectException(HttpException::class);
        $mw->handle($r, self::next());
    }
}
