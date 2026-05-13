<?php
declare(strict_types=1);
namespace KazSign\Models;

use KazSign\Core\Database;

/**
 * Holder — the person a credential/document is issued about.
 * Linked 1-to-1 with users (role = 'holder').
 */
class Holder
{
    public static function findByUserId(int $userId): ?array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT h.*, u.username, u.email, u.public_key
               FROM holders h
               JOIN users u ON u.id = h.user_id
              WHERE h.user_id = :uid LIMIT 1'
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(int $userId, string $fullName, string $idNumber): int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO holders (user_id, full_name, id_number)
             VALUES (:uid, :name, :idn)'
        );
        $stmt->execute([':uid' => $userId, ':name' => $fullName, ':idn' => $idNumber]);
        return (int) $db->getConnection()->lastInsertId();
    }

    /** All documents owned by this holder. */
    public static function documents(int $userId): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT * FROM documents WHERE user_id = :uid ORDER BY created_at DESC'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }
}
