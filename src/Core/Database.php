<?php

declare(strict_types=1);

namespace KazSign\Core;

use PDO;
use PDOException;

/**
 * Database – Singleton PDO wrapper.
 *
 * Three modes:
 *   DB_DRIVER=sqlite → SQLite file in storage/kaz_sign.db
 *   DB_DRIVER=mysql  → MySQL via XAMPP
 *   DB_SKIP=true     → Session only (demo)
 */
final class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection            = null;
    private bool $stubMode              = false;
    private int  $lastId                = 0;

    private const PDO_OPTIONS = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    private function __construct()
    {
        if (getenv('DB_SKIP') === 'true') {
            $this->stubMode = true;
            $this->initSessionStore();
            return;
        }

        $driver = getenv('DB_DRIVER') ?: 'sqlite';

        try {
            if ($driver === 'sqlite') {
                $this->connectSQLite();
            } else {
                $this->connectMySQL();
            }
        } catch (\Throwable $e) {
            error_log('[KazSign] DB connect failed: ' . $e->getMessage());
            $this->stubMode = true;
            $this->initSessionStore();
        }
    }

    private function __clone() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // =========================================================================
    //  SQLite connection
    // =========================================================================

    private function connectSQLite(): void
    {
        // Walk up from src/Core/ → src/ → project root
        $root    = dirname(__DIR__, 2);
        $storage = $root . DIRECTORY_SEPARATOR . 'storage';

        // Create storage folder if missing
        if (!is_dir($storage)) {
            if (!mkdir($storage, 0755, true)) {
                throw new \RuntimeException(
                    "Cannot create storage folder at: {$storage}\n" .
                    "Please create it manually: mkdir storage"
                );
            }
        }

        if (!is_writable($storage)) {
            throw new \RuntimeException(
                "Storage folder is not writable: {$storage}\n" .
                "Please give write permission to this folder."
            );
        }

        $dbFile    = $storage . DIRECTORY_SEPARATOR . 'kaz_sign.db';
        $isNewFile = !file_exists($dbFile);

        $this->connection = new PDO(
            'sqlite:' . $dbFile,
            null,
            null,
            self::PDO_OPTIONS
        );

        // Enable WAL mode and foreign keys
        $this->connection->exec('PRAGMA journal_mode=WAL');
        $this->connection->exec('PRAGMA foreign_keys=ON');

        // Auto-create schema on first run
        if ($isNewFile) {
            $this->createSQLiteSchema();
            error_log('[KazSign] SQLite database created at: ' . $dbFile);
        } else {
            // Always ensure all tables exist (safe to run on existing db)
            $this->createSQLiteSchema();
        }
    }

    private function createSQLiteSchema(): void
    {
        // Users table
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS users (
                id          INTEGER  PRIMARY KEY AUTOINCREMENT,
                username    TEXT     NOT NULL,
                email       TEXT     NOT NULL,
                role        TEXT     NOT NULL DEFAULT 'holder',
                password    TEXT     NOT NULL,
                public_key  TEXT     NOT NULL,
                did         TEXT,
                created_at  TEXT     NOT NULL DEFAULT (datetime('now')),
                updated_at  TEXT     NOT NULL DEFAULT (datetime('now'))
            )
        ");

        // Unique indexes
        $this->connection->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_users_username ON users(username)");
        $this->connection->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_users_email    ON users(email)");

        // Issuers table
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS issuers (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id      INTEGER NOT NULL,
                organisation TEXT    NOT NULL,
                created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        $this->connection->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_issuers_user ON issuers(user_id)");

        // Holders table
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS holders (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id     INTEGER NOT NULL,
                full_name   TEXT    NOT NULL,
                id_number   TEXT    NOT NULL,
                created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        $this->connection->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_holders_user ON holders(user_id)");

        // Verifiers table
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS verifiers (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id      INTEGER NOT NULL,
                organisation TEXT    NOT NULL,
                created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        $this->connection->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_verifiers_user ON verifiers(user_id)");

        // Credentials table
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS credentials (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                issuer_id     INTEGER NOT NULL,
                holder_id     INTEGER NOT NULL,
                credential_id TEXT    NOT NULL,
                subject       TEXT    NOT NULL,
                jsonld        TEXT    NOT NULL,
                signature     TEXT    NOT NULL,
                file_hash     TEXT    NOT NULL,
                status        TEXT    NOT NULL DEFAULT 'issued'
                CHECK(status IN ('issued','verified','rejected','revoked')),
                issued_at     TEXT    NOT NULL DEFAULT (datetime('now')),
                FOREIGN KEY (issuer_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (holder_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        $this->connection->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_credential_id ON credentials(credential_id)");

        // Documents table
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS documents (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id     INTEGER NOT NULL,
                file_name   TEXT    NOT NULL,
                file_hash   TEXT    NOT NULL,
                signature   TEXT    NOT NULL,
                status      TEXT    NOT NULL DEFAULT 'pending',
                created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }

    // =========================================================================
    //  MySQL connection
    // =========================================================================

    private function connectMySQL(): void
    {
        $host    = getenv('DB_HOST')    ?: 'localhost';
        $port    = getenv('DB_PORT')    ?: '3306';
        $dbname  = getenv('DB_NAME')    ?: 'a200368';
        $user    = getenv('DB_USER')    ?: 'root';
        $pass    = getenv('DB_PASS')    ?: '';
        $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        $options = self::PDO_OPTIONS + [
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'",
        ];

        $this->connection = new PDO($dsn, $user, $pass, $options);
    }

    // =========================================================================
    //  Public API
    // =========================================================================

    public function getConnection(): PDO
    {
        if ($this->stubMode || $this->connection === null) {
            throw new \RuntimeException('No DB connection — running in stub mode.');
        }
        return $this->connection;
    }

    public function isStubMode(): bool
    {
        return $this->stubMode;
    }

    public function lastInsertId(): int
    {
        if ($this->stubMode) return $this->lastId;
        return (int) $this->connection->lastInsertId();
    }

    public function prepare(string $sql): \PDOStatement|StubStatement
    {
        if ($this->stubMode) return new StubStatement($sql, $this);
        return $this->connection->prepare($sql);
    }

    public function setLastInsertId(int $id): void
    {
        $this->lastId = $id;
    }

    // =========================================================================
    //  Session stub
    // =========================================================================

    private function initSessionStore(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!isset($_SESSION['__db'])) {
            $_SESSION['__db'] = [
                'users'       => [],
                'credentials' => [],
                'documents'   => [],
                '__next_id'   => ['users' => 1, 'credentials' => 1, 'documents' => 1],
            ];
        }
    }
}

// =============================================================================
//  StubStatement
// =============================================================================

class StubStatement
{
    private string   $sql;
    private Database $db;
    private array    $params = [];
    private array    $rows   = [];

    public function __construct(string $sql, Database $db)
    {
        $this->sql = trim($sql);
        $this->db  = $db;
    }

    public function execute(array $params = []): bool
    {
        $this->params = [];
        foreach ($params as $k => $v) {
            $this->params[ltrim($k, ':')] = $v;
        }

        $sql   = strtolower($this->sql);
        $store = &$_SESSION['__db'];

        if (str_starts_with($sql, 'insert into')) {
            preg_match('/insert into\s+(\w+)/i', $this->sql, $m);
            $table = $m[1] ?? '';
            if (!isset($store[$table]))              $store[$table] = [];
            if (!isset($store['__next_id'][$table])) $store['__next_id'][$table] = 1;
            $id  = $store['__next_id'][$table];
            $row = array_merge(['id' => $id], $this->params);
            $row['created_at'] = date('Y-m-d H:i:s');
            if ($table === 'users') {
                foreach ($store[$table] as $e) {
                    if (($e['username'] ?? '') === ($this->params['username'] ?? ''))
                        throw new \PDOException("UNIQUE constraint failed: users.username");
                    if (($e['email'] ?? '') === ($this->params['email'] ?? ''))
                        throw new \PDOException("UNIQUE constraint failed: users.email");
                }
            }
            $store[$table][] = $row;
            $store['__next_id'][$table]++;
            $this->db->setLastInsertId($id);
            $this->rows = [];
            return true;
        }

        if (str_starts_with($sql, 'select')) {
            preg_match('/from\s+(\w+)/i', $this->sql, $m);
            $table = $m[1] ?? '';
            $rows  = $store[$table] ?? [];
            if (isset($this->params['id']))   $rows = array_values(array_filter($rows, fn($r) => (int)($r['id'] ?? 0) === (int)$this->params['id']));
            if (isset($this->params['uid']))  $rows = array_values(array_filter($rows, fn($r) => (int)($r['user_id'] ?? 0) === (int)$this->params['uid']));
            if (isset($this->params['u']))    $rows = array_values(array_filter($rows, fn($r) => ($r['username'] ?? '') === $this->params['u']));
            if (isset($this->params['cid']))  $rows = array_values(array_filter($rows, fn($r) => ($r['credential_id'] ?? '') === $this->params['cid']));
            if (str_contains($sql, 'order by')) usort($rows, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
            $this->rows = $rows;
            return true;
        }

        if (str_starts_with($sql, 'update')) {
            preg_match('/update\s+(\w+)/i', $this->sql, $m);
            $table = $m[1] ?? '';
            foreach ($store[$table] as &$row) {
                if (isset($this->params['id']) && (int)($row['id'] ?? 0) === (int)$this->params['id']) {
                    foreach ($this->params as $k => $v) {
                        if ($k !== 'id') $row[$k] = $v;
                    }
                }
            }
            unset($row);
            return true;
        }

        return true;
    }

    public function fetch(int $mode = \PDO::FETCH_ASSOC): array|false
    {
        return array_shift($this->rows) ?? false;
    }

    public function fetchAll(int $mode = \PDO::FETCH_ASSOC): array
    {
        return $this->rows;
    }
}