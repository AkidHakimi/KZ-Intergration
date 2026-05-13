<?php
// What MySQL is PHP actually connecting to?
echo "<h2>PHP MySQL Info</h2>";
echo "PHP version: " . phpversion() . "<br>";

$connections = [
    'localhost:3306'   => ['localhost', 3306],
    '127.0.0.1:3306'  => ['127.0.0.1', 3306],
    'localhost:3307'   => ['localhost', 3307],
    '127.0.0.1:3307'  => ['127.0.0.1', 3307],
];

foreach ($connections as $label => [$host, $port]) {
    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};charset=utf8mb4",
            'root', '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $dbs = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
        echo "<p style='color:green'><strong>{$label} — CONNECTED</strong><br>";
        echo "Databases: " . implode(', ', $dbs) . "</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'><strong>{$label} — FAILED:</strong> " . $e->getMessage() . "</p>";
    }
}