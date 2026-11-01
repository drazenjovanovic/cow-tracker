<?php

declare(strict_types=1);

use App\Controller\AdminController;
use App\Controller\AuthController;
use App\Controller\HealthController;
use App\Controller\PageController;
use App\Core\Router;

/**
 * @param list<object> $guest
 * @param list<object> $member
 * @param list<object> $verified
 * @param list<object> $adminOnly
 */
return static function (
    Router $router,
    AuthController $auth,
    PageController $page,
    AdminController $admin,
    HealthController $health,
    array $guest,
    array $member,
    array $verified,
    array $adminOnly,
): void {
    $router->get('/health', $health->check(...), []);

    $router->get('/', $page->home(...), $verified);

    $router->get('/login',           $auth->showLogin(...),         $guest);
    $router->post('/login',          $auth->login(...),             $guest);
    $router->get('/register',        $auth->showRegister(...),      $guest);
    $router->post('/register',       $auth->register(...),          $guest);
    $router->get('/forgot-password', $auth->showForgotPassword(...), $guest);
    $router->post('/forgot-password',$auth->forgotPassword(...),    $guest);
    $router->get('/reset-password',  $auth->showResetPassword(...), $guest);
    $router->post('/reset-password', $auth->resetPassword(...),     $guest);
    $router->post('/logout',         $auth->logout(...),            $member);

    $router->get('/verify-email',         $auth->showVerifyEmail(...),    $member);
    $router->post('/verify-email/resend', $auth->resendVerification(...), $member);
    $router->get('/verify-email/confirm', $auth->confirmEmail(...),       []);

    $router->get('/page-a',        $page->pageA(...),   $verified);
    $router->post('/page-a/buy',   $page->buyCow(...),  $verified);
    $router->get('/page-b',        $page->pageB(...),   $verified);
    $router->post('/page-b/download', $page->download(...), $verified);

    $router->get('/admin/stats',   $admin->stats(...),   $adminOnly);
    $router->get('/admin/reports', $admin->reports(...), $adminOnly);
};
