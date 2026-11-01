<?php

declare(strict_types=1);

namespace App;

use App\Controller\AdminController;
use App\Controller\AuthController;
use App\Controller\HealthController;
use App\Controller\PageController;
use App\Core\Config;
use App\Core\Config\AppConfig;
use App\Core\Config\DatabaseConfig;
use App\Core\Config\LogConfig;
use App\Core\Config\MailConfig;
use App\Core\Config\RateLimitConfig;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Mailer;
use App\Core\QueueMailer;
use App\Core\RateLimiter;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\LoggingMiddleware;
use App\Middleware\VerifiedMiddleware;
use App\Repository\EmailVerificationRepository;
use App\Repository\EventRepository;
use App\Repository\PasswordResetRepository;
use App\Repository\UserRepository;
use App\Service\ActivityLogger;
use App\Service\Auth;
use App\Service\EmailVerificationService;
use App\Service\PasswordResetService;
use App\Service\ReportService;

/** Builds the object graph and returns a ready-to-run Application. */
final class AppFactory
{
    public static function create(string $basePath): Application
    {
        $config    = Config::load($basePath);
        $appConfig = AppConfig::fromConfig($config);

        date_default_timezone_set($appConfig->timezone);

        $logger  = new Logger(LogConfig::fromConfig($config, $basePath));
        $db      = new Database(DatabaseConfig::fromConfig($config), $logger);
        $session = new Session($config->string('session.path'), $config->string('session.name', 'cow_session'));
        $csrf    = new Csrf($session);

        $users   = new UserRepository($db);
        $events  = new EventRepository($db);
        $auth    = new Auth($session, $users);
        $reports = new ReportService($events);
        $view    = new View($basePath . '/views', $auth, $csrf, $session, $appConfig->name);
        $limiter = new RateLimiter($db, RateLimitConfig::fromConfig($config));

        $mailConfig = MailConfig::fromConfig($config);
        $mailer     = $mailConfig->async ? new QueueMailer($db) : new Mailer($mailConfig);

        $activityLogger    = new ActivityLogger($events, $auth, $logger);
        $passwordReset     = new PasswordResetService(new PasswordResetRepository($db), $users, $mailer, $appConfig->url);
        $emailVerification = new EmailVerificationService(new EmailVerificationRepository($db), $users, $mailer, $appConfig->url);

        $authController   = new AuthController($view, $auth, $session, $users, $activityLogger, $limiter, $passwordReset, $emailVerification);
        $pageController   = new PageController(
            $view, $session, $activityLogger,
            $config->string('messages.thank_you', 'thank you'),
            $config->string('download.file'),
            $config->string('download.name', 'download.exe'),
        );
        $adminController  = new AdminController($view, $events, $users, $reports, $appConfig->timezone);
        $healthController = new HealthController($db);

        $guest     = [new GuestMiddleware($auth)];
        $member    = [new AuthMiddleware($auth, $session)];
        $verified  = [new AuthMiddleware($auth, $session), new VerifiedMiddleware($auth)];
        $adminOnly = [new AuthMiddleware($auth, $session), new VerifiedMiddleware($auth), new AdminMiddleware($auth)];

        $router = new Router([new LoggingMiddleware($logger, $auth), new CsrfMiddleware($csrf)]);

        $registerRoutes = require $basePath . '/config/routes.php';
        $registerRoutes($router, $authController, $pageController, $adminController, $healthController, $guest, $member, $verified, $adminOnly);

        return new Application($appConfig, $router, $session, $view, $logger);
    }
}
