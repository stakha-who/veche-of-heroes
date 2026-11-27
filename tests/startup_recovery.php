<?php
declare(strict_types=1);

require __DIR__ . '/../app/database.php';

if (getenv('APP_TEST') !== '1' || getenv('APP_DEMO') !== '1') {
    fwrite(STDERR, "Запусти проверку на новой тестовой базе с APP_TEST=1 и APP_DEMO=1.\n");
    exit(2);
}

$database = connectDatabase();
if ($database->query('SHOW TABLES')->fetch() !== false) {
    fwrite(STDERR, "Для проверки восстановления нужна пустая тестовая база.\n");
    exit(2);
}

function runStartupScript(string $file): array
{
    $process = proc_open([PHP_BINARY, __DIR__ . '/../bin/' . $file], [1 => ['pipe', 'w'], 2 => ['redirect', 1]], $pipes);
    if ($process === false) {
        throw new RuntimeException('Не удалось запустить ' . $file);
    }
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    return [proc_close($process), $output];
}

function assertStartupState(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

[$status, $output] = runStartupScript('migrate.php');
assertStartupState($status === 0, $output);

// Воспроизводим сбой после изменения схемы и до записи в журнал.
$database->exec("DELETE FROM schema_migrations WHERE name IN ('017_item_catalog.sql', '019_demo_clock.sql')");
$database->exec("DELETE FROM item_catalog WHERE name = 'Ратный клинок'");
$database->exec('ALTER TABLE users MODIFY COLUMN demo_days_elapsed SMALLINT NOT NULL DEFAULT 0');
[$status, $output] = runStartupScript('migrate.php');
assertStartupState($status !== 0 && str_contains($output, 'Миграция 019:'), '019 должна отклонить столбец с другим типом.');
$applied = $database->query('SELECT name FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
assertStartupState(in_array('017_item_catalog.sql', $applied, true), '017 отсутствует в журнале после повторного выполнения.');
assertStartupState(!in_array('019_demo_clock.sql', $applied, true), '019 попала в журнал после ошибки.');
assertStartupState((int) $database->query('SELECT COUNT(*) FROM item_catalog')->fetchColumn() === 12,
    'В каталоге должны быть все 12 стартовых позиций.');
assertStartupState($database->query("SELECT name FROM item_catalog WHERE name = 'Ратный клинок'")->fetchColumn() !== false,
    '017 не восстановила отсутствующий предмет.');
echo "OK   017 заполняет уже существующий каталог\n";

$database->exec('ALTER TABLE users MODIFY COLUMN demo_days_elapsed SMALLINT UNSIGNED NOT NULL DEFAULT 0');
[$status, $output] = runStartupScript('migrate.php');
assertStartupState($status === 0, $output);
assertStartupState($database->query("SELECT name FROM schema_migrations WHERE name = '019_demo_clock.sql'")->fetchColumn() !== false,
    '019 не отметила уже созданный корректный столбец.');
echo "OK   019 проверяет определение столбца и завершает повторный запуск\n";

// Яромир записывается первым. Отказ на втором предмете проверяет откат всего профиля.
$database->exec("ALTER TABLE account_items ADD CONSTRAINT test_initial_items CHECK (item_name <> 'Ратный клинок')");
try {
    [$status, $output] = runStartupScript('seed.php');
    assertStartupState($status !== 0 && str_contains($output, 'test_initial_items'), 'Ожидался сбой при записи предмета.');
    assertStartupState((int) $database->query('SELECT COUNT(*) FROM accounts')->fetchColumn() === 0,
        'После сбоя в базе остался незавершённый профиль.');
    assertStartupState((int) $database->query('SELECT COUNT(*) FROM account_items')->fetchColumn() === 0,
        'После сбоя в базе остался первый предмет.');
} finally {
    $database->exec('ALTER TABLE account_items DROP CHECK test_initial_items');
}

[$status, $output] = runStartupScript('seed.php');
assertStartupState($status === 0, $output);
$yaromir = $database->query("SELECT id, gold FROM accounts WHERE player_name = 'Яромир'")->fetch();
assertStartupState($yaromir !== false && (int) $yaromir['gold'] === 3000, 'Повторный запуск не восстановил кошелёк Яромира.');
$items = $database->prepare('SELECT item_name FROM account_items WHERE account_id = ? AND quantity = 1 ORDER BY item_name');
$items->execute([$yaromir['id']]);
assertStartupState($items->fetchAll(PDO::FETCH_COLUMN) === ['Обережный пояс', 'Ратный клинок'], 'Яромир получил неполный инвентарь.');
$rank = $database->prepare('SELECT confirmed_rank, rank_points FROM rankings WHERE account_id = ? AND closed_at IS NULL');
$rank->execute([$yaromir['id']]);
$ranking = $rank->fetch();
assertStartupState($ranking !== false && $ranking['confirmed_rank'] === 'Изумруд II' && (int) $ranking['rank_points'] === 45,
    'После повтора не восстановлен стартовый ранг Яромира.');
echo "OK   Повторная загрузка создаёт полный и согласованный демопрофиль\n";
