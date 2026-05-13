<?php

declare(strict_types=1);

namespace KazSign\Core;

use PDO;
use PDOException;

/**
 * Database – Singleton PDO wrapper.
 *
 * Automatically falls back to a session-based store when MySQL is
 * unavailable (DB_SKIP=true in .env, or connection fails).
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
        PDO::ATTR_PERSISTENT         => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'",
    ];

    private function __construct()
    {
        if (getenv('DB_SKIP') === 'true') {
            $this->stubMode = true;
            $this->initSessionStore();
            return;
        }

        $host    = getenv('DB_HOST')    ?: 'localhost';
        $port    = getenv('DB_PORT')    ?: '3306';
        $dbname  = getenv('DB_NAME')    ?: 'a200368';
        $user    = getenv('DB_USER')    ?: 'root';
        $pass    = getenv('DB_PASS')    ?: '';
        $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        try {
            $this->connection = new PDO($dsn, $user, $pass, self::PDO_OPTIONS);
        } catch (PDOException $e) {
            error_log('[KazSign] DB unavailable, using session fallback: ' . $e->getMessage());
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

    /** Works in both real MySQL mode and stub mode. */
    public function lastInsertId(): int
    {
        if ($this->stubMode) {
            return $this->lastId;
        }
        return (int) $this->connection->lastInsertId();
    }

    /** Prepare a statement — returns a real PDOStatement or StubStatement. */
    public function prepare(string $sql): \PDOStatement|StubStatement
    {
        if ($this->stubMode) {
            return new StubStatement($sql, $this);
        }
        return $this->connection->prepare($sql);
    }

    /** Called by StubStatement after an INSERT to record the new ID. */
    public function setLastInsertId(int $id): void
    {
        $this->lastId = $id;
    }

    private function initSessionStore(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!isset($_SESSION['__db'])) {
            $_SESSION['__db'] = [
                'users'     => [],
                'documents' => [],
                '__next_id' => ['users' => 1, 'documents' => 1],
            ];
        }
    }
}

// =============================================================================
//  StubStatement — session-backed PDOStatement replacement
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

        // INSERT
        if (str_starts_with($sql, 'insert into')) {
            preg_match('/insert into\s+(\w+)/i', $this->sql, $m);
            $table = $m[1] ?? '';

            if (!isset($store[$table]))              $store[$table] = [];
            if (!isset($store['__next_id'][$table])) $store['__next_id'][$table] = 1;

            $id  = $store['__next_id'][$table];
            $row = array_merge(['id' => $id], $this->params);
            $row['created_at'] = $row['created_at'] ?? date('Y-m-d H:i:s');

            // Unique check for users
            if ($table === 'users') {
                foreach ($store[$table] as $existing) {
                    if (isset($this->params['username']) &&
                        ($existing['username'] ?? '') === $this->params['username']) {
                        throw new \PDOException("Duplicate entry '1062' username");
                    }
                    if (isset($this->params['email']) &&
                        ($existing['email'] ?? '') === $this->params['email']) {
                        throw new \PDOException("Duplicate entry '1062' email");
                    }
                }
            }

            $store[$table][] = $row;
            $store['__next_id'][$table]++;
            $this->db->setLastInsertId($id);
            $this->rows = [];
            return true;
        }

        // SELECT
        if (str_starts_with($sql, 'select')) {
            preg_match('/from\s+(\w+)/i', $this->sql, $m);
            $table = $m[1] ?? '';
            $rows  = $store[$table] ?? [];

            if (isset($this->params['id'])) {
                $rows = array_values(array_filter($rows,
                    fn($r) => (int)($r['id'] ?? 0) === (int)$this->params['id']
                ));
            }
            if (isset($this->params['uid'])) {
                $rows = array_values(array_filter($rows,
                    fn($r) => (int)($r['user_id'] ?? 0) === (int)$this->params['uid']
                ));
            }
            if (isset($this->params['u'])) {
                $rows = array_values(array_filter($rows,
                    fn($r) => ($r['username'] ?? '') === $this->params['u']
                ));
            }
            if (str_contains($sql, 'order by created_at desc')) {
                usort($rows, fn($a, $b) =>
                    strcmp($b['created_at'] ?? '', $a['created_at'] ?? '')
                );
            }

            $this->rows = $rows;
            return true;
        }

        // UPDATE
        if (str_starts_with($sql, 'update')) {
            preg_match('/update\s+(\w+)/i', $this->sql, $m);
            $table = $m[1] ?? '';

            foreach ($store[$table] as &$row) {
                if (isset($this->params['id']) &&
                    (int)($row['id'] ?? 0) === (int)$this->params['id']) {
                    if (isset($this->params['status'])) {
                        $row['status'] = $this->params['status'];
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
