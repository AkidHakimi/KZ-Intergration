<?php

declare(strict_types=1);

namespace KazSign\Controllers;

use KazSign\Core\Controller;
use KazSign\Core\Database;
use KazSign\Core\KazSignEngine;

/**
 * DocumentController
 *
 * Handles file upload and per-document signature verification:
 *   index()      — list documents for the logged-in user  (GET  /documents)
 *   uploadForm() — show upload form                       (GET  /documents/upload)
 *   upload()     — receive file, hash, sign, persist      (POST /documents/upload)
 *   verify()     — re-verify a stored document            (GET  /documents/:id/verify)
 */
final class DocumentController extends Controller
{
    private const UPLOAD_DIR     = ROOT_PATH . '/uploads/';
    private const MAX_SIZE_BYTES = 10 * 1024 * 1024; // 10 MB

    // -------------------------------------------------------------------------
    // GET /documents
    // -------------------------------------------------------------------------

    public function index(array $params = []): void
    {
        $this->requireAuth();

        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM documents WHERE user_id = :uid ORDER BY created_at DESC'
        );
        $stmt->execute([':uid' => $this->authUserId()]);
        $documents = $stmt->fetchAll() ?: [];

        $this->render('dashboard', [
            'credentials'    => [],
            'holders'        => [],
            'documents'      => $documents,
            'flash'          => $this->consumeFlash(),
            'csrf_token'     => $this->generateCsrfToken(),
            'result'         => null,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /documents/upload
    // -------------------------------------------------------------------------

    public function uploadForm(array $params = []): void
    {
        $this->requireAuth();

        $this->render('dashboard', [
            'credentials' => [],
            'holders'     => [],
            'documents'   => [],
            'flash'       => $this->consumeFlash(),
            'csrf_token'  => $this->generateCsrfToken(),
            'result'      => null,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /documents/upload
    // -------------------------------------------------------------------------

    public function upload(array $params = []): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        // ── File validation ───────────────────────────────────────────────────
        if (empty($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            $this->flashAndRedirect('error', 'No file uploaded or upload error occurred.', '/documents/upload');
        }

        $tmpPath  = $_FILES['document']['tmp_name'];
        $origName = basename($_FILES['document']['name']);
        $size     = $_FILES['document']['size'];

        if ($size > self::MAX_SIZE_BYTES) {
            $this->flashAndRedirect('error', 'File exceeds the 10 MB size limit.', '/documents/upload');
        }

        if (!is_uploaded_file($tmpPath)) {
            $this->flashAndRedirect('error', 'Invalid upload.', '/documents/upload');
        }

        // ── Hash the file ─────────────────────────────────────────────────────
        $fileHash = hash_file('sha256', $tmpPath);

        // ── Sign the hash ─────────────────────────────────────────────────────
        $privateKey = $_SESSION['private_key'] ?? null;
        $signature  = '';
        $status     = 'pending';

        if ($privateKey !== null) {
            try {
                $engine    = new KazSignEngine();
                $signature = $engine->signData($fileHash, $privateKey);
                $status    = 'signed';
            } catch (\Throwable $e) {
                error_log('[KazSign] Document signing failed: ' . $e->getMessage());
                // Continue without signature — stored as pending
            }
        }

        // ── Persist the upload ────────────────────────────────────────────────
        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, true);
        }

        $storedName = date('Ymd_His') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
        move_uploaded_file($tmpPath, self::UPLOAD_DIR . $storedName);

        try {
            $stmt = Database::getInstance()->prepare(
                'INSERT INTO documents (user_id, file_name, file_hash, signature, status)
                 VALUES (:user_id, :file_name, :file_hash, :signature, :status)'
            );
            $stmt->execute([
                ':user_id'   => $this->authUserId(),
                ':file_name' => $origName,
                ':file_hash' => $fileHash,
                ':signature' => $signature,
                ':status'    => $status,
            ]);
        } catch (\Throwable $e) {
            error_log('[KazSign] Document DB insert failed: ' . $e->getMessage());
            $this->flashAndRedirect('error', 'Database error saving document.', '/documents/upload');
        }

        $msg = $status === 'signed'
            ? "Document uploaded and signed successfully."
            : "Document uploaded (no private key loaded — stored as pending, not signed).";

        $this->flashAndRedirect('success', $msg, '/');
    }

    // -------------------------------------------------------------------------
    // GET /documents/:id/verify
    // -------------------------------------------------------------------------

    public function verify(array $params = []): void
    {
        $this->requireAuth();

        $docId = (int) ($params['id'] ?? 0);

        $stmt = Database::getInstance()->prepare(
            'SELECT d.*, u.public_key
               FROM documents d
               JOIN users u ON u.id = d.user_id
              WHERE d.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $docId]);
        $doc = $stmt->fetch();

        if (!$doc) {
            $this->flashAndRedirect('error', 'Document not found.', '/');
        }

        if ((int)$doc['user_id'] !== $this->authUserId()) {
            $this->flashAndRedirect('error', 'Access denied.', '/');
        }

        if (empty($doc['signature'])) {
            $this->flashAndRedirect('error', 'This document has no signature to verify.', '/');
        }

        $engine         = new KazSignEngine();
        $signatureValid = $engine->verifySignature(
            $doc['file_hash'],
            $doc['signature'],
            $doc['public_key']
        );

        $newStatus = $signatureValid ? 'verified' : 'rejected';

        $stmt = Database::getInstance()->prepare(
            'UPDATE documents SET status = :status WHERE id = :id'
        );
        $stmt->execute([':status' => $newStatus, ':id' => $docId]);

        $this->flashAndRedirect(
            $signatureValid ? 'success' : 'error',
            $signatureValid
                ? "Document verified — signature is valid."
                : "Verification failed — signature is invalid or document was modified.",
            '/'
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function validateCsrf(): void
    {
        $posted  = $_POST['csrf_token'] ?? '';
        $session = $_SESSION['csrf_token'] ?? '';
        if (!hash_equals($session, $posted)) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }
    }

    private function flashAndRedirect(string $type, string $message, string $url): never
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
        $this->redirect($url);
    }

    private function consumeFlash(): ?array
    {
        if (!isset($_SESSION['flash'])) return null;
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }

    private function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}