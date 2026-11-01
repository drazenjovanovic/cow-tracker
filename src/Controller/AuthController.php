<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\SessionInterface;
use App\Core\Validator;
use App\Core\View;
use App\Domain\EventAction;
use App\Repository\EmailAlreadyExists;
use App\Repository\UserRepositoryInterface;
use App\Service\ActivityLogger;
use App\Service\Auth;
use App\Service\EmailVerificationService;
use App\Service\PasswordResetService;

final class AuthController extends Controller
{
    public function __construct(
        View $view,
        private readonly Auth $auth,
        private readonly SessionInterface $session,
        private readonly UserRepositoryInterface $users,
        private readonly ActivityLogger $logger,
        private readonly RateLimiter $limiter,
        private readonly PasswordResetService $passwordReset,
        private readonly EmailVerificationService $emailVerification,
    ) {
        parent::__construct($view);
    }

    public function showLogin(Request $request): Response
    {
        return $this->render('auth/login', ['title' => 'Sign in', 'email' => '', 'error' => null]);
    }

    public function login(Request $request): Response
    {
        $key = 'login:' . ($request->ip() ?? 'unknown');

        if ($this->limiter->tooManyAttempts($key)) {
            $seconds = $this->limiter->availableIn($key);
            return $this->render(
                'auth/login',
                ['title' => 'Sign in', 'email' => '', 'error' => "Too many login attempts. Try again in {$seconds} seconds."],
                429,
            );
        }

        $email    = strtolower(trim($request->postString('email')));
        $password = $request->postString('password');

        $user = ($email !== '' && $password !== '') ? $this->auth->attempt($email, $password) : null;
        if ($user === null) {
            $this->limiter->hit($key);
            return $this->render(
                'auth/login',
                ['title' => 'Sign in', 'email' => $email, 'error' => 'Invalid email or password.'],
                422,
            );
        }

        $this->limiter->clear($key);
        $this->auth->login($user);
        $this->logger->log($request, EventAction::Login, null, $user->id);

        return Response::redirect($this->intendedUrl());
    }

    public function showRegister(Request $request): Response
    {
        return $this->render('auth/register', ['title' => 'Create account', 'old' => ['name' => '', 'email' => ''], 'errors' => []]);
    }

    public function register(Request $request): Response
    {
        $key = 'register:' . ($request->ip() ?? 'unknown');
        if ($this->limiter->tooManyAttempts($key, 10)) {
            $seconds = $this->limiter->availableIn($key);
            return $this->render(
                'auth/register',
                ['title' => 'Create account', 'old' => ['name' => '', 'email' => ''], 'errors' => ['_' => "Too many registrations. Try again in {$seconds} seconds."]],
                429,
            );
        }
        $this->limiter->hit($key, 3600);

        $name  = trim($request->postString('name'));
        $email = strtolower(trim($request->postString('email')));

        $input = [
            'name'                  => $name,
            'email'                 => $email,
            'password'              => $request->postString('password'),
            'password_confirmation' => $request->postString('password_confirmation'),
        ];

        $errors = Validator::make($input, [
            'name'     => ['required', 'min:2', 'max:60'],
            'email'    => ['required', 'email', 'max:190'],
            'password' => ['required', 'min_bytes:8', 'max_bytes:72', 'confirmed'],
        ]);

        if ($errors === [] && $this->users->emailExists($email)) {
            $errors['email'] = 'This email is already registered.';
        }

        $user = null;
        if ($errors === []) {
            try {
                $user = $this->users->create($name, $email, password_hash($input['password'], PASSWORD_DEFAULT));
            } catch (EmailAlreadyExists) {
                $errors['email'] = 'This email is already registered.';
            }
        }

        if ($user === null) {
            return $this->render(
                'auth/register',
                ['title' => 'Create account', 'old' => ['name' => $name, 'email' => $email], 'errors' => $errors],
                422,
            );
        }

        $this->logger->log($request, EventAction::Registration, null, $user->id);
        $this->emailVerification->sendVerificationEmail($user);
        $this->session->addMessage('success', 'Account created. Please check your email to verify your address.');

        return Response::redirect('/login');
    }

    public function showVerifyEmail(Request $request): Response
    {
        return $this->render('auth/verify-email', ['title' => 'Verify your email']);
    }

    public function resendVerification(Request $request): Response
    {
        $user = $this->auth->user();
        $key  = 'verify-resend:' . ($user?->id ?? $request->ip() ?? 'unknown');

        if ($this->limiter->tooManyAttempts($key, 3)) {
            $seconds = $this->limiter->availableIn($key);
            $this->session->addMessage('error', "Too many requests. Try again in {$seconds} seconds.");
            return Response::redirect('/verify-email');
        }
        $this->limiter->hit($key, 3600);

        if ($user !== null && !$user->isVerified()) {
            $this->emailVerification->sendVerificationEmail($user);
        }
        $this->session->addMessage('success', 'Verification email sent. Please check your inbox.');

        return Response::redirect('/verify-email');
    }

    public function confirmEmail(Request $request): Response
    {
        $token = (string) ($request->query['token'] ?? '');

        if ($token === '' || $this->emailVerification->verify($token) === null) {
            $this->session->addMessage('error', 'This verification link is invalid or has expired.');
            return Response::redirect($this->auth->check() ? '/verify-email' : '/login');
        }

        $this->session->addMessage('success', 'Email verified. Welcome!');
        return Response::redirect($this->auth->check() ? '/' : '/login');
    }

    public function showForgotPassword(Request $request): Response
    {
        return $this->render('auth/forgot-password', ['title' => 'Reset password', 'error' => null]);
    }

    public function forgotPassword(Request $request): Response
    {
        $key = 'forgot-password:' . ($request->ip() ?? 'unknown');
        if ($this->limiter->tooManyAttempts($key)) {
            $seconds = $this->limiter->availableIn($key);
            return $this->render('auth/forgot-password', ['title' => 'Reset password', 'error' => "Too many attempts. Try again in {$seconds} seconds."], 429);
        }
        $this->limiter->hit($key);

        $email  = strtolower(trim($request->postString('email')));
        $errors = Validator::make(['email' => $email], [
            'email' => ['required', 'email'],
        ]);

        if ($errors !== []) {
            return $this->render('auth/forgot-password', ['title' => 'Reset password', 'error' => $errors['email']]);
        }

        $this->passwordReset->sendResetLink($email);
        $this->session->addMessage('success', 'If that email is registered you will receive a reset link shortly.');

        return Response::redirect('/login');
    }

    public function showResetPassword(Request $request): Response
    {
        $token = (string) ($request->query['token'] ?? '');

        if ($token === '' || $this->passwordReset->validateToken($token) === null) {
            $this->session->addMessage('error', 'This password reset link is invalid or has expired.');
            return Response::redirect('/forgot-password');
        }

        return $this->render('auth/reset-password', ['title' => 'Set new password', 'token' => $token, 'errors' => []]);
    }

    public function resetPassword(Request $request): Response
    {
        $token        = $request->postString('token');
        $password     = $request->postString('password');
        $confirmation = $request->postString('password_confirmation');

        $errors = Validator::make(
            ['password' => $password, 'password_confirmation' => $confirmation],
            ['password' => ['required', 'min_bytes:8', 'max_bytes:72', 'confirmed']],
        );

        if ($errors !== []) {
            return $this->render('auth/reset-password', ['title' => 'Set new password', 'token' => $token, 'errors' => $errors]);
        }

        if (!$this->passwordReset->resetPassword($token, $password)) {
            $this->session->addMessage('error', 'This password reset link is invalid or has expired.');
            return Response::redirect('/forgot-password');
        }

        $this->session->addMessage('success', 'Password updated. You can sign in with your new password.');
        return Response::redirect('/login');
    }

    public function logout(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user !== null) {
            $this->logger->log($request, EventAction::Logout, null, $user->id); // before the session is destroyed
        }

        $this->auth->logout();
        $this->session->addMessage('success', 'You have been signed out.');

        return Response::redirect('/login');
    }

    /** Where to send the user after login: the page they originally wanted, if it is a safe local path. */
    private function intendedUrl(): string
    {
        $intended = $this->session->pull('intended');

        return is_string($intended) && preg_match('#^/(?!/)[^\\\\\r\n]*$#', $intended) === 1 ? $intended : '/';
    }
}
