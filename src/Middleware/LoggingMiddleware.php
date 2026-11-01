<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\LoggerInterface;
use App\Core\Request;
use App\Core\Response;
use App\Service\Auth;

/**
 * Outermost middleware: logs one line per request with method, path, status, and duration.
 * 2xx/3xx → INFO, 4xx → WARNING, 5xx → ERROR.
 */
final class LoggingMiddleware implements Middleware
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Auth $auth,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $requestId = bin2hex(random_bytes(8));
        $this->logger->withContext(['request_id' => $requestId]);

        $start = hrtime(true);

        try {
            $response = $next($request);
        } catch (HttpException $e) {
            $this->log($request, $e->status, hrtime(true) - $start);
            throw $e;
        } catch (\Throwable $e) {
            $this->log($request, 500, hrtime(true) - $start);
            throw $e;
        }

        $this->log($request, $response->status, hrtime(true) - $start);

        return $response->withHeader('X-Request-ID', $requestId);
    }

    private function log(Request $request, int $status, int|float $nanoseconds): void
    {
        $ms     = (int) ($nanoseconds / 1_000_000);
        $userId = $this->auth->user()?->id;
        $level  = match (true) {
            $status >= 500 => 'error',
            $status >= 400 => 'warning',
            default        => 'info',
        };

        $context = array_filter(['ip' => $request->ip(), 'user_id' => $userId]);

        $this->logger->{$level}(
            sprintf('%s %s %d %dms', $request->method, $request->path, $status, $ms),
            $context,
        );
    }
}
