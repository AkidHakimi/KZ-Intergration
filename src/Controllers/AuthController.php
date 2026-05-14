<?php

declare(strict_types=1);

namespace KazSign\Controllers;

use KazSign\Core\Controller;
use KazSign\Core\Database;
use KazSign\Core\KazSignEngine;

/**
 * AuthController — registration (with role + DID), login, logout, key handoff.
 */
final class AuthController extends Controller
{
    /** GET /register */
    public function registerForm(array $_params = []): void
    {
        if ($this->authUserId()) $this->redirect('/');

        $this->render('auth.register', [
            'csrf_token' => $this->generateCsrfToken(),
            'flash'      => $this->consumeFlash(),
        ]);
    }

    /** POST /register */
    public function register(array $_params = []): void
    {
        if ($this->authUserId()) $this->redirect('/');

        $this->validateCsrf();

        $username     = trim($_POST['username']     ?? '');
        $email        = trim($_POST['email']        ?? '');
        $password     =      $_POST['password']     ?? '';
        $role         = trim($_POST['role']         ?? 'holder');
        $organisation = trim($_POST['organisation'] ?? '');
        $fullName     = trim($_POST['full_name']    ?? '');
        $idNumber     = trim($_POST['id_number']    ?? '');

        // Validation
        if ($username === '' || $email === '' || $password === '') {
            $this->setFlash('error', 'Username, email and password are required.');
            $this->redirect('/register');
        }

        if (!in_array($role, ['issuer', 'holder', 'verifier'], true)) {
            $this->setFlash('error', 'Invalid role selected.');
            $this->redirect('/register');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->setFlash('error', 'Invalid email address.');
            $this->redirect('/register');
        }

        if (strlen($password) < 8) {
            $this->setFlash('error', 'Password must be at least 8 characters.');
            $this->redirect('/register');
        }

        if (in_array($role, ['issuer', 'verifier'], true) && $organisation === '') {
            $this->setFlash('error', 'Organisation name is required for Issuer / Verifier.');
            $this->redirect('/register');
        }

        if ($role === 'holder' && ($fullName === '' || $idNumber === '')) {
            $this->setFlash('error', 'Full name and ID number are required for Holder.');
            $this->redirect('/register');
        }

        // Generate KAZ-SIGN PQC key pair
        $engine  = new KazSignEngine();
        $keyPair = $engine->generateKeyPair();

        // Generate DID
        $did = 'did:kazsign:' . hash('sha256', $username);

        $db = Database::getInstance();

        try {
            // Insert user — try with did column first
            try {
                $stmt = $db->prepare(
                    'INSERT INTO users (username, email, role, password, public_key, did)
                     VALUES (:username, :email, :role, :password, :public_key, :did)'
                );
                $stmt->execute([
                    ':username'   => $username,
                    ':email'      => $email,
                    ':role'       => $role,
                    ':password'   => password_hash($password, PASSWORD_BCRYPT),
                    ':public_key' => $keyPair['public_key'],
                    ':did'        => $did,
                ]);
            } catch (\Throwable $e) {
                // If did column doesn't exist, try without it
                if (str_contains($e->getMessage(), 'did') || str_contains($e->getMessage(), 'no such column')) {
                    $stmt = $db->prepare(
                        'INSERT INTO users (username, email, role, password, public_key)
                         VALUES (:username, :email, :role, :password, :public_key)'
                    );
                    $stmt->execute([
                        ':username'   => $username,
                        ':email'      => $email,
                        ':role'       => $role,
                        ':password'   => password_hash($password, PASSWORD_BCRYPT),
                        ':public_key' => $keyPair['public_key'],
                    ]);
                } else {
                    throw $e;
                }
            }

            $userId = $db->lastInsertId();

            // Insert into role table
            if ($role === 'issuer') {
                $stmt = $db->prepare(
                    'INSERT INTO issuers (user_id, organisation) VALUES (:uid, :org)'
                );
                $stmt->execute([':uid' => $userId, ':org' => $organisation]);

            } elseif ($role === 'holder') {
                $stmt = $db->prepare(
                    'INSERT INTO holders (user_id, full_name, id_number)
                     VALUES (:uid, :name, :idn)'
                );
                $stmt->execute([':uid' => $userId, ':name' => $fullName, ':idn' => $idNumber]);

            } elseif ($role === 'verifier') {
                $stmt = $db->prepare(
                    'INSERT INTO verifiers (user_id, organisation) VALUES (:uid, :org)'
                );
                $stmt->execute([':uid' => $userId, ':org' => $organisation]);
            }

        } catch (\Throwable $e) {
            $msg = $e->getMessage();

            if (str_contains($msg, '1062') || str_contains($msg, 'UNIQUE constraint failed')) {
                $this->setFlash('error', 'Username or email is already taken.');
            } else {
                // Show the REAL error so we can debug it
                $this->setFlash('error', 'Registration failed: ' . $msg);
            }

            error_log('[KazSign] Register error: ' . $msg);
            $this->redirect('/register');
        }

        session_regenerate_id(delete_old_session: true);
        $_SESSION['user_id']     = $userId;
        $_SESSION['username']    = $username;
        $_SESSION['role']        = $role;
        $_SESSION['did']         = $did;
        $_SESSION['private_key'] = $keyPair['private_key'];

        $this->redirect('/key');
    }

    /** GET /key */
    public function saveKeyPage(array $_params = []): void
    {
        $this->requireAuth();
        $this->render('auth.save_key', [
            'private_key' => $_SESSION['private_key'] ?? '',
            'did'         => $_SESSION['did']         ?? '',
            'role'        => $_SESSION['role']         ?? 'holder',
            'csrf_token'  => $this->generateCsrfToken(),
        ]);
    }

    /** POST /key/confirm */
    public function saveKeyConfirm(array $_params = []): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->setFlash('success', 'Welcome! Your KAZ-SIGN PQC key pair is active.');
        $this->redirect('/');
    }

    /** GET /login */
    public function loginForm(array $_params = []): void
    {
        if ($this->authUserId()) $this->redirect('/');
        $this->render('auth.login', [
            'csrf_token' => $this->generateCsrfToken(),
            'flash'      => $this->consumeFlash(),
        ]);
    }

    /** POST /login */
    public function login(array $_params = []): void
    {
        if ($this->authUserId()) $this->redirect('/');

        $this->validateCsrf();

        $username   = trim($_POST['username']    ?? '');
        $password   =      $_POST['password']    ?? '';
        $privateKey = trim($_POST['private_key'] ?? '');

        if ($username === '' || $password === '') {
            $this->setFlash('error', 'Username and password are required.');
            $this->redirect('/login');
        }

        try {
            $stmt = Database::getInstance()->prepare(
                'SELECT id, username, password, role, did FROM users WHERE username = :u LIMIT 1'
            );
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch();
        } catch (\Throwable $e) {
            // Try without did column for older schema
            try {
                $stmt = Database::getInstance()->prepare(
                    'SELECT id, username, password, role FROM users WHERE username = :u LIMIT 1'
                );
                $stmt->execute([':u' => $username]);
                $user = $stmt->fetch();
            } catch (\Throwable $e2) {
                $this->setFlash('error', 'Login error: ' . $e2->getMessage());
                $this->redirect('/login');
            }
        }

        if ($user === false || !password_verify($password, $user['password'])) {
            $this->setFlash('error', 'Invalid username or password.');
            $this->redirect('/login');
        }

        if ($privateKey !== '' && !str_starts_with($privateKey, 'KAZSIGN-PRV-v1::')) {
            $this->setFlash('error', 'Invalid private key format.');
            $this->redirect('/login');
        }

        session_regenerate_id(delete_old_session: true);
        $_SESSION['user_id']     = (int) $user['id'];
        $_SESSION['username']    = $user['username'];
        $_SESSION['role']        = $user['role'] ?? 'holder';
        $_SESSION['did']         = $user['did']  ?? ('did:kazsign:' . hash('sha256', $user['username']));
        $_SESSION['private_key'] = $privateKey !== '' ? $privateKey : null;

        $this->setFlash('success', "Welcome back, {$user['username']}! Signed in as {$_SESSION['role']}.");
        $this->redirect('/');
    }

    /** GET /logout */
    public function logout(array $_params = []): void
    {
        $_SESSION = [];
        session_destroy();
        $this->redirect('/login');
    }

    // -------------------------------------------------------------------------
    // Helpers
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