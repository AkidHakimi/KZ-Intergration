<?php
declare(strict_types=1);
namespace KazSign\Models;

use KazSign\Core\Database;

/**
 * Issuer — an entity that signs and issues documents.
 * Linked 1-to-1 with users (role = 'issuer').
 */
class Issuer
{
    public static function findByUserId(int $userId): ?array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT i.*, u.username, u.email, u.public_key
               FROM issuers i
               JOIN users u ON u.id = i.user_id
              WHERE i.user_id = :uid LIMIT 1'
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(int $userId, string $organisation): int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO issuers (user_id, organisation) VALUES (:uid, :org)'
        );
        $stmt->execute([':uid' => $userId, ':org' => $organisation]);
        return (int) $db->getConnection()->lastInsertId();
    }

    /** All documents issued by this user. */
    public static function documents(int $userId): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM documents WHERE user_id = :uid ORDER BY created_at DESC'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }
}
