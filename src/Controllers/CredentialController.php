<?php

declare(strict_types=1);

namespace KazSign\Controllers;

use KazSign\Core\Controller;
use KazSign\Core\Database;
use KazSign\Core\KazSignEngine;

/**
 * CredentialController
 *
 * issue()  — Issuer creates and signs a credential (POST /credentials/issue)
 * revoke() — Issuer revokes a credential          (POST /credentials/revoke)
 * verify() — Verifier checks a credential         (POST /credentials/verify)
 * show()   — View a single credential as JSON-LD  (GET  /credentials/:id/show)
 */
final class CredentialController extends Controller
{
    // =========================================================================
    //  POST /credentials/issue
    // =========================================================================

    public function issue(array $params = []): void
    {
        $this->requireAuth();
        $this->requireRole('issuer');
        $this->validateCsrf();

        $holderId       = (int)  ($_POST['holder_id']      ?? 0);
        $credentialType = trim(   $_POST['credential_type'] ?? 'AcademicCredential');
        $subjectData    = trim(   $_POST['subject_data']    ?? '');

        if ($holderId === 0 || $subjectData === '') {
            $this->flashAndRedirect('error', 'Holder and subject data are required.', '/');
        }

        $stmt = Database::getInstance()->prepare(
            'SELECT u.id, u.username, u.public_key, h.full_name, h.id_number
               FROM users u JOIN holders h ON h.user_id = u.id
              WHERE u.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $holderId]);
        $holder = $stmt->fetch();
        if (!$holder) $this->flashAndRedirect('error', 'Holder not found.', '/');

        $stmt = Database::getInstance()->prepare(
            'SELECT u.username, u.public_key, u.did, i.organisation
               FROM users u JOIN issuers i ON i.user_id = u.id
              WHERE u.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $this->authUserId()]);
        $issuer = $stmt->fetch();
        if (!$issuer) $this->flashAndRedirect('error', 'Issuer profile not found.', '/');

        $subjectJson = json_decode($subjectData, true);
        if (!is_array($subjectJson)) $subjectJson = ['description' => $subjectData];

        $credentialId = 'urn:uuid:' . $this->generateUuid();
        $issuedAt     = date('c');
        $issuerDid    = $issuer['did'] ?? ('did:kazsign:' . hash('sha256', $issuer['username']));
        $holderDid    = 'did:kazsign:' . hash('sha256', $holder['username']);

        $credential = [
            '@context' => [
                'https://www.w3.org/2018/credentials/v1',
                'https://www.w3.org/2018/credentials/examples/v1',
            ],
            'id'   => $credentialId,
            'type' => ['VerifiableCredential', $credentialType],
            'issuer' => [
                'id'        => $issuerDid,
                'name'      => $issuer['organisation'],
                'publicKey' => $issuer['public_key'],
            ],
            'issuanceDate'      => $issuedAt,
            'credentialSubject' => array_merge([
                'id'       => $holderDid,
                'name'     => $holder['full_name'],
                'idNumber' => $holder['id_number'],
            ], $subjectJson),
        ];

        $jsonld   = json_encode($credential, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $fileHash = hash('sha256', $jsonld);

        $privateKey = $_SESSION['private_key'] ?? null;
        if ($privateKey === null) {
            $this->flashAndRedirect('error', 'No private key in session. Re-login with your key.', '/');
        }

        try {
            $engine    = new KazSignEngine();
            $signature = $engine->signData($jsonld, $privateKey);
        } catch (\Throwable $e) {
            error_log('[KazSign] Signing failed: ' . $e->getMessage());
            $this->flashAndRedirect('error', 'Signing failed. Check server logs.', '/');
        }

        $credential['proof'] = [
            'type'               => 'KazSignPQC2024',
            'algorithm'          => 'KAZ-SIGN-128 (Post-Quantum)',
            'created'            => $issuedAt,
            'verificationMethod' => $issuerDid . '#key-1',
            'proofValue'         => $signature,
        ];

        $signedJsonld = json_encode($credential, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

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
            $this->flashAndRedirect('error', 'Database error: ' . $e->getMessage(), '/');
        }

        $this->flashAndRedirect('success', "Credential issued to {$holder['full_name']} successfully.", '/');
    }

    // =========================================================================
    //  POST /credentials/revoke  (Issuer only)
    // =========================================================================

    public function revoke(array $params = []): void
    {
        $this->requireAuth();
        $this->requireRole('issuer');
        $this->validateCsrf();

        $credentialDbId = (int) ($_POST['credential_db_id'] ?? 0);

        if ($credentialDbId === 0) {
            $this->flashAndRedirect('error', 'Invalid credential.', '/');
        }

        $stmt = Database::getInstance()->prepare(
            'SELECT id, status, issuer_id FROM credentials WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $credentialDbId]);
        $cred = $stmt->fetch();

        if (!$cred) {
            $this->flashAndRedirect('error', 'Credential not found.', '/');
        }

        if ((int)$cred['issuer_id'] !== $this->authUserId()) {
            $this->flashAndRedirect('error', 'You can only revoke credentials you issued.', '/');
        }

        if ($cred['status'] === 'revoked') {
            $this->flashAndRedirect('error', 'This credential is already revoked.', '/');
        }

        $stmt = Database::getInstance()->prepare(
            'UPDATE credentials SET status = :status WHERE id = :id'
        );
        $stmt->execute([':status' => 'revoked', ':id' => $credentialDbId]);

        $this->flashAndRedirect('success', 'Credential has been revoked successfully.', '/');
    }

    // =========================================================================
    //  POST /credentials/verify  (Verifier only)
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

        // NEW — fetches credential first, then resolves public key from trust registry
$stmt = Database::getInstance()->prepare(
    'SELECT c.*, u.did AS issuer_did
       FROM credentials c
       JOIN users u ON u.id = c.issuer_id
      WHERE c.credential_id = :cid LIMIT 1'
);
$stmt->execute([':cid' => $credentialId]);
$cred = $stmt->fetch();

if (!$cred) { /* handle not found */ }

// Resolve issuer public key from trust registry using their DID
$regStmt = Database::getInstance()->prepare(
    'SELECT public_key, status FROM trust_registry
      WHERE did = :did LIMIT 1'
);
$regStmt->execute([':did' => $cred['issuer_did']]);
$registryEntry = $regStmt->fetch();

if (!$registryEntry) {
    // Issuer DID not found in trust registry — reject
    $this->renderVerifierDashboard([
        'type'    => 'error',
        'message' => '✗ Issuer is not in the trust registry. This credential cannot be verified.'
    ], null);
    return;
}

if ($registryEntry['status'] !== 'active') {
    // Issuer has been deregistered or suspended
    $this->renderVerifierDashboard([
        'type'    => 'error',
        'message' => '✗ The issuer of this credential is no longer a trusted issuer.'
    ], null);
    return;
}

// Use the public key from the registry (not from users table)
$cred['issuer_public_key'] = $registryEntry['public_key'];
        $stmt->execute([':cid' => $credentialId]);
        $cred = $stmt->fetch();

        if (!$cred) {
            $this->renderVerifierDashboard(
                ['type' => 'error', 'message' => 'Credential ID not found in the system.'],
                null
            );
            return;
        }

        // ── REVOKED check — stop here, no signature check needed ──────────────
        if ($cred['status'] === 'revoked') {
            $this->renderVerifierDashboard(
                ['type' => 'error', 'message' => 'This credential has been REVOKED by the issuer and is no longer valid.'],
                [
                    'credential'      => $cred,
                    'jsonld'          => json_decode($cred['jsonld'], true),
                    'hash_intact'     => false,
                    'signature_valid' => false,
                    'verified'        => false,
                    'revoked'         => true,
                ]
            );
            return;
        }

        // Verify hash
        $jsonldData = json_decode($cred['jsonld'], true);
        unset($jsonldData['proof']);
        $jsonldNoProof = json_encode($jsonldData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $computedHash  = hash('sha256', $jsonldNoProof);
        $hashIntact    = hash_equals($cred['file_hash'], $computedHash);

        // Verify signature
        $engine         = new KazSignEngine();
        $signatureValid = $engine->verifySignature(
            $jsonldNoProof,
            $cred['signature'],
            $cred['issuer_public_key']
        );

        $verified  = $hashIntact && $signatureValid;
        $newStatus = $verified ? 'verified' : 'rejected';

        $stmt = Database::getInstance()->prepare(
            'UPDATE credentials SET status = :status WHERE id = :id'
        );
        $stmt->execute([':status' => $newStatus, ':id' => $cred['id']]);
        $cred['status'] = $newStatus;

        $this->renderVerifierDashboard(
            [
                'type'    => $verified ? 'success' : 'error',
                'message' => $verified
                    ? '✓ Credential is authentic and untampered.'
                    : '✗ Verification FAILED — signature invalid or credential was modified.',
            ],
            [
                'credential'      => $cred,
                'jsonld'          => json_decode($cred['jsonld'], true),
                'hash_intact'     => $hashIntact,
                'signature_valid' => $signatureValid,
                'verified'        => $verified,
                'revoked'         => false,
            ]
        );
    }

    // =========================================================================
    //  GET /credentials/:id/show
    // =========================================================================

    public function show(array $params = []): void
    {
        $this->requireAuth();

        $id   = (int) ($params['id'] ?? 0);
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM credentials WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $cred = $stmt->fetch();

        if (!$cred) $this->flashAndRedirect('error', 'Credential not found.', '/');

        $userId = $this->authUserId();
        if ((int)$cred['holder_id'] !== $userId && (int)$cred['issuer_id'] !== $userId) {
            $this->flashAndRedirect('error', 'Access denied.', '/');
        }

        $this->render('dashboard', [
            'credentials'    => [$cred],
            'holders'        => [],
            'issuer_profile' => [],
            'flash'          => $this->consumeFlash(),
            'csrf_token'     => $this->generateCsrfToken(),
            'result'         => null,
        ]);
    }

    // =========================================================================
    //  Helpers
    // =========================================================================

    private function renderVerifierDashboard(?array $flash, ?array $result): void
    {
        $this->render('dashboard', [
            'credentials'    => [],
            'holders'        => [],
            'issuer_profile' => [],
            'flash'          => $flash,
            'csrf_token'     => $this->generateCsrfToken(),
            'result'         => $result,
        ]);
    }

    private function requireRole(string $role): void
    {
        if (($_SESSION['role'] ?? '') !== $role) {
            $this->flashAndRedirect('error', "Access denied. This action is for {$role}s only.", '/');
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