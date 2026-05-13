<?php

declare(strict_types=1);

namespace KazSign\Controllers;

use KazSign\Core\Controller;
use KazSign\Core\Database;
use KazSign\Core\KazSignEngine;

/**
 * CredentialController
 *
 * Handles JSON-LD Verifiable Credential lifecycle:
 *   issue()  — Issuer creates and signs a credential for a Holder
 *   verify() — Verifier checks a credential by its ID
 *   show()   — Holder views a single credential as JSON-LD
 */
final class CredentialController extends Controller
{
    // -------------------------------------------------------------------------
    // POST /credentials/issue  (Issuer only)
    // -------------------------------------------------------------------------

    public function issue(array $params = []): void
    {
        $this->requireAuth();
        $this->requireRole('issuer');
        $this->validateCsrf();

        $holderId       = (int) ($_POST['holder_id']       ?? 0);
        $credentialType = trim($_POST['credential_type']   ?? 'AcademicCredential');
        $subjectData    = trim($_POST['subject_data']      ?? '');

        if ($holderId === 0 || $subjectData === '') {
            $this->flashAndRedirect('error', 'Holder and subject data are required.', '/');
        }

        // Verify holder exists
        $stmt = Database::getInstance()->prepare(
            'SELECT u.id, u.username, u.public_key, h.full_name, h.id_number
               FROM users u JOIN holders h ON h.user_id = u.id
              WHERE u.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $holderId]);
        $holder = $stmt->fetch();

        if (!$holder) {
            $this->flashAndRedirect('error', 'Holder not found.', '/');
        }

        // Get issuer info
        $stmt = Database::getInstance()->prepare(
            'SELECT u.username, u.public_key, i.organisation
               FROM users u JOIN issuers i ON i.user_id = u.id
              WHERE u.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $this->authUserId()]);
        $issuer = $stmt->fetch();

        // Build JSON-LD Verifiable Credential
        $credentialId = 'urn:uuid:' . $this->generateUuid();
        $issuedAt     = date('c'); // ISO 8601

        // Parse subject data — accept JSON or plain text
        $subjectJson = json_decode($subjectData, true);
        if (!is_array($subjectJson)) {
            // Plain text — wrap it
            $subjectJson = ['description' => $subjectData];
        }

        $credential = [
            '@context' => [
                'https://www.w3.org/2018/credentials/v1',
                'https://www.w3.org/2018/credentials/examples/v1',
            ],
            'id'   => $credentialId,
            'type' => ['VerifiableCredential', $credentialType],
            'issuer' => [
                'id'           => 'did:kazsign:' . hash('sha256', $issuer['username']),
                'name'         => $issuer['organisation'],
                'publicKey'    => $issuer['public_key'],
            ],
            'issuanceDate'      => $issuedAt,
            'credentialSubject' => array_merge([
                'id'       => 'did:kazsign:' . hash('sha256', $holder['username']),
                'name'     => $holder['full_name'],
                'idNumber' => $holder['id_number'],
            ], $subjectJson),
        ];

        $jsonld    = json_encode($credential, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $fileHash  = hash('sha256', $jsonld);

        // Sign with private key
        $privateKey = $_SESSION['private_key'] ?? null;
        if ($privateKey === null) {
            $this->flashAndRedirect('error', 'No private key in session. Log out and log back in with your key.', '/');
        }

        try {
            $engine    = new KazSignEngine();
            $signature = $engine->signData($jsonld, $privateKey);
        } catch (\Throwable $e) {
            error_log('[KazSign] Signing failed: ' . $e->getMessage());
            $this->flashAndRedirect('error', 'Signing failed. Check server logs.', '/');
        }

        // Add proof to the credential
        $credential['proof'] = [
            'type'               => 'KazSign2024',
            'created'            => $issuedAt,
            'verificationMethod' => 'did:kazsign:' . hash('sha256', $issuer['username']),
            'proofValue'         => $signature,
        ];

        $signedJsonld = json_encode($credential, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Persist to DB
        try {
            $stmt = Database::getInstance()->prepare(
                'INSERT INTO credentials
                    (issuer_id, holder_id, credential_id, subject, jsonld, signature, file_hash, status)
                 VALUES
                    (:issuer_id, :holder_id, :credential_id, :subject, :jsonld, :signature, :file_hash, :status)'
            );
            $stmt->execute([
                ':issuer_id'     => $this->authUserId(),
                ':holder_id'     => $holderId,
                ':credential_id' => $credentialId,
                ':subject'       => json_encode($subjectJson),
                ':jsonld'        => $signedJsonld,
                ':signature'     => $signature,
                ':file_hash'     => $fileHash,
                ':status'        => 'issued',
            ]);
        } catch (\Throwable $e) {
            error_log('[KazSign] Credential insert failed: ' . $e->getMessage());
            $this->flashAndRedirect('error', 'Database error saving credential.', '/');
        }

        $this->flashAndRedirect('success', "Credential issued to {$holder['full_name']} successfully.", '/');
    }

    // -------------------------------------------------------------------------
    // GET /credentials/:id/show  (Holder views JSON-LD)
    // -------------------------------------------------------------------------

    public function show(array $params = []): void
    {
        $this->requireAuth();

        $credId = (int) ($params['id'] ?? 0);
        $cred   = $this->findCredential($credId);

        if (!$cred) {
            $this->flashAndRedirect('error', 'Credential not found.', '/');
        }

        // Only holder or issuer of this credential can view it
        $userId = $this->authUserId();
        if ($cred['holder_id'] != $userId && $cred['issuer_id'] != $userId) {
            $this->flashAndRedirect('error', 'Access denied.', '/');
        }

        $this->render('holder.credential_show', [
            'credential' => $cred,
            'jsonld'     => json_decode($cred['jsonld'], true),
            'flash'      => $this->consumeFlash(),
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /credentials/verify  (Verifier)
    // -------------------------------------------------------------------------

    public function verify(array $params = []): void
    {
        $this->requireAuth();
        $this->requireRole('verifier');
        $this->validateCsrf();

        $credentialId = trim($_POST['credential_id'] ?? '');

        if ($credentialId === '') {
            $this->flashAndRedirect('error', 'Please enter a credential ID.', '/');
        }

        // Find credential
        $stmt = Database::getInstance()->prepare(
            'SELECT c.*, u.public_key AS issuer_public_key
               FROM credentials c
               JOIN users u ON u.id = c.issuer_id
              WHERE c.credential_id = :cid LIMIT 1'
        );
        $stmt->execute([':cid' => $credentialId]);
        $cred = $stmt->fetch();

        if (!$cred) {
            $this->renderVerifierResult(null, 'Credential ID not found in the system.', false);
            return;
        }

        // Recompute hash of the jsonld (without proof)
        $jsonldData  = json_decode($cred['jsonld'], true);
        $proofBackup = $jsonldData['proof'] ?? null;
        unset($jsonldData['proof']);
        $jsonldNoProof   = json_encode($jsonldData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $computedHash    = hash('sha256', $jsonldNoProof);
        $hashIntact      = hash_equals($cred['file_hash'], $computedHash);

        // Verify signature
        $engine         = new KazSignEngine();
        $signatureValid = $engine->verifySignature(
            $jsonldNoProof,
            $cred['signature'],
            $cred['issuer_public_key']
        );

        $verified  = $hashIntact && $signatureValid;
        $newStatus = $verified ? 'verified' : 'rejected';

        // Update status
        $stmt = Database::getInstance()->prepare(
            'UPDATE credentials SET status = :status WHERE id = :id'
        );
        $stmt->execute([':status' => $newStatus, ':id' => $cred['id']]);

        $this->renderVerifierResult(
            $cred,
            $verified ? 'Credential is authentic and untampered.' : 'Verification FAILED.',
            $verified,
            $hashIntact,
            $signatureValid
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function renderVerifierResult(
        ?array $cred,
        string $message,
        bool   $verified,
        bool   $hashIntact     = false,
        bool   $signatureValid = false
    ): void {
        $this->render('verifier.dashboard', [
            'flash'      => ['type' => $verified ? 'success' : 'error', 'message' => $message],
            'csrf_token' => $this->generateCsrfToken(),
            'result'     => $cred ? [
                'credential'      => $cred,
                'jsonld'          => json_decode($cred['jsonld'], true),
                'hash_intact'     => $hashIntact,
                'signature_valid' => $signatureValid,
                'verified'        => $verified,
            ] : null,
        ]);
    }

    private function findCredential(int $id): ?array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM credentials WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function requireRole(string $role): void
    {
        if (($_SESSION['role'] ?? '') !== $role) {
            $this->flashAndRedirect('error', "Access denied. This page is for {$role}s only.", '/');
        }
    }

    private function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
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

    private function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
