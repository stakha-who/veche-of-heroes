<?php
declare(strict_types=1);

require __DIR__ . '/../app/database.php';
require __DIR__ . '/../app/models/seasons.php';
require __DIR__ . '/../app/models/rankings.php';

if (getenv('APP_DEMO') !== '1') {
    fwrite(STDERR, "Команда доступна в демонстрационном режиме.\n");
    exit(2);
}

$database = connectDatabase();
$name = $argv[1] ?? 'Яромир';
$query = $database->prepare(
    'SELECT a.id, a.player_name, a.level, a.gold, a.mmr, a.current_region_id, r.code AS region
     FROM accounts a JOIN regions r ON r.id = a.current_region_id WHERE a.player_name = ?'
);
$query->execute([$name]);
$account = $query->fetch();
if (!$account) {
    fwrite(STDERR, "Игровой аккаунт не найден.\n");
    exit(1);
}
$season = activeSeason($database, new DateTimeImmutable());
$ranking = $season === null ? null : openRanking(
    $database, (int) $account['id'], (int) $season['id'], (int) $account['current_region_id']
);
echo json_encode(['account' => $account, 'ranking' => $ranking], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
