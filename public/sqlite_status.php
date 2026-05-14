<?php
// Save as: public/sqlite_status.php
// Visit:   http://localhost/KZ-Intergration/public/sqlite_status.php

echo "<!DOCTYPE html><html><head><style>
body { font-family: monospace; background: #0f172a; color: #e2e8f0; padding: 30px; }
h2 { color: #10b981; }
h3 { color: #60a5fa; margin-top: 20px; }
.ok  { color: #10b981; }
.err { color: #f87171; }
.warn{ color: #fbbf24; }
table { border-collapse: collapse; width: 100%; margin-top: 10px; }
th { background: #1e293b; padding: 8px 12px; text-align: left; color: #94a3b8; font-size: 11px; }
td { padding: 8px 12px; border-bottom: 1px solid #1e293b; font-size: 12px; }
pre { background: #1e293b; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 11px; color: #6ee7b7; }
</style></head><body>";

echo "<h2>KAZ-SIGN SQLite Status Check</h2>";

// ── 1. Check .env ─────────────────────────────────────────────────────────────
echo "<h3>1. Environment (.env)</h3>";
$envFile = dirname(__DIR__) . '/.env';
if (!file_exists($envFile)) {
    echo "<p class='err'>✗ .env file NOT found at: $envFile</p>";
} else {
    echo "<p class='ok'>✓ .env found</p>";
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        if (in_array($key, ['DB_SKIP','DB_DRIVER','KAZSIGN_STUB'])) {
            $ok = match($key) {
                'DB_SKIP'       => $value === 'false',
                'DB_DRIVER'     => $value === 'sqlite',
                'KAZSIGN_STUB'  => true,
                default         => true,
            };
            echo "<p class='" . ($ok ? 'ok' : 'err') . "'>" . ($ok ? '✓' : '✗') . " $key = <strong>$value</strong></p>";
        }
    }
}

// ── 2. Check storage folder ───────────────────────────────────────────────────
echo "<h3>2. Storage Folder</h3>";
$storagePath = dirname(__DIR__) . '/storage';
$dbPath      = $storagePath . '/kaz_sign.db';

if (!is_dir($storagePath)) {
    echo "<p class='err'>✗ storage/ folder does NOT exist at: $storagePath</p>";
    echo "<p class='warn'>→ Create it: mkdir C:\\xampp1\\htdocs\\KZ-Intergration\\storage</p>";
} else {
    echo "<p class='ok'>✓ storage/ folder exists: $storagePath</p>";
    echo "<p class='" . (is_writable($storagePath) ? 'ok' : 'err') . "'>" 
       . (is_writable($storagePath) ? '✓ Writable' : '✗ NOT writable — give write permission') . "</p>";
}

if (!file_exists($dbPath)) {
    echo "<p class='err'>✗ kaz_sign.db does NOT exist yet — it will be created on first registration</p>";
} else {
    $size = number_format(filesize($dbPath) / 1024, 2);
    echo "<p class='ok'>✓ kaz_sign.db exists ({$size} KB)</p>";
}

// ── 3. Check PDO SQLite ───────────────────────────────────────────────────────
echo "<h3>3. PHP SQLite Support</h3>";
if (extension_loaded('pdo_sqlite')) {
    echo "<p class='ok'>✓ pdo_sqlite extension loaded</p>";
} else {
    echo "<p class='err'>✗ pdo_sqlite NOT loaded — open php.ini and uncomment: extension=pdo_sqlite</p>";
}

// ── 4. Try connecting and show tables ─────────────────────────────────────────
echo "<h3>4. Database Tables & Data</h3>";

if (!file_exists($dbPath)) {
    echo "<p class='warn'>⚠ Database file not created yet. Register a user first.</p>";
} else {
    try {
        $pdo = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Show tables
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll();
        echo "<p class='ok'>✓ Connected to SQLite successfully</p>";
        echo "<p>Tables found: <strong>" . implode(', ', array_column($tables, 'name')) . "</strong></p>";

        // Show users
        echo "<h3>5. Users Table</h3>";
        $users = $pdo->query("SELECT id, username, email, role, did, created_at FROM users")->fetchAll();
        if (empty($users)) {
            echo "<p class='warn'>⚠ No users registered yet.</p>";
        } else {
            echo "<table><thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>DID</th><th>Created</th></tr></thead><tbody>";
            foreach ($users as $u) {
                echo "<tr>
                    <td>{$u['id']}</td>
                    <td>{$u['username']}</td>
                    <td>{$u['email']}</td>
                    <td><strong>{$u['role']}</strong></td>
                    <td style='font-size:10px;color:#6ee7b7'>" . substr($u['did'] ?? '—', 0, 40) . "…</td>
                    <td>{$u['created_at']}</td>
                </tr>";
            }
            echo "</tbody></table>";
        }

        // Show credentials
        echo "<h3>6. Credentials Table</h3>";
        $creds = $pdo->query("SELECT id, issuer_id, holder_id, credential_id, status, issued_at FROM credentials")->fetchAll();
        if (empty($creds)) {
            echo "<p class='warn'>⚠ No credentials issued yet.</p>";
        } else {
            echo "<table><thead><tr><th>ID</th><th>Issuer ID</th><th>Holder ID</th><th>Credential ID</th><th>Status</th><th>Issued</th></tr></thead><tbody>";
            foreach ($creds as $c) {
                $statusColor = match($c['status']) {
                    'verified' => '#10b981',
                    'rejected' => '#f87171',
                    'revoked'  => '#f97316',
                    default    => '#60a5fa',
                };
                echo "<tr>
                    <td>{$c['id']}</td>
                    <td>{$c['issuer_id']}</td>
                    <td>{$c['holder_id']}</td>
                    <td style='font-size:10px;color:#6ee7b7'>" . substr($c['credential_id'], 0, 30) . "…</td>
                    <td style='color:{$statusColor}'><strong>{$c['status']}</strong></td>
                    <td>{$c['issued_at']}</td>
                </tr>";
            }
            echo "</tbody></table>";
        }

        // Show role tables
        foreach (['issuers' => 'organisation', 'holders' => 'full_name', 'verifiers' => 'organisation'] as $table => $col) {
            echo "<h3>7. $table Table</h3>";
            $rows = $pdo->query("SELECT * FROM $table")->fetchAll();
            if (empty($rows)) {
                echo "<p class='warn'>⚠ No entries in $table yet.</p>";
            } else {
                echo "<table><thead><tr><th>ID</th><th>User ID</th><th>" . ucfirst($col) . "</th><th>Created</th></tr></thead><tbody>";
                foreach ($rows as $r) {
                    echo "<tr>
                        <td>{$r['id']}</td>
                        <td>{$r['user_id']}</td>
                        <td>{$r[$col]}</td>
                        <td>{$r['created_at']}</td>
                    </tr>";
                }
                echo "</tbody></table>";
            }
        }

    } catch (Exception $e) {
        echo "<p class='err'>✗ SQLite error: " . $e->getMessage() . "</p>";
    }
}

echo "<h3>Done</h3>";
echo "<p><a href='../public/register' style='color:#10b981'>→ Go to Register</a> &nbsp; 
      <a href='../public/' style='color:#10b981'>→ Go to Dashboard</a></p>";
echo "</body></html>";
?>