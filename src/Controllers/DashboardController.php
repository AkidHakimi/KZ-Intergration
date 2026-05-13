<?php

declare(strict_types=1);

namespace KazSign\Controllers;

use KazSign\Core\Controller;
use KazSign\Core\Database;

/**
 * DashboardController — home page.
 *
 * Delegates all real logic to DocumentController::index().
 * Kept as a separate class so the route table stays semantic.
 */
final class DashboardController extends Controller
{
    /** GET / */
    public function index(array $params = []): void
    {
        $this->requireAuth();

        $stmt = Database::getInstance()->prepare(
            'SELECT id, file_name, file_hash, signature, status, created_at
               FROM documents
              WHERE user_id = :uid
           ORDER BY created_at DESC'
        );
        $stmt->execute([':uid' => $this->authUserId()]);
        $documents = $stmt->fetchAll();

        $flash = null;
        if (!empty($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
        }

        $csrfToken = $_SESSION['csrf_token'] ?? '';
        if ($csrfToken === '') {
            $csrfToken = bin2hex(random_bytes(32));
            $_SESSION['csrf_token'] = $csrfToken;
        }

        $this->render('dashboard', [
            'documents'  => $documents,
            'flash'      => $flash,
            'csrf_token' => $csrfToken,
        ]);
    }
}
