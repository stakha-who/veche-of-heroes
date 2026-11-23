<?php
declare(strict_types=1);

require __DIR__ . '/../app/database.php';
require __DIR__ . '/../app/models/transfers.php';
require __DIR__ . '/../app/models/seasons.php';
require __DIR__ . '/../app/models/rankings.php';
require __DIR__ . '/../app/models/transfer_service.php';

if (getenv('APP_TEST') !== '1') {
    fwrite(STDERR, "Запускай проверки в отдельном окружении с APP_TEST=1.\n");
    exit(2);
}

$database = connectDatabase();
$regionIds = $database->query('SELECT code, id FROM regions')->fetchAll(PDO::FETCH_KEY_PAIR);
$now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
$owner = $database->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
$owner->execute(['test-' . bin2hex(random_bytes(8)), password_hash('test', PASSWORD_DEFAULT)]);
$ownerId = (int) $database->lastInsertId();
$failures = 0;
$checks = 0;

function check(string $label, callable $test): void
{
    global $failures, $checks;
    $checks++;
    try {
        $test();
        echo 'OK   ' . $label . PHP_EOL;
    } catch (Throwable $exception) {
        $failures++;
        echo 'FAIL ' . $label . ' — ' . $exception->getMessage() . PHP_EOL;
    }
}

function sameValue(mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new RuntimeException('Ожидалось ' . var_export($expected, true) . ', получено ' . var_export($actual, true));
    }
}

function testAccount(string $region = 'EUW', int $gold = 3000, ?DateTimeImmutable $last = null): int
{
    global $database, $ownerId, $regionIds;
    $query = $database->prepare(
        'INSERT INTO accounts (owner_id, player_name, current_region_id, gold, last_transfer_at)
         VALUES (?, ?, ?, ?, ?)'
    );
    $query->execute([$ownerId, 'Тест-' . bin2hex(random_bytes(8)), $regionIds[$region], $gold, $last?->format('Y-m-d H:i:s')]);
    return (int) $database->lastInsertId();
}

function accountState(int $accountId): array
{
    global $database;
    $query = $database->prepare('SELECT * FROM accounts WHERE id = ?');
    $query->execute([$accountId]);
    return $query->fetch();
}

function tryTransfer(int $accountId, string $source, string $target, ?string $key = null, int $version = 0): array
{
    global $database, $ownerId, $regionIds, $now;
    return performTransfer($database, $accountId, $ownerId, (int) $regionIds[$source], $version,
        (int) $regionIds[$target], $key ?? bin2hex(random_bytes(16)), $now);
}

function transferCount(int $accountId): int
{
    global $database;
    $result = $database->prepare('SELECT COUNT(*) FROM transfers WHERE account_id = ?');
    $result->execute([$accountId]);
    return (int) $result->fetchColumn();
}

function expectDomainFailure(callable $action, string $text): void
{
    try {
        $action();
    } catch (DomainException $exception) {
        if (!str_contains($exception->getMessage(), $text)) {
            throw new RuntimeException($exception->getMessage());
        }
        return;
    }
    throw new RuntimeException('Ожидаемый отказ не сработал.');
}

require __DIR__ . '/transfers.php';
require __DIR__ . '/rankings.php';

echo ($checks - $failures) . '/' . $checks . ' проверок пройдено' . PHP_EOL;
exit($failures > 0 ? 1 : 0);
