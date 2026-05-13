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
 *
 * Every action that mutates state validates a CSRF token to prevent
 * cross-site request forgery.
 */
final class DocumentController extends Controller
{
    // -------------------------------------------------------------------------
    // Configuration
    // -------------------------------------------------------------------------

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

    /**
     * GET /
     * GET /documents
     *
     * Render the dashboard showing the signed-documents list.
     *
     * @param array<string, string> $params Route parameters (unused here).
     */
    public function index(array $params = []): void
    {
        $this->requireAuth();

        $documents = $this->fetchUserDocuments($this->authUserId());

        $this->render('dashboard', [
            'documents'  => $documents,
            'flash'      => $this->consumeFlash(),
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * GET /documents/upload
     *
     * Render the stand-alone upload form (also embedded in the dashboard).
     *
     * @param array<string, string> $params Route parameters (unused here).
     */
    public function uploadForm(array $params = []): void
    {
        $this->requireAuth();

        $this->render('dashboard', [
            'documents'  => $this->fetchUserDocuments($this->authUserId()),
            'flash'      => $this->consumeFlash(),
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * POST /documents/upload
     *
     * Pipeline:
     *   1. Validate CSRF token.
     *   2. Validate the uploaded file (size, MIME type).
     *   3. Read the file contents into memory.
     *   4. Compute the SHA-256 hex digest of the raw bytes.
     *   5. Retrieve the user's private key from the session.
     *   6. Call KazSignEngine::signData() → base64-encoded signature.
     *   7. Move the file to uploads/ with a collision-safe name.
     *   8. Persist (file_name, file_hash, signature, status='signed') in DB.
     *   9. Redirect to dashboard with a success flash message.
     *
     * @param array<string, string> $params Route parameters (unused here).
     */
    public function upload(array $params = []): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        // ── 1. File presence check ────────────────────────────────────────────
        if (empty($_FILES['document']) || $_FILES['document']['error'] === UPLOAD_ERR_NO_FILE) {
            $this->flashAndRedirect('error', 'Please choose a file to upload.', '/documents/upload');
        }

        $file = $_FILES['document'];

        // ── 2. Upload error codes ─────────────────────────────────────────────
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->flashAndRedirect(
                'error',
                'Upload failed (code ' . $file['error'] . '). Try again.',
                '/documents/upload'
            );
        }

        // ── 3. Size guard ─────────────────────────────────────────────────────
        if ($file['size'] > self::MAX_BYTES) {
            $this->flashAndRedirect('error', 'File exceeds the 10 MB limit.', '/documents/upload');
        }

        // ── 4. MIME type guard (server-side, not trusting client header) ──────
        $detectedMime = mime_content_type($file['tmp_name']);
        if (!in_array($detectedMime, self::ALLOWED_MIME, strict: true)) {
            $this->flashAndRedirect(
                'error',
                'Unsupported file type. Allowed: PDF, DOC, DOCX, TXT.',
                '/documents/upload'
            );
        }

        // ── 5. Read raw bytes ─────────────────────────────────────────────────
        $rawBytes = file_get_contents($file['tmp_name']);
        if ($rawBytes === false) {
            $this->flashAndRedirect('error', 'Could not read the uploaded file.', '/documents/upload');
        }

        // ── 6. Compute file hash ──────────────────────────────────────────────
        $fileHash = hash('sha256', $rawBytes); // 64-char hex string

        // ── 7. Sign with KazSignEngine ────────────────────────────────────────
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

        // ── 8. Move file to uploads/ with safe name ───────────────────────────
        $safeOriginal = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
        $storedName   = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $safeOriginal;
        $destination  = ROOT_PATH . '/uploads/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $this->flashAndRedirect('error', 'Could not save the uploaded file.', '/documents/upload');
        }

        // ── 9. Persist to database ────────────────────────────────────────────
        try {
            $pdo  = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare(
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
        } catch (\PDOException $e) {
            error_log('[KazSign] DB insert failed: ' . $e->getMessage());
            // File was already moved; clean up orphan.
            @unlink($destination);
            $this->flashAndRedirect('error', 'Database error. Upload rolled back.', '/documents/upload');
        }

        $this->flashAndRedirect('success', "'{$safeOriginal}' signed and saved successfully.", '/');
    }

    /**
     * GET /documents/:id/verify
     *
     * Re-read the stored file, re-compute its hash, then call
     * KazSignEngine::verifySignature() against the stored signature and the
     * document owner's public key.
     *
     * Shows the dashboard with a verification result banner.
     *
     * @param array<string, string> $params Route parameters — expects 'id'.
     */
    public function verify(array $params = []): void
    {
        $this->requireAuth();

        $documentId = isset($params['id']) ? (int) $params['id'] : 0;

        // ── Fetch document row ────────────────────────────────────────────────
        $document = $this->findDocumentById($documentId);

        if ($document === null) {
            $this->flashAndRedirect('error', 'Document not found.', '/');
        }

        // ── Fetch owner's public key ──────────────────────────────────────────
        $publicKey = $this->fetchPublicKey((int) $document['user_id']);

        if ($publicKey === null) {
            $this->flashAndRedirect('error', 'Owner public key not found.', '/');
        }

        // ── Re-read stored file ───────────────────────────────────────────────
        $filePath = ROOT_PATH . '/uploads/' . $document['file_name'];
        $rawBytes = is_file($filePath) ? file_get_contents($filePath) : false;

        if ($rawBytes === false) {
            $this->flashAndRedirect('error', 'Stored file is missing from disk.', '/');
        }

        // ── Re-compute hash and compare ───────────────────────────────────────
        $currentHash = hash('sha256', $rawBytes);
        $hashIntact  = hash_equals($document['file_hash'], $currentHash);

        // ── Cryptographic signature check ─────────────────────────────────────
        $engine          = new KazSignEngine();
        $signatureValid  = $engine->verifySignature($rawBytes, $document['signature'], $publicKey);

        // ── Derive overall result ─────────────────────────────────────────────
        $verified = $hashIntact && $signatureValid;

        // ── Update status in DB ───────────────────────────────────────────────
        $newStatus = $verified ? 'verified' : 'rejected';
        $this->updateDocumentStatus($documentId, $newStatus);

        // ── Render dashboard with result ──────────────────────────────────────
        $flashType    = $verified ? 'success' : 'error';
        $flashMessage = $verified
            ? 'Signature verified — document is authentic and untampered.'
            : 'Verification FAILED — signature invalid or file has been modified.';

        $this->render('dashboard', [
            'documents'        => $this->fetchUserDocuments($this->authUserId()),
            'flash'            => ['type' => $flashType, 'message' => $flashMessage],
            'csrf_token'       => $this->generateCsrfToken(),
            'verify_result'    => [
                'document'        => $document,
                'hash_intact'     => $hashIntact,
                'signature_valid' => $signatureValid,
                'verified'        => $verified,
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Database helpers
    // -------------------------------------------------------------------------

    /**
     * Return all documents belonging to $userId, newest first.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchUserDocuments(int $userId): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT id, file_name, file_hash, signature, status, created_at
               FROM documents
              WHERE user_id = :uid
           ORDER BY created_at DESC'
        );
        $stmt->execute([':uid' => $userId]);

        return $stmt->fetchAll();
    }

    /**
     * Return a single document row by its primary key, or null.
     *
     * @return array<string, mixed>|null
     */
    private function findDocumentById(int $id): ?array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM documents WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Return the public key stored for a given user, or null.
     */
    private function fetchPublicKey(int $userId): ?string
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT public_key FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();

        return $row !== false ? $row['public_key'] : null;
    }

    /**
     * Update the status column of a document row.
     */
    private function updateDocumentStatus(int $id, string $status): void
    {
        $stmt = Database::getInstance()->prepare(
            'UPDATE documents SET status = :status WHERE id = :id'
        );
        $stmt->execute([':status' => $status, ':id' => $id]);
    }

    // -------------------------------------------------------------------------
    // CSRF helpers
    // -------------------------------------------------------------------------

    /**
     * Generate (or reuse) a CSRF token for the current session.
     */
    private function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Abort with 403 when the posted CSRF token does not match the session token.
     */
    private function validateCsrf(): void
    {
        $posted  = $_POST['csrf_token'] ?? '';
        $session = $_SESSION['csrf_token'] ?? '';

        if (!hash_equals($session, $posted)) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }
    }

    // -------------------------------------------------------------------------
    // Flash message helpers
    // -------------------------------------------------------------------------

    /**
     * Store a flash message in the session, then redirect.
     *
     * @return never
     */
    private function flashAndRedirect(string $type, string $message, string $url): never
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
        $this->redirect($url);
    }

    /**
     * Read and clear the flash message from the session.
     *
     * @return array{type: string, message: string}|null
     */
    private function consumeFlash(): ?array
    {
        if (!isset($_SESSION['flash'])) {
            return null;
        }

        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);

        return $flash;
    }
}
