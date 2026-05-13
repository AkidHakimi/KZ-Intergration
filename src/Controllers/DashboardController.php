<?php

declare(strict_types=1);

namespace KazSign\Controllers;

use KazSign\Core\Controller;
use KazSign\Core\Database;

/**
 * DashboardController — loads role-specific data, renders single dashboard.php
 */
final class DashboardController extends Controller
{
    /** GET / */
    public function index(array $params = []): void
    {
        $this->requireAuth();

        $role   = $_SESSION['role'] ?? 'holder';
        $userId = $this->authUserId();
        $flash  = $this->consumeFlash();
        $csrf   = $this->generateCsrfToken();

        match ($role) {
            'issuer'   => $this->renderIssuer($userId, $flash, $csrf),
            'verifier' => $this->renderVerifier($flash, $csrf),
            default    => $this->renderHolder($userId, $flash, $csrf),
        };
    }

    // -------------------------------------------------------------------------

    private function renderIssuer(int $userId, ?array $flash, string $csrf): void
    {
        // Credentials this issuer has issued
        $stmt = Database::getInstance()->prepare(
            'SELECT c.*, u.username AS holder_name
               FROM credentials c
               JOIN users u ON u.id = c.holder_id
              WHERE c.issuer_id = :uid
           ORDER BY c.issued_at DESC'
        );
        $stmt->execute([':uid' => $userId]);
        $credentials = $stmt->fetchAll() ?: [];

        // All holders available to issue to
        $stmt = Database::getInstance()->prepare(
            'SELECT u.id, u.username, h.full_name, h.id_number
               FROM users u
               JOIN holders h ON h.user_id = u.id
              WHERE u.role = :role'
        );
        $stmt->execute([':role' => 'holder']);
        $holders = $stmt->fetchAll() ?: [];

        $this->render('dashboard', [
            'credentials' => $credentials,
            'holders'     => $holders,
            'flash'       => $flash,
            'csrf_token'  => $csrf,
            'result'      => null,
        ]);
    }

    private function renderHolder(int $userId, ?array $flash, string $csrf): void
    {
        // Credentials issued TO this holder
        $stmt = Database::getInstance()->prepare(
            'SELECT c.*, u.username AS issuer_name
               FROM credentials c
               JOIN users u ON u.id = c.issuer_id
              WHERE c.holder_id = :uid
           ORDER BY c.issued_at DESC'
        );
        $stmt->execute([':uid' => $userId]);
        $credentials = $stmt->fetchAll() ?: [];

        $this->render('dashboard', [
            'credentials' => $credentials,
            'holders'     => [],
            'flash'       => $flash,
            'csrf_token'  => $csrf,
            'result'      => null,
        ]);
    }

    private function renderVerifier(?array $flash, string $csrf): void
    {
        $this->render('dashboard', [
            'credentials' => [],
            'holders'     => [],
            'flash'       => $flash,
            'csrf_token'  => $csrf,
            'result'      => null,
        ]);
    }

    // -------------------------------------------------------------------------

    private function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    private function consumeFlash(): ?array
    {
        if (!isset($_SESSION['flash'])) return null;
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
}
