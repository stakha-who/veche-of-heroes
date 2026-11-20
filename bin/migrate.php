<?php
declare(strict_types=1);

require __DIR__ . '/../app/database.php';

$database = connectDatabase();
$database->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
    name VARCHAR(120) PRIMARY KEY,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$applied = $database->query('SELECT name FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$files = glob(__DIR__ . '/../database/migrations/*.sql');
sort($files);
$save = $database->prepare('INSERT INTO schema_migrations (name) VALUES (?)');
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $applied, true)) {
        continue;
    }
    $statements = array_filter(array_map('trim', explode(';', file_get_contents($file))));
    foreach ($statements as $statement) {
        $database->exec($statement);
    }
    $save->execute([$name]);
    echo $name . PHP_EOL;
}
