<?php

declare(strict_types=1);

namespace App;

use App\Core\Config\AppConfig;
use App\Core\HttpException;
use App\Core\LoggerInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;

final class Application
{
    private const CSP = "default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; "
        . "form-action 'self'; base-uri 'self'; frame-ancestors 'none'";

    public function __construct(
        private readonly AppConfig $config,
        private readonly Router $router,
        private readonly Session $session,
        private readonly View $view,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function run(): void
    {
        $request = Request::fromGlobals();
        $this->view->setCurrentPath($request->path);

        try {
            $this->session->start($request->isSecure());
            $response = $this->router->dispatch($request);
        } catch (HttpException $e) {
            $response = $this->errorResponse($request, $e->status, $e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error('Unhandled exception', [
                'exception' => $e::class,
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ]);
            $debug    = $this->config->debug;
            $response = $this->errorResponse($request, 500, $debug ? $e->getMessage() : '');
        }

        $this->withSecurityHeaders($response, $request)->send();
    }

    private function errorResponse(Request $request, int $status, string $message): Response
    {
        $headings = [
            401 => 'Sign in required',
            403 => 'Access denied',
            404 => 'Page not found',
            405 => 'Method not allowed',
            429 => 'Too many requests',
        ];
        $heading = $headings[$status] ?? 'Something went wrong';
        if ($message === '' && $status >= 500) {
            $message = 'An unexpected error occurred. Please try again later.';
        }

        if ($request->wantsJson()) {
            return Response::json(['ok' => false, 'error' => $message !== '' ? $message : $heading], $status);
        }

        try {
            return Response::html($this->view->render('errors/error', [
                'title'   => $heading,
                'status'  => $status,
                'heading' => $heading,
                'message' => $message,
            ]), $status);
        } catch (\Throwable) {
            return Response::html('<h1>' . htmlspecialchars($heading) . '</h1>', $status);
        }
    }

    private function withSecurityHeaders(Response $response, Request $request): Response
    {
        $response = $response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Referrer-Policy', 'same-origin')
            ->withHeader('Content-Security-Policy', self::CSP);

        if ($request->isSecure()) {
            $response = $response->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response->hasHeader('Cache-Control') ? $response : $response->withHeader('Cache-Control', 'no-store');
    }
}
