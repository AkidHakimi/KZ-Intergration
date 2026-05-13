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
 *   issue()  — Issuer creates and signs a credential for a Holder (POST /credentials/issue)
 *   verify() — Verifier checks a credential by its ID       (POST /credentials/verify)
 *   show()   — Holder views a single credential as JSON-LD  (GET  /credentials/:id/show)
 */
final class CredentialController extends Controller
{
    // =========================================================================
    //  POST /credentials/issue   (Issuer only)
    // =========================================================================

    public function issue(array $params = []): void
    {
        $this->requireAuth();
        $this->requireRole('issuer');
        $this->validateCsrf();

        $holderId       = (int)   ($_POST['holder_id']       ?? 0);
        $credentialType = trim(    $_POST['credential_type']  ?? 'AcademicCredential');
        $subjectData    = trim(    $_POST['subject_data']     ?? '');

        // ── Validation ────────────────────────────────────────────────────────
        if ($holderId === 0 || $subjectData === '') {
            $this->flashAndRedirect('error', 'Holder and subject data are required.', '/');
        }

        // ── Load holder ───────────────────────────────────────────────────────
        $stmt = Database::getInstance()->prepare(
            'SELECT u.id, u.username, u.public_key, h.full_name, h.id_number
               FROM users u
               JOIN holders h ON h.user_id = u.id
              WHERE u.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $holderId]);
        $holder = $stmt->fetch();

        if (!$holder) {
            $this->flashAndRedirect('error', 'Holder not found.', '/');
        }

        // ── Load issuer ───────────────────────────────────────────────────────
        $stmt = Database::getInstance()->prepare(
            'SELECT u.username, u.public_key, i.organisation
               FROM users u
               JOIN issuers i ON i.user_id = u.id
              WHERE u.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $this->authUserId()]);
        $issuer = $stmt->fetch();

        if (!$issuer) {
            $this->flashAndRedirect('error', 'Issuer profile not found.', '/');
        }

        // ── Parse subject data ────────────────────────────────────────────────
        $subjectJson = json_decode($subjectData, true);
        if (!is_array($subjectJson)) {
            // Plain text — wrap it
            $subjectJson = ['description' => $subjectData];
        }

        // ── Build JSON-LD Verifiable Credential ───────────────────────────────
        $credentialId = 'urn:uuid:' . $this->generateUuid();
        $issuedAt     = date('c'); // ISO 8601

        $credential = [
            '@context' => [
                'https://www.w3.org/2018/credentials/v1',
                'https://www.w3.org/2018/credentials/examples/v1',
            ],
            'id'   => $credentialId,
            'type' => ['VerifiableCredential', $credentialType],
            'issuer' => [
                'id'        => 'did:kazsign:' . hash('sha256', $issuer['username']),
                'name'      => $issuer['organisation'],
                'publicKey' => $issuer['public_key'],
            ],
            'issuanceDate'      => $issuedAt,
            'credentialSubject' => array_merge([
                'id'       => 'did:kazsign:' . hash('sha256', $holder['username']),
                'name'     => $holder['full_name'],
                'idNumber' => $holder['id_number'],
            ], $subjectJson),
        ];

        // ── Encode to JSON (without proof yet) ────────────────────────────────
        $jsonld   = json_encode($credential, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $fileHash = hash('sha256', $jsonld);

        // ── Sign with private key ─────────────────────────────────────────────
        $privateKey = $_SESSION['private_key'] ?? null;
        if ($privateKey === null) {
            $this->flashAndRedirect(
                'error',
                'No private key in session. Log out and log back in with your key.',
                '/'
            );
        }

        try {
            $engine    = new KazSignEngine();
            $signature = $engine->signData($jsonld, $privateKey);
        } catch (\Throwable $e) {
            error_log('[KazSign] Credential signing failed: ' . $e->getMessage());
            $this->flashAndRedirect('error', 'Signing failed. Check server logs.', '/');
        }

        // ── Add proof block ───────────────────────────────────────────────────
        $credential['proof'] = [
            'type'               => 'KazSign2024',
            'created'            => $issuedAt,
            'verificationMethod' => 'did:kazsign:' . hash('sha256', $issuer['username']),
            'proofValue'         => $signature,
        ];

        $signedJsonld = json_encode($credential, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // ── Persist to database ───────────────────────────────────────────────
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
            error_log('[KazSign] Credential DB insert failed: ' . $e->getMessage());
            $this->flashAndRedirect('error', 'Database error saving credential.', '/');
        }

        $this->flashAndRedirect(
            'success',
            "Credential issued to {$holder['full_name']} successfully.",
            '/'
        );
    }

    // =========================================================================
    //  POST /credentials/verify   (Verifier only)
    // =========================================================================

    public function verify(array $params = []): void
    {
        $this->requireAuth();
        $this->requireRole('verifier');
        $this->validateCsrf();

        $credentialId = trim($_POST['credential_id'] ?? '');

        if ($credentialId === '') {
            $this->flashAndRedirect('error', 'Please enter a Credential ID.', '/');
        }

        // ── Find credential ───────────────────────────────────────────────────
        $stmt = Database::getInstance()->prepare(
            'SELECT c.*, u.public_key AS issuer_public_key
               FROM credentials c
               JOIN users u ON u.id = c.issuer_id
              WHERE c.credential_id = :cid LIMIT 1'
        );
        $stmt->execute([':cid' => $credentialId]);
        $cred = $stmt->fetch();

        if (!$cred) {
            // Credential not found — render verifier dashboard with error
            $this->renderVerifierDashboard(
                ['type' => 'error', 'message' => 'Credential ID not found in the system.'],
                null
            );
            return;
        }

        // ── Recompute hash of jsonld WITHOUT the proof block ──────────────────
        $jsonldData = json_decode($cred['jsonld'], true);
        unset($jsonldData['proof']); // remove proof before hashing
        $jsonldNoProof = json_encode($jsonldData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $computedHash  = hash('sha256', $jsonldNoProof);
        $hashIntact    = hash_equals($cred['file_hash'], $computedHash);

        // ── Verify KAZ-SIGN signature ─────────────────────────────────────────
        $engine         = new KazSignEngine();
        $signatureValid = $engine->verifySignature(
            $jsonldNoProof,
            $cred['signature'],
            $cred['issuer_public_key']
        );

        $verified  = $hashIntact && $signatureValid;
        $newStatus = $verified ? 'verified' : 'rejected';

        // ── Update status ─────────────────────────────────────────────────────
        $stmt = Database::getInstance()->prepare(
            'UPDATE credentials SET status = :status WHERE id = :id'
        );
        $stmt->execute([':status' => $newStatus, ':id' => $cred['id']]);

        // Re-attach the full jsonld for display
        $cred['jsonld_decoded'] = json_decode($cred['jsonld'], true);

        $this->renderVerifierDashboard(
            [
                'type'    => $verified ? 'success' : 'error',
                'message' => $verified
                    ? 'Credential is authentic and untampered.'
                    : 'Verification FAILED — signature invalid or credential has been modified.',
            ],
            [
                'credential'      => $cred,
                'jsonld'          => $cred['jsonld_decoded'],
                'hash_intact'     => $hashIntact,
                'signature_valid' => $signatureValid,
                'verified'        => $verified,
            ]
        );
    }

    // =========================================================================
    //  GET /credentials/:id/show   (Holder views full JSON-LD page)
    // =========================================================================

    public function show(array $params = []): void
    {
        $this->requireAuth();

        $id   = (int) ($params['id'] ?? 0);
        $cred = $this->findCredentialById($id);

        if (!$cred) {
            $this->flashAndRedirect('error', 'Credential not found.', '/');
        }

        // Only the holder or issuer of this credential can view it
        $userId = $this->authUserId();
        if ((int)$cred['holder_id'] !== $userId && (int)$cred['issuer_id'] !== $userId) {
            $this->flashAndRedirect('error', 'Access denied.', '/');
        }

        $this->render('dashboard', [
            'credentials' => [$cred],
            'holders'     => [],
            'flash'       => $this->consumeFlash(),
            'csrf_token'  => $this->generateCsrfToken(),
            'result'      => null,
        ]);
    }

    // =========================================================================
    //  Private helpers
    // =========================================================================

    /**
     * Render the verifier's view of dashboard.php with a result.
     */
    private function renderVerifierDashboard(?array $flash, ?array $result): void
    {
        $this->render('dashboard', [
            'credentials' => [],
            'holders'     => [],
            'flash'       => $flash,
            'csrf_token'  => $this->generateCsrfToken(),
            'result'      => $result,
        ]);
    }

    /**
     * Find a credential row by its integer primary key.
     */
    private function findCredentialById(int $id): ?array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM credentials WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Abort with a flash redirect if the session role doesn't match.
     */
    private function requireRole(string $role): void
    {
        if (($_SESSION['role'] ?? '') !== $role) {
            $this->flashAndRedirect(
                'error',
                "Access denied. This action is for {$role}s only.",
                '/'
            );
        }
    }

    /**
     * Generate a UUID v4.
     */
    private function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Validate the CSRF token from the POST body.
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

    /**
     * Store a flash message and redirect.
     * @return never
     */
    private function flashAndRedirect(string $type, string $message, string $url): never
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
        $this->redirect($url);
    }

    /**
     * Consume and clear the flash message from session.
     */
    private function consumeFlash(): ?array
    {
        if (!isset($_SESSION['flash'])) return null;
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }

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
}