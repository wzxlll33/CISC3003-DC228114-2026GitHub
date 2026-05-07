<?php

namespace App\Services;

use App\Core\App;
use App\Repositories\UserRepository;
use Throwable;

class AuthService
{
    protected App $app;

    protected UserRepository $users;

    protected EmailService $emailService;

    public function __construct(App $app, ?UserRepository $users = null, ?EmailService $emailService = null)
    {
        $this->app = $app;
        $this->users = $users ?? new UserRepository($app->db());
        $this->emailService = $emailService ?? new EmailService($app);
    }

    public function register(string $username, string $email, string $password): array
    {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $pdo = $this->app->db()->pdo();

        try {
            $pdo->beginTransaction();

            $userId = $this->users->create([
                'username' => trim($username),
                'email' => strtolower(trim($email)),
                'password_hash' => $passwordHash,
                'is_verified' => 0,
                'locale' => (string) $this->app->config('app.locale', 'zh'),
            ]);

            $this->users->storeVerificationToken($userId, $tokenHash, $expiresAt);
            $this->emailService->sendVerificationEmail(strtolower(trim($email)), trim($username), $token);
            $pdo->commit();

            return [
                'success' => true,
                'message' => 'Registration successful. Please check your email to verify your account before signing in.',
            ];
        } catch (Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return [
                'success' => false,
                'message' => 'Unable to create your account right now. Please try again.',
            ];
        }
    }

    public function login(string $email, string $password): array
    {
        $user = $this->users->findByEmail(strtolower(trim($email)));

        if ($user === false || !password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            return [
                'success' => false,
                'message' => 'Invalid email or password.',
            ];
        }

        if ((int) ($user['is_verified'] ?? 0) !== 1) {
            return [
                'success' => false,
                'message' => 'Please verify your email address before signing in.',
            ];
        }

        $session = $this->app->session();
        $session->regenerate();
        $session->set('user_id', (int) $user['id']);
        $session->set('username', (string) $user['username']);
        $session->set('locale', in_array((string) ($user['locale'] ?? 'zh'), ['zh', 'en', 'pt'], true) ? (string) $user['locale'] : 'zh');

        return [
            'success' => true,
            'message' => 'Welcome back, ' . $user['username'] . '!',
            'user' => $user,
        ];
    }

    public function logout(): void
    {
        $this->app->session()->destroy();
    }

    public function verifyEmail(string $token): array
    {
        $tokenHash = hash('sha256', $token);
        $record = $this->users->findVerificationToken($tokenHash);

        if ($record === false) {
            return [
                'success' => false,
                'message' => 'This verification link is invalid or has expired.',
            ];
        }

        $pdo = $this->app->db()->pdo();

        try {
            $pdo->beginTransaction();
            $this->users->verifyEmail((int) $record['user_id']);
            $this->users->deleteVerificationTokens((int) $record['user_id']);
            $pdo->commit();

            return [
                'success' => true,
                'message' => 'Your email address has been verified. You can now log in.',
            ];
        } catch (Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return [
                'success' => false,
                'message' => 'We could not verify your email right now. Please try again later.',
            ];
        }
    }

    public function requestPasswordReset(string $email): bool
    {
        $user = $this->users->findByEmail(strtolower(trim($email)));

        if ($user === false) {
            return true;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $pdo = $this->app->db()->pdo();

        try {
            $pdo->beginTransaction();
            $this->users->deleteResetTokens((int) $user['id']);
            $this->users->storeResetToken((int) $user['id'], $tokenHash, $expiresAt);
            $pdo->commit();

            $this->emailService->sendPasswordResetEmail((string) $user['email'], (string) $user['username'], $token);
        } catch (Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }

        return true;
    }

    public function resetPassword(string $token, string $newPassword): array
    {
        $tokenHash = hash('sha256', $token);
        $record = $this->users->findResetToken($tokenHash);

        if ($record === false) {
            return [
                'success' => false,
                'message' => 'This password reset link is invalid or has expired.',
            ];
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $pdo = $this->app->db()->pdo();

        try {
            $pdo->beginTransaction();
            $this->users->updatePassword((int) $record['user_id'], $passwordHash);
            $this->users->deleteResetTokens((int) $record['user_id']);
            $pdo->commit();

            return [
                'success' => true,
                'message' => 'Your password has been reset successfully. Please log in.',
            ];
        } catch (Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return [
                'success' => false,
                'message' => 'We could not reset your password right now. Please try again later.',
            ];
        }
    }
}
