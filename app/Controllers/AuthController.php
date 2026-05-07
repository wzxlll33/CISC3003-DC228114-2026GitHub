<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->authService = new AuthService($app);
    }

    public function loginForm(): void
    {
        if ($this->session->isLoggedIn()) {
            $this->redirect('/explore');
            return;
        }

        $this->renderAuthView('auth/login', 'Welcome Back');
    }

    public function login(): void
    {
        if (!$this->validateCsrf('/login')) {
            return;
        }

        $data = [
            'email' => trim((string) $this->request->post('email', '')),
            'password' => (string) $this->request->post('password', ''),
        ];

        $validation = $this->app->validator()->validate($data, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!$validation['valid']) {
            $this->flashFormState($validation['errors'], ['email' => $data['email']]);
            $this->redirect('/login');
            return;
        }

        $result = $this->authService->login($data['email'], $data['password']);

        if (!$result['success']) {
            $this->session->flash('error', $result['message']);
            $this->session->flash('old', ['email' => $data['email']]);
            $this->redirect('/login');
            return;
        }

        $this->session->flash('success', $result['message']);
        $this->redirect('/explore');
    }

    public function registerForm(): void
    {
        if ($this->session->isLoggedIn()) {
            $this->redirect('/explore');
            return;
        }

        $this->renderAuthView('auth/register', 'Create Your Account');
    }

    public function register(): void
    {
        if (!$this->validateCsrf('/register')) {
            return;
        }

        $data = [
            'username' => trim((string) $this->request->post('username', '')),
            'email' => strtolower(trim((string) $this->request->post('email', ''))),
            'password' => (string) $this->request->post('password', ''),
            'password_confirm' => (string) $this->request->post('password_confirm', ''),
        ];

        $validation = $this->app->validator()->validate($data, [
            'username' => 'required|min:3|max:50',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'password_confirm' => 'required|match:password',
        ]);

        if (!$validation['valid']) {
            $this->flashFormState($validation['errors'], [
                'username' => $data['username'],
                'email' => $data['email'],
            ]);
            $this->redirect('/register');
            return;
        }

        $result = $this->authService->register($data['username'], $data['email'], $data['password']);

        if (!$result['success']) {
            $this->session->flash('error', $result['message']);
            $this->session->flash('old', [
                'username' => $data['username'],
                'email' => $data['email'],
            ]);
            $this->redirect('/register');
            return;
        }

        $this->session->flash('success', $result['message']);
        $this->redirect('/login');
    }

    public function verify(): void
    {
        $token = trim((string) $this->request->get('token', ''));

        if ($token === '') {
            $this->session->flash('error', 'Missing verification token.');
            $this->redirect('/login');
            return;
        }

        $result = $this->authService->verifyEmail($token);
        $this->session->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/login');
    }

    public function forgotForm(): void
    {
        if ($this->session->isLoggedIn()) {
            $this->redirect('/explore');
            return;
        }

        $this->renderAuthView('auth/forgot-password', 'Reset Your Password');
    }

    public function forgot(): void
    {
        if (!$this->validateCsrf('/forgot-password')) {
            return;
        }

        $data = [
            'email' => strtolower(trim((string) $this->request->post('email', ''))),
        ];

        $validation = $this->app->validator()->validate($data, [
            'email' => 'required|email',
        ]);

        if (!$validation['valid']) {
            $this->flashFormState($validation['errors'], ['email' => $data['email']]);
            $this->redirect('/forgot-password');
            return;
        }

        $this->authService->requestPasswordReset($data['email']);
        $this->session->flash('success_key', 'auth.forgot.success');
        $this->redirect('/forgot-password');
    }

    public function resetForm(): void
    {
        $token = trim((string) $this->request->get('token', ''));

        if ($token === '') {
            $this->session->flash('error', 'Missing password reset token.');
            $this->redirect('/forgot-password');
            return;
        }

        $this->view('auth/reset-password', [
            'title' => 'Set New Password',
            'app' => $this->app,
            'locale' => $this->resolveLocale(),
            'csrfToken' => $this->csrf->getToken(),
            'bodyClass' => 'page-auth',
            'token' => $token,
        ]);
    }

    public function reset(): void
    {
        $token = trim((string) $this->request->post('token', ''));

        if (!$this->validateCsrf('/reset-password?token=' . urlencode($token))) {
            return;
        }

        $data = [
            'token' => $token,
            'password' => (string) $this->request->post('password', ''),
            'password_confirm' => (string) $this->request->post('password_confirm', ''),
        ];

        $validation = $this->app->validator()->validate($data, [
            'token' => 'required',
            'password' => 'required|min:8',
            'password_confirm' => 'required|match:password',
        ]);

        if (!$validation['valid']) {
            $this->flashFormState($validation['errors']);
            $this->redirect('/reset-password?token=' . urlencode($token));
            return;
        }

        $result = $this->authService->resetPassword($data['token'], $data['password']);
        $this->session->flash($result['success'] ? 'success' : 'error', $result['message']);

        if (!$result['success']) {
            $this->redirect('/reset-password?token=' . urlencode($token));
            return;
        }

        $this->redirect('/login');
    }

    public function logout(): void
    {
        if (!$this->validateCsrf('/')) {
            return;
        }

        $this->authService->logout();
        $this->session->start();
        $this->session->flash('success', 'You have been logged out.');
        $this->redirect('/');
    }

    protected function validateCsrf(string $redirectUrl): bool
    {
        $token = $this->request->post('_token') ?? $this->request->post('_csrf_token');

        if ($this->csrf->validateToken(is_string($token) ? $token : null)) {
            return true;
        }

        $this->session->flash('error', 'Your session has expired. Please try again.');
        $this->redirect($redirectUrl);

        return false;
    }

    protected function flashFormState(array $errors, array $old = []): void
    {
        $this->session->flash('errors', $errors);

        if ($old !== []) {
            $this->session->flash('old', $old);
        }
    }

    protected function renderAuthView(string $template, string $title, array $data = []): void
    {
        $this->view($template, array_merge([
            'title' => $title,
            'app' => $this->app,
            'locale' => $this->resolveLocale(),
            'csrfToken' => $this->csrf->getToken(),
            'bodyClass' => 'page-auth',
        ], $data));
    }

    protected function resolveLocale(): string
    {
        $locale = (string) $this->session->get('locale', $this->app->config('app.locale', 'zh'));

        return in_array($locale, ['en', 'zh', 'pt'], true) ? $locale : 'zh';
    }
}
