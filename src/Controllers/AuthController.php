<?php

declare(strict_types=1);

namespace KazSign\Controllers;

use KazSign\Core\Controller;
use KazSign\Core\Database;
use KazSign\Core\KazSignEngine;

/**
 * AuthController — registration, login, logout, and private-key handoff.
 */
final class AuthController extends Controller
{
    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    /** GET /register */
    public function registerForm(array $_params = []): void
    {
        if ($this->authUserId()) {
            $this->redirect('/');
        }

        $this->render('auth.register', [
            'csrf_token' => $this->generateCsrfToken(),
            'flash'      => $this->consumeFlash(),
        ]);
    }

    /** POST /register */
    public function register(array $_params = []): void
    {
        if ($this->authUserId()) {
            $this->redirect('/');
        }

        $this->validateCsrf();

        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password =       $_POST['password'] ?? '';

        if ($username === '' || $email === '' || $password === '') {
            $this->setFlash('error', 'All fields are required.');
            $this->redirect('/register');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->setFlash('error', 'Invalid email address.');
            $this->redirect('/register');
        }

        if (\strlen($password) < 8) {
            $this->setFlash('error', 'Password must be at least 8 characters.');
            $this->redirect('/register');
        }

        // ── Generate KAZ-SIGN key pair ────────────────────────────────────────
        $engine  = new KazSignEngine();
        $keyPair = $engine->generateKeyPair();

        // ── Persist user ──────────────────────────────────────────────────────
        $db = Database::getInstance();

        try {
            $stmt = $db->prepare(
                'INSERT INTO users (username, email, password, public_key)
                 VALUES (:username, :email, :password, :public_key)'
            );
            $stmt->execute([
                ':username'   => $username,
                ':email'      => $email,
                ':password'   => password_hash($password, PASSWORD_BCRYPT),
                ':public_key' => $keyPair['public_key'],
            ]);

            // Works in both real MySQL and stub mode
            $userId = $db->lastInsertId();

        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), '1062')) {
                $this->setFlash('error', 'Username or email is already taken.');
            } else {
                error_log('[KazSign] Register error: ' . $e->getMessage());
                $this->setFlash('error', 'Registration failed. Please try again.');
            }
            $this->redirect('/register');
        }

        // ── Start session, then redirect to key-save page ─────────────────────
        session_regenerate_id(delete_old_session: true);
        $_SESSION['user_id']     = $userId;
        $_SESSION['username']    = $username;
        $_SESSION['private_key'] = $keyPair['private_key'];

        $this->redirect('/key');
    }

    // -------------------------------------------------------------------------
    // Private-key save page
    // -------------------------------------------------------------------------

    /** GET /key */
    public function saveKeyPage(array $_params = []): void
    {
        $this->requireAuth();

        $this->render('auth.save_key', [
            'private_key' => $_SESSION['private_key'] ?? '',
            'csrf_token'  => $this->generateCsrfToken(),
        ]);
    }

    /** POST /key/confirm */
    public function saveKeyConfirm(array $_params = []): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $this->setFlash('success', 'Welcome! Your key pair is active for this session.');
        $this->redirect('/');
    }

    // -------------------------------------------------------------------------
    // Login
    // -------------------------------------------------------------------------

    /** GET /login */
    public function loginForm(array $_params = []): void
    {
        if ($this->authUserId()) {
            $this->redirect('/');
        }

        $this->render('auth.login', [
            'csrf_token' => $this->generateCsrfToken(),
            'flash'      => $this->consumeFlash(),
        ]);
    }

    /** POST /login */
    public function login(array $_params = []): void
    {
        if ($this->authUserId()) {
            $this->redirect('/');
        }

        $this->validateCsrf();

        $username   = trim($_POST['username']    ?? '');
        $password   =      $_POST['password']    ?? '';
        $privateKey = trim($_POST['private_key'] ?? '');

        if ($username === '' || $password === '') {
            $this->setFlash('error', 'Username and password are required.');
            $this->redirect('/login');
        }

        // ── Verify credentials ────────────────────────────────────────────────
        $stmt = Database::getInstance()->prepare(
            'SELECT id, username, password FROM users WHERE username = :u LIMIT 1'
        );
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if ($user === false || !password_verify($password, $user['password'])) {
            $this->setFlash('error', 'Invalid username or password.');
            $this->redirect('/login');
        }

        // ── Validate private key format if provided ───────────────────────────
        if ($privateKey !== '' && !str_starts_with($privateKey, 'KAZSIGN-PRV-v1::')) {
            $this->setFlash('error', 'Invalid private key format. It must start with KAZSIGN-PRV-v1::');
            $this->redirect('/login');
        }

        // ── Start session ─────────────────────────────────────────────────────
        session_regenerate_id(delete_old_session: true);
        $_SESSION['user_id']     = (int) $user['id'];
        $_SESSION['username']    = $user['username'];
        $_SESSION['private_key'] = $privateKey !== '' ? $privateKey : null;

        $msg = $privateKey !== ''
            ? "Welcome back, {$user['username']}! Private key loaded — signing enabled."
            : "Welcome back, {$user['username']}! No private key — verify-only mode.";

        $this->setFlash('success', $msg);
        $this->redirect('/');
    }

    // -------------------------------------------------------------------------
    // Logout
    // -------------------------------------------------------------------------

    /** GET /logout */
    public function logout(array $_params = []): void
    {
        $_SESSION = [];
        session_destroy();
        $this->redirect('/login');
    }

    // -------------------------------------------------------------------------
    // Helpers (kept private — base Controller has protected versions too)
    // -------------------------------------------------------------------------

    private function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    private function validateCsrf(): void
    {
        $posted  = $_POST['csrf_token'] ?? '';
        $session = $_SESSION['csrf_token'] ?? '';
        if (!hash_equals($session, $posted)) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }
    }

    private function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    private function consumeFlash(): ?array
    {
        if (!isset($_SESSION['flash'])) return null;
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
}
