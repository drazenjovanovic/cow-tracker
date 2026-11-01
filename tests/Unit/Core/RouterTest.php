<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Middleware\Middleware;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private static function ok(): Response
    {
        return Response::html('ok');
    }

    public function testDispatchesMatchingGetRoute(): void
    {
        $router = new Router();
        $router->get('/hello', fn () => self::ok());

        $response = $router->dispatch(new Request('GET', '/hello'));

        $this->assertSame(200, $response->status);
    }

    public function testDispatchesMatchingPostRoute(): void
    {
        $router = new Router();
        $router->post('/submit', fn () => self::ok());

        $response = $router->dispatch(new Request('POST', '/submit'));

        $this->assertSame(200, $response->status);
    }

    public function testUnknownPathThrows404(): void
    {
        $this->expectException(HttpException::class);

        $router = new Router();
        $router->dispatch(new Request('GET', '/missing'));
    }

    public function testWrongMethodThrows405(): void
    {
        $this->expectException(HttpException::class);

        $router = new Router();
        $router->get('/page', fn () => self::ok());
        $router->dispatch(new Request('POST', '/page'));
    }

    public function testGlobalMiddlewareRunsForEveryRoute(): void
    {
        $ran = false;
        $mw  = new class ($ran) implements Middleware {
            public function __construct(private bool &$ran) {}

            public function handle(Request $request, callable $next): Response
            {
                $this->ran = true;

                return $next($request);
            }
        };

        $router = new Router([$mw]);
        $router->get('/x', fn () => self::ok());
        $router->dispatch(new Request('GET', '/x'));

        $this->assertTrue($ran);
    }

    public function testRouteMiddlewareRunsAfterGlobal(): void
    {
        $order = [];
        $global = new class ($order, 'global') implements Middleware {
            public function __construct(private array &$order, private string $name) {}

            public function handle(Request $request, callable $next): Response
            {
                $this->order[] = $this->name;

                return $next($request);
            }
        };
        $route = new class ($order, 'route') implements Middleware {
            public function __construct(private array &$order, private string $name) {}

            public function handle(Request $request, callable $next): Response
            {
                $this->order[] = $this->name;

                return $next($request);
            }
        };

        $router = new Router([$global]);
        $router->get('/y', fn () => self::ok(), [$route]);
        $router->dispatch(new Request('GET', '/y'));

        $this->assertSame(['global', 'route'], $order);
    }

    public function testMiddlewareCanShortCircuit(): void
    {
        $blocked = new class implements Middleware {
            public function handle(Request $request, callable $next): Response
            {
                return Response::redirect('/login');
            }
        };

        $reached = false;
        $router  = new Router();
        $router->get('/protected', function () use (&$reached) {
            $reached = true;

            return self::ok();
        }, [$blocked]);

        $response = $router->dispatch(new Request('GET', '/protected'));

        $this->assertFalse($reached);
        $this->assertSame(302, $response->status);
    }
}
