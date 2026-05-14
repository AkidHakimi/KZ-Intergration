<?php

declare(strict_types=1);

namespace KazSign\Core;

use PDO;
use PDOException;

/**
 * Database – Singleton PDO wrapper.
 *
 * Supports THREE modes depending on .env settings:
 *
 *   1. SQLite  (DB_DRIVER=sqlite) — single file, no server needed  ← NEW
 *   2. MySQL   (DB_DRIVER=mysql)  — uses phpMyAdmin / XAMPP MySQL
 *   3. Session (DB_SKIP=true)     — browser session only, no persistence
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
        // ── Session stub mode ─────────────────────────────────────────────────
        if (getenv('DB_SKIP') === 'true') {
            $this->stubMode = true;
            $this->initSessionStore();
            return;
        }

        $driver = getenv('DB_DRIVER') ?: 'mysql';

        try {
            if ($driver === 'sqlite') {
                $this->connectSQLite();
            } else {
                $this->connectMySQL();
            }
        } catch (PDOException $e) {
            error_log('[KazSign] DB unavailable, using session fallback: ' . $e->getMessage());
            $this->stubMode = true;
            $this->initSessionStore();
        }
    }

    // =========================================================================
    //  Connection methods
    // =========================================================================

    private function connectSQLite(): void
    {
        // SQLite file lives at: project_root/storage/kaz_sign.db
        $root    = dirname(__DIR__, 2); // go up from src/Core/ to project root
        $storage = $root . DIRECTORY_SEPARATOR . 'storage';

        // Create storage folder if it doesn't exist
        if (!is_dir($storage)) {
            mkdir($storage, 0755, true);
        }

        $dbFile = $storage . DIRECTORY_SEPARATOR . 'kaz_sign.db';
        $exists = file_exists($dbFile);

        $this->connection = new PDO('sqlite:' . $dbFile, null, null, self::PDO_OPTIONS);

        // Enable WAL mode for better concurrent access
        $this->connection->exec('PRAGMA journal_mode=WAL');
        $this->connection->exec('PRAGMA foreign_keys=ON');

        // Create tables if this is a fresh database
        if (!$exists) {
            $this->createSQLiteSchema();
        }

        error_log('[KazSign] Connected to SQLite: ' . $dbFile);
    }

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
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'",
        ];

        $this->connection = new PDO($dsn, $user, $pass, $options);
    }

    // =========================================================================
    //  SQLite schema (auto-created on first run)
    // =========================================================================

    private function createSQLiteSchema(): void
    {
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS users (
                id          INTEGER  PRIMARY KEY AUTOINCREMENT,
                username    TEXT     NOT NULL UNIQUE,
                email       TEXT     NOT NULL UNIQUE,
                role        TEXT     NOT NULL DEFAULT 'holder'
                                     CHECK(role IN ('issuer','holder','verifier')),
                password    TEXT     NOT NULL,
                public_key  TEXT     NOT NULL,
                did         TEXT     NULL,
                created_at  DATETIME NOT NULL DEFAULT (datetime('now')),
                updated_at  DATETIME NOT NULL DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS issuers (
                id           INTEGER  PRIMARY KEY AUTOINCREMENT,
                user_id      INTEGER  NOT NULL UNIQUE
                                      REFERENCES users(id) ON DELETE CASCADE,
                organisation TEXT     NOT NULL,
                created_at   DATETIME NOT NULL DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS holders (
                id          INTEGER  PRIMARY KEY AUTOINCREMENT,
                user_id     INTEGER  NOT NULL UNIQUE
                                     REFERENCES users(id) ON DELETE CASCADE,
                full_name   TEXT     NOT NULL,
                id_number   TEXT     NOT NULL,
                created_at  DATETIME NOT NULL DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS verifiers (
                id           INTEGER  PRIMARY KEY AUTOINCREMENT,
                user_id      INTEGER  NOT NULL UNIQUE
                                      REFERENCES users(id) ON DELETE CASCADE,
                organisation TEXT     NOT NULL,
                created_at   DATETIME NOT NULL DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS credentials (
                id            INTEGER  PRIMARY KEY AUTOINCREMENT,
                issuer_id     INTEGER  NOT NULL
                                       REFERENCES users(id) ON DELETE CASCADE,
                holder_id     INTEGER  NOT NULL
                                       REFERENCES users(id) ON DELETE CASCADE,
                credential_id TEXT     NOT NULL UNIQUE,
                subject       TEXT     NOT NULL,
                jsonld        TEXT     NOT NULL,
                signature     TEXT     NOT NULL,
                file_hash     TEXT     NOT NULL,
                status        TEXT     NOT NULL DEFAULT 'issued'
                                       CHECK(status IN ('issued','verified','rejected')),
                issued_at     DATETIME NOT NULL DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS documents (
                id          INTEGER  PRIMARY KEY AUTOINCREMENT,
                user_id     INTEGER  NOT NULL
                                     REFERENCES users(id) ON DELETE CASCADE,
                file_name   TEXT     NOT NULL,
                file_hash   TEXT     NOT NULL,
                signature   TEXT     NOT NULL,
                status      TEXT     NOT NULL DEFAULT 'pending'
                                     CHECK(status IN ('pending','signed','verified','rejected')),
                created_at  DATETIME NOT NULL DEFAULT (datetime('now'))
            );
        ");

        error_log('[KazSign] SQLite schema created successfully.');
    }

    // =========================================================================
    //  Public API (same interface as before — no other files need changing)
    // =========================================================================

    private function __clone() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        if ($this->stubMode || $this->connection === null) {
            throw new \RuntimeException('No real DB connection — running in stub mode.');
        }
        return $this->connection;
    }

    public function isStubMode(): bool
    {
        return $this->stubMode;
    }

    public function lastInsertId(): int
    {
        if ($this->stubMode) {
            return $this->lastId;
        }
        return (int) $this->connection->lastInsertId();
    }

    public function prepare(string $sql): \PDOStatement|StubStatement
    {
        if ($this->stubMode) {
            return new StubStatement($sql, $this);
        }
        return $this->connection->prepare($sql);
    }

    public function setLastInsertId(int $id): void
    {
        $this->lastId = $id;
    }

    // =========================================================================
    //  Session stub (unchanged from before)
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
//  StubStatement (session fallback — unchanged)
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
                        throw new \PDOException("Duplicate entry '1062' username");
                    if (($e['email'] ?? '') === ($this->params['email'] ?? ''))
                        throw new \PDOException("Duplicate entry '1062' email");
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
            if (isset($this->params['id']))  $rows = array_values(array_filter($rows, fn($r) => (int)($r['id'] ?? 0) === (int)$this->params['id']));
            if (isset($this->params['uid'])) $rows = array_values(array_filter($rows, fn($r) => (int)($r['user_id'] ?? 0) === (int)$this->params['uid']));
            if (isset($this->params['u']))   $rows = array_values(array_filter($rows, fn($r) => ($r['username'] ?? '') === $this->params['u']));
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