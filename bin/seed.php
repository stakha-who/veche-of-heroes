<?php
declare(strict_types=1);

require __DIR__ . '/../app/database.php';
require __DIR__ . '/../app/models/rankings.php';
require __DIR__ . '/../app/models/seasons.php';
require __DIR__ . '/../app/models/game_clock.php';
require __DIR__ . '/../app/models/demo_fixtures.php';

if (getenv('APP_DEMO') !== '1') {
    exit(0);
}

$database = connectDatabase();
$userSelect = $database->prepare('SELECT id FROM users WHERE username = ?');
$userInsert = $database->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
$userIds = [];
foreach (['demo' => 'veche-demo', 'guest' => 'veche-guest'] as $name => $password) {
    $userSelect->execute([$name]);
    $userId = $userSelect->fetchColumn();
    if ($userId === false) {
        $userInsert->execute([$name, password_hash($password, PASSWORD_DEFAULT)]);
        $userId = $database->lastInsertId();
    }
    $userIds[$name] = (int) $userId;
}

$regionInsert = $database->prepare(
    'INSERT INTO regions (code, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE code = regions.code'
);
foreach (['EUW' => 'Западная Европа', 'EUNE' => 'Северо-Восточная Европа', 'ASIA' => 'Азия',
          'KR' => 'Корея', 'RU' => 'Россия'] as $code => $name) {
    $regionInsert->execute([$code, $name]);
}
$regionIds = $database->query('SELECT code, id FROM regions')->fetchAll(PDO::FETCH_KEY_PAIR);

$routeInsert = $database->prepare(
    'INSERT INTO transfer_routes (source_region_id, target_region_id) VALUES (?, ?)
     ON DUPLICATE KEY UPDATE source_region_id = transfer_routes.source_region_id'
);
foreach (['EUW', 'EUNE', 'ASIA', 'KR'] as $source) {
    foreach (['EUW', 'EUNE', 'ASIA'] as $target) {
        if ($source !== $target) {
            $routeInsert->execute([$regionIds[$source], $regionIds[$target]]);
        }
    }
}

$seasonInsert = $database->prepare(
    'INSERT INTO seasons (name, starts_at, ends_at) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE starts_at = seasons.starts_at'
);
$year = (int) date('Y');
foreach (range($year - 1, $year + 1) as $seasonYear) {
    foreach (['01-01', '07-01'] as $half => $monthDay) {
        $start = new DateTimeImmutable($seasonYear . '-' . $monthDay . ' 00:00:00');
        $end = $start->modify('+6 months');
        $seasonInsert->execute([
            'Сезон ' . ($half + 1) . ' · ' . $seasonYear,
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s'),
        ]);
    }
}

$serverInsert = $database->prepare(
    'INSERT INTO match_servers (region_id, name, is_available) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE name = match_servers.name'
);
foreach (['EUW' => 'Ясный бор', 'EUNE' => 'Северный страж', 'ASIA' => 'Восточные врата',
          'KR' => 'Горный дозор', 'RU' => 'Ратный двор'] as $code => $name) {
    $serverInsert->execute([$regionIds[$code], $name, 1]);
}
$serverInsert->execute([$regionIds['EUW'], 'Старый острог', 0]);


$now = new DateTimeImmutable();
$existing = $database->prepare('SELECT 1 FROM accounts WHERE player_name = ?');
foreach ($userIds as $username => $ownerId) {
    foreach (demoAccountFixtures($username) as $fixture) {
        $existing->execute([$fixture[0]]);
        if ($existing->fetchColumn() !== false) {
            continue;
        }
        $database->beginTransaction();
        try {
            addDemoAccount($database, $ownerId, $fixture, $regionIds, gameTime($database, $ownerId, $now));
            $database->commit();
        } catch (Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            throw $exception;
        }
    }
}
