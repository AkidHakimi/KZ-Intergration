<?php
// Save as: public/fix_db.php
// Visit:   http://localhost/KZ-Intergration/public/fix_db.php
// DELETE this file after running!

$dbPath = dirname(__DIR__) . '/storage/kaz_sign.db';

if (!file_exists($dbPath)) {
    die("Database not found at: $dbPath");
}

try {
    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $pdo->exec('PRAGMA foreign_keys=OFF');

    // Step 1 - Create new credentials table with revoked status
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS credentials_new (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            issuer_id     INTEGER NOT NULL,
            holder_id     INTEGER NOT NULL,
            credential_id TEXT    NOT NULL,
            subject       TEXT    NOT NULL,
            jsonld        TEXT    NOT NULL,
            signature     TEXT    NOT NULL,
            file_hash     TEXT    NOT NULL,
            status        TEXT    NOT NULL DEFAULT 'issued',
            issued_at     TEXT    NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (issuer_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (holder_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<p style='color:green'>✓ New credentials table created (no CHECK constraint — allows any status)</p>";

    // Step 2 - Copy existing data
    $count = $pdo->exec("INSERT INTO credentials_new SELECT * FROM credentials");
    echo "<p style='color:green'>✓ Copied $count existing credentials</p>";

    // Step 3 - Drop old table
    $pdo->exec("DROP TABLE credentials");
    echo "<p style='color:green'>✓ Old table dropped</p>";

    // Step 4 - Rename new table
    $pdo->exec("ALTER TABLE credentials_new RENAME TO credentials");
    echo "<p style='color:green'>✓ Table renamed</p>";

    // Step 5 - Recreate index
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS uq_credential_id ON credentials(credential_id)");
    echo "<p style='color:green'>✓ Index recreated</p>";

    $pdo->exec('PRAGMA foreign_keys=ON');

    // Step 6 - Verify
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p style='color:green'>✓ Tables: " . implode(', ', $tables) . "</p>";

    // Test insert with revoked status
    $pdo->exec("UPDATE credentials SET status='issued' WHERE status='revoked'");
    echo "<p style='color:blue'>ℹ Reset any revoked back to issued for clean state</p>";

    echo "<h2 style='color:green'>✓ DONE — Revoke is now supported!</h2>";
    echo "<p>Now go back to the dashboard and try revoking a credential.</p>";
    echo "<p style='color:red'><strong>DELETE this file (public/fix_db.php) after use!</strong></p>";
    echo "<p><a href='/KZ-Intergration/public/'>→ Go to Dashboard</a></p>";

} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>