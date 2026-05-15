<?php
declare(strict_types=1);
namespace KazSign\Controllers;

use KazSign\Core\Controller;
use KazSign\Core\Database;

final class TrustRegistryController extends Controller
{
    // GET /trust-registry — list all trusted issuers (public)
    public function index(array $params = []): void
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT did, issuer_name, public_key, status, registered_at
               FROM trust_registry
              WHERE status = :status
           ORDER BY registered_at DESC'
        );
        $stmt->execute([':status' => 'active']);
        $entries = $stmt->fetchAll();

        header('Content-Type: application/json');
        echo json_encode([
            'registry'  => 'KAZ-SIGN Trust Registry',
            'algorithm' => 'KAZ-SIGN-128 (Post-Quantum)',
            'issuers'   => $entries,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // GET /trust-registry/:did — resolve a specific DID (public)
    public function resolve(array $params = []): void
    {
        $did  = urldecode($params['did'] ?? '');
        $stmt = Database::getInstance()->prepare(
            'SELECT did, issuer_name, public_key, status, registered_at
               FROM trust_registry WHERE did = :did LIMIT 1'
        );
        $stmt->execute([':did' => $did]);
        $entry = $stmt->fetch();

        header('Content-Type: application/json');
        if (!$entry) {
            http_response_code(404);
            echo json_encode(['error' => 'DID not found in trust registry']);
        } else {
            echo json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        exit;
    }

    // POST /trust-registry/deactivate — remove an issuer from trust
public function deactivate(array $params = []): void
{
    $this->requireAuth();
    $userId = $this->authUserId();

    $stmt = Database::getInstance()->prepare(
        'UPDATE trust_registry SET status = :status
          WHERE user_id = :uid'
    );
    $stmt->execute([':status' => 'revoked', ':uid' => $userId]);

    header('Content-Type: application/json');
    echo json_encode(['message' => 'Issuer removed from trust registry']);
    exit;
}

}