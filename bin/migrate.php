<?php
declare(strict_types=1);

require __DIR__ . '/../app/database.php';

function migrationColumnExists(PDO $database, string $migration): bool
{
    $definition = match ($migration) {
        '019_demo_clock.sql' => ['users', 'demo_days_elapsed', 'smallint unsigned', 'NO', '0'],
        '020_account_avatar.sql' => ['accounts', 'avatar_id', 'tinyint unsigned', 'YES', null],
        default => null,
    };
    if ($definition === null) {
        return false;
    }
    [$table, $column, $type, $nullable, $default] = $definition;
    $query = $database->prepare(
        'SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $query->execute([$table, $column]);
    $actual = $query->fetch(PDO::FETCH_NUM);
    if ($actual === false) {
        return false;
    }
    if ($actual !== [$type, $nullable, $default, '']) {
        throw new RuntimeException('Миграция ' . substr($migration, 0, 3) . ": неправильное определение $table.$column.");
    }
    return true;
}

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
    if (!migrationColumnExists($database, $name)) {
        $statements = array_filter(array_map('trim', explode(';', file_get_contents($file))));
        foreach ($statements as $statement) {
            $database->exec($statement);
        }
    }
    $save->execute([$name]);
    echo $name . PHP_EOL;
}
