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
