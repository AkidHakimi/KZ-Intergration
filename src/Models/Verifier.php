<?php
declare(strict_types=1);
namespace KazSign\Models;

use KazSign\Core\Database;

/**
 * Verifier — an entity that checks document authenticity.
 * Linked 1-to-1 with users (role = 'verifier').
 */
class Verifier
{
    public static function findByUserId(int $userId): ?array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT v.*, u.username, u.email, u.public_key
               FROM verifiers v
               JOIN users u ON u.id = v.user_id
              WHERE v.user_id = :uid LIMIT 1'
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(int $userId, string $organisation): int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO verifiers (user_id, organisation) VALUES (:uid, :org)'
        );
        $stmt->execute([':uid' => $userId, ':org' => $organisation]);
        return (int) $db->getConnection()->lastInsertId();
    }

    /** Look up public key of the document owner for verification. */
    public static function fetchPublicKey(int $documentOwnerUserId): ?string
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT public_key FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $documentOwnerUserId]);
        $row = $stmt->fetch();
        return $row ? $row['public_key'] : null;
    }
}
