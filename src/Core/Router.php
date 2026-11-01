<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\Middleware;

final class Router
{
    /** @var array<string, array<string, array{handler: callable, middleware: list<Middleware>}>> */
    private array $routes = [];

    /** @param list<Middleware> $globalMiddleware Runs for every matched route, before route middleware. */
    public function __construct(private readonly array $globalMiddleware = [])
    {
    }

    /** @param list<Middleware> $middleware */
    public function get(string $path, callable $handler, array $middleware = []): void
    {
        $this->routes['GET'][$path] = ['handler' => $handler, 'middleware' => $middleware];
    }

    /** @param list<Middleware> $middleware */
    public function post(string $path, callable $handler, array $middleware = []): void
    {
        $this->routes['POST'][$path] = ['handler' => $handler, 'middleware' => $middleware];
    }

    public function dispatch(Request $request): Response
    {
        $route = $this->routes[$request->method][$request->path] ?? null;

        if ($route === null) {
            foreach ($this->routes as $method => $paths) {
                if ($method !== $request->method && isset($paths[$request->path])) {
                    throw new HttpException(405, 'This URL does not accept ' . $request->method . ' requests.');
                }
            }
            throw new HttpException(404, 'The page you are looking for does not exist.');
        }

        $pipeline = static fn (Request $r): Response => ($route['handler'])($r);
        foreach (array_reverse([...$this->globalMiddleware, ...$route['middleware']]) as $middleware) {
            $next = $pipeline;
            $pipeline = static fn (Request $r): Response => $middleware->handle($r, $next);
        }

        return $pipeline($request);
    }
}
