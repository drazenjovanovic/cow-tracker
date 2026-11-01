<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Core\HttpException;
use App\Core\LoggerInterface;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\LoggingMiddleware;
use PHPUnit\Framework\TestCase;
use Tests\Support\AuthBuilder;

final class LoggingMiddlewareTest extends TestCase
{
    private LoggerInterface $logger;
    private LoggingMiddleware $mw;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $auth = (new AuthBuilder())->build();
        $this->mw = new LoggingMiddleware($this->logger, $auth);
    }

    public function testLogsInfoForSuccessfulRequest(): void
    {
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('GET / 200'));

        $this->mw->handle(new Request('GET', '/'), fn ($r) => Response::html('ok'));
    }

    public function testLogsInfoForRedirect(): void
    {
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('POST /login 302'));

        $this->mw->handle(new Request('POST', '/login'), fn ($r) => Response::redirect('/'));
    }

    public function testLogsWarningFor4xx(): void
    {
        $this->logger->expects($this->once())->method('warning');

        try {
            $this->mw->handle(
                new Request('GET', '/missing'),
                fn ($r) => throw new HttpException(404, 'not found'),
            );
        } catch (HttpException) {
            // expected re-throw
        }
    }

    public function testLogsErrorFor5xx(): void
    {
        $this->logger->expects($this->once())->method('error');

        try {
            $this->mw->handle(
                new Request('GET', '/'),
                fn ($r) => throw new \RuntimeException('boom'),
            );
        } catch (\RuntimeException) {
            // expected re-throw
        }
    }

    public function testExceptionIsRethrown(): void
    {
        $this->expectException(HttpException::class);

        $this->mw->handle(
            new Request('GET', '/'),
            fn ($r) => throw new HttpException(403, 'forbidden'),
        );
    }

    public function testIncludesIpInContext(): void
    {
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->anything(), $this->arrayHasKey('ip'));

        $this->mw->handle(
            new Request('GET', '/', [], [], ['REMOTE_ADDR' => '1.2.3.4']),
            fn ($r) => Response::html('ok'),
        );
    }

    public function testIncludesUserIdForAuthenticatedUser(): void
    {
        $auth = (new AuthBuilder())->withVerifiedUser()->build();
        $mw   = new LoggingMiddleware($this->logger, $auth);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->anything(), $this->arrayHasKey('user_id'));

        $mw->handle(new Request('GET', '/'), fn ($r) => Response::html('ok'));
    }

    public function testMessageContainsMethodPathStatusAndDuration(): void
    {
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->matchesRegularExpression('/^GET \/page-a 200 \d+ms$/'));

        $this->mw->handle(new Request('GET', '/page-a'), fn ($r) => Response::html('ok'));
    }
}
