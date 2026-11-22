<?php
declare(strict_types=1);

require __DIR__ . '/../app/database.php';

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

$fixtures = [
    ['demo', 'Яромир', 'EUW', 27, 3000],
    ['demo', 'Добрыня', 'KR', 31, 4200],
    ['demo', 'Мирослава', 'RU', 24, 4200],
    ['demo', 'Ратибор', 'EUW', 12, 1200],
    ['demo', 'Весна', 'EUW', 17, 4000],
    ['demo', 'Любомир', 'ASIA', 36, 4300],
    ['guest', 'Лада', 'EUNE', 9, 3200],
];
$accountSelect = $database->prepare('SELECT id FROM accounts WHERE player_name = ?');
$accountInsert = $database->prepare(
    'INSERT INTO accounts (owner_id, player_name, current_region_id, level, gold)
     VALUES (?, ?, ?, ?, ?)'
);
$accountIds = [];
$newAccounts = [];
foreach ($fixtures as [$owner, $player, $region, $level, $gold]) {
    $accountSelect->execute([$player]);
    $accountId = $accountSelect->fetchColumn();
    if ($accountId === false) {
        $accountInsert->execute([$userIds[$owner], $player, $regionIds[$region], $level, $gold]);
        $accountId = $database->lastInsertId();
        $newAccounts[] = $player;
    }
    $accountIds[$player] = (int) $accountId;
}

$itemInsert = $database->prepare(
    'INSERT INTO account_items (account_id, item_name, quantity) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE item_name = account_items.item_name'
);
foreach ([
    ['Яромир', 'Обережный пояс', 1],
    ['Яромир', 'Ратный клинок', 1],
    ['Добрыня', 'Липовый щит', 1],
    ['Мирослава', 'Берестяной оберег', 2],
    ['Любомир', 'Серебряная гривна', 1],
] as [$player, $item, $quantity]) {
    $itemInsert->execute([$accountIds[$player], $item, $quantity]);
}

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

$accountTime = $database->prepare('UPDATE accounts SET last_transfer_at = ? WHERE id = ?');
foreach (['Весна' => '-1 day', 'Любомир' => '-91 days'] as $player => $interval) {
    if (in_array($player, $newAccounts, true)) {
        $timestamp = new DateTimeImmutable($interval);
        $accountTime->execute([$timestamp->format('Y-m-d H:i:s'), $accountIds[$player]]);
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
