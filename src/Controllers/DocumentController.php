<?php

declare(strict_types=1);

namespace KazSign\Controllers;

use KazSign\Core\Controller;
use KazSign\Core\Database;
use KazSign\Core\KazSignEngine;

/**
 * DocumentController
 *
 * Handles the full document lifecycle:
 *   index()      — list the authenticated user's documents (dashboard)
 *   uploadForm() — render the upload form (GET /documents/upload)
 *   upload()     — receive file, hash it, sign it, persist it (POST /documents/upload)
 *   verify()     — re-run signature verification and show result (GET /documents/:id/verify)
 */
final class DocumentController extends Controller
{
    /** Permitted MIME types for uploaded documents. */
    private const ALLOWED_MIME = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
    ];

    /** Maximum upload size: 10 MB. */
    private const MAX_BYTES = 10 * 1_024 * 1_024;

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    /** GET / — dashboard */
    public function index(array $params = []): void
    {
        $this->requireAuth();

        $this->render('dashboard', [
            'documents'  => $this->fetchUserDocuments($this->authUserId()),
            'flash'      => $this->consumeFlash(),
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /** GET /documents/upload */
    public function uploadForm(array $params = []): void
    {
        $this->requireAuth();

        $this->render('dashboard', [
            'documents'  => $this->fetchUserDocuments($this->authUserId()),
            'flash'      => $this->consumeFlash(),
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /** POST /documents/upload */
    public function upload(array $params = []): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        // 1. File presence check
        if (empty($_FILES['document']) || $_FILES['document']['error'] === UPLOAD_ERR_NO_FILE) {
            $this->flashAndRedirect('error', 'Please choose a file to upload.', '/documents/upload');
        }

        $file = $_FILES['document'];

        // 2. Upload error codes
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->flashAndRedirect(
                'error',
                'Upload failed (code ' . $file['error'] . '). Try again.',
                '/documents/upload'
            );
        }

        // 3. Size guard
        if ($file['size'] > self::MAX_BYTES) {
            $this->flashAndRedirect('error', 'File exceeds the 10 MB limit.', '/documents/upload');
        }

        // 4. MIME type guard
        $detectedMime = mime_content_type($file['tmp_name']);
        if (!in_array($detectedMime, self::ALLOWED_MIME, strict: true)) {
            $this->flashAndRedirect(
                'error',
                'Unsupported file type. Allowed: PDF, DOC, DOCX, TXT.',
                '/documents/upload'
            );
        }

        // 5. Read raw bytes
        $rawBytes = file_get_contents($file['tmp_name']);
        if ($rawBytes === false) {
            $this->flashAndRedirect('error', 'Could not read the uploaded file.', '/documents/upload');
        }

        // 6. Compute file hash
        $fileHash = hash('sha256', $rawBytes);

        // 7. Sign with KazSignEngine
        $privateKey = $_SESSION['private_key'] ?? null;
        if ($privateKey === null) {
            $this->flashAndRedirect(
                'error',
                'No private key found in session. Please log out and log back in.',
                '/documents/upload'
            );
        }

        try {
            $engine    = new KazSignEngine();
            $signature = $engine->signData($rawBytes, $privateKey);
        } catch (\Throwable $e) {
            error_log('[KazSign] Signing failed: ' . $e->getMessage());
            $this->flashAndRedirect('error', 'Signing failed. Check server logs.', '/documents/upload');
        }

        // 8. Move file to uploads/ with safe name
        $safeOriginal = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
        $storedName   = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $safeOriginal;
        $destination  = ROOT_PATH . '/uploads/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $this->flashAndRedirect('error', 'Could not save the uploaded file.', '/documents/upload');
        }

        // 9. Persist to database
        // FIX: use Database::getInstance()->prepare() — NOT getConnection()->prepare()
        // getConnection() throws RuntimeException when anything goes wrong.
        try {
            $stmt = Database::getInstance()->prepare(
                'INSERT INTO documents (user_id, file_name, file_hash, signature, status)
                 VALUES (:user_id, :file_name, :file_hash, :signature, :status)'
            );
            $stmt->execute([
                ':user_id'   => $this->authUserId(),
                ':file_name' => $storedName,
                ':file_hash' => $fileHash,
                ':signature' => $signature,
                ':status'    => 'signed',
            ]);
        } catch (\Throwable $e) {
            error_log('[KazSign] DB insert failed: ' . $e->getMessage());
            @unlink($destination);
            $this->flashAndRedirect('error', 'Database error. Upload rolled back.', '/documents/upload');
        }

        $this->flashAndRedirect('success', "'{$safeOriginal}' signed and saved successfully.", '/');
    }

    /** GET /documents/:id/verify */
    public function verify(array $params = []): void
    {
        $this->requireAuth();

        $documentId = isset($params['id']) ? (int) $params['id'] : 0;

        $document = $this->findDocumentById($documentId);
        if ($document === null) {
            $this->flashAndRedirect('error', 'Document not found.', '/');
        }

        $publicKey = $this->fetchPublicKey((int) $document['user_id']);
        if ($publicKey === null) {
            $this->flashAndRedirect('error', 'Owner public key not found.', '/');
        }

        $filePath = ROOT_PATH . '/uploads/' . $document['file_name'];
        $rawBytes = is_file($filePath) ? file_get_contents($filePath) : false;
        if ($rawBytes === false) {
            $this->flashAndRedirect('error', 'Stored file is missing from disk.', '/');
        }

        $currentHash    = hash('sha256', $rawBytes);
        $hashIntact     = hash_equals($document['file_hash'], $currentHash);

        $engine         = new KazSignEngine();
        $signatureValid = $engine->verifySignature($rawBytes, $document['signature'], $publicKey);

        $verified  = $hashIntact && $signatureValid;
        $newStatus = $verified ? 'verified' : 'rejected';
        $this->updateDocumentStatus($documentId, $newStatus);

        $flashType    = $verified ? 'success' : 'error';
        $flashMessage = $verified
            ? 'Signature verified — document is authentic and untampered.'
            : 'Verification FAILED — signature invalid or file has been modified.';

        $this->flashAndRedirect($flashType, $flashMessage, '/');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function fetchUserDocuments(?int $userId): array
    {
        if ($userId === null) return [];

        $stmt = Database::getInstance()->prepare(
            'SELECT id, file_name, file_hash, signature, status, created_at
               FROM documents
              WHERE user_id = :uid
           ORDER BY created_at DESC'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    private function findDocumentById(int $id): ?array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT id, user_id, file_name, file_hash, signature, status
               FROM documents WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    private function fetchPublicKey(int $userId): ?string
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT public_key FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();
        return $row !== false ? $row['public_key'] : null;
    }

    private function updateDocumentStatus(int $id, string $status): void
    {
        $stmt = Database::getInstance()->prepare(
            'UPDATE documents SET status = :status WHERE id = :id'
        );
        $stmt->execute([':status' => $status, ':id' => $id]);
    }

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
}
