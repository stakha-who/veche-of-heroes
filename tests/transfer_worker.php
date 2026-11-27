<?php
declare(strict_types=1);

if (getenv('APP_TEST') !== '1' || $argc !== 7) {
    exit(2);
}

require __DIR__ . '/../app/database.php';
require __DIR__ . '/../app/models/transfers.php';
require __DIR__ . '/../app/models/seasons.php';
require __DIR__ . '/../app/models/rankings.php';
require __DIR__ . '/../app/models/transfer_service.php';

$database = connectDatabase();
$database->exec('SET SESSION innodb_lock_wait_timeout = 12');
echo $database->query('SELECT CONNECTION_ID()')->fetchColumn() . PHP_EOL;

try {
    performTransfer($database, (int) $argv[1], (int) $argv[2], (int) $argv[3], 0,
        (int) $argv[4], $argv[5], new DateTimeImmutable($argv[6]));
    echo 'ok' . PHP_EOL;
} catch (DomainException $exception) {
    $message = $exception->getMessage();
    echo (str_contains($message, 'Условия устарели') ? 'stale' : $message) . PHP_EOL;
}
