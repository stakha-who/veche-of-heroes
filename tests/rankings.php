<?php
declare(strict_types=1);

check('Перенос снижает MMR и переносит подтверждённый ранг как предварительный', function () use ($database, $regionIds, $now): void {
    $id = testAccount();
    $season = requireSeason($database, $now);
    $database->prepare('UPDATE accounts SET mmr = 1800 WHERE id = ?')->execute([$id]);
    $database->prepare(
        'INSERT INTO rankings (account_id, season_id, region_id, confirmed_rank, placement_games) VALUES (?, ?, ?, ?, 5)'
    )->execute([$id, $season['id'], $regionIds['EUW'], 'Изумруд II']);

    $key = bin2hex(random_bytes(16));
    $receipt = tryTransfer($id, 'EUW', 'ASIA', $key);
    sameValue(1780, (int) accountState($id)['mmr']);
    $rank = openRanking($database, $id, (int) $season['id'], (int) $regionIds['ASIA']);
    sameValue('Изумруд II', $rank['provisional_rank']);
    sameValue(null, $rank['confirmed_rank']);
    sameValue(0, (int) $rank['placement_games']);
    sameValue('Изумруд II', $receipt['source_rank']);
    sameValue(null, openRanking($database, $id, (int) $season['id'], (int) $regionIds['EUW']));
    tryTransfer($id, 'EUW', 'ASIA', $key);
    sameValue(1780, (int) accountState($id)['mmr']);
});

check('Ошибка записи сохраняет MMR и исходный ранг', function () use ($database, $regionIds, $now): void {
    $id = testAccount();
    $season = requireSeason($database, $now);
    $database->prepare('UPDATE accounts SET mmr = 1800 WHERE id = ?')->execute([$id]);
    $database->prepare(
        'INSERT INTO rankings (account_id, season_id, region_id, confirmed_rank, placement_games) VALUES (?, ?, ?, ?, 5)'
    )->execute([$id, $season['id'], $regionIds['EUW'], 'Изумруд II']);
    $before = accountState($id);
    $beforeRank = openRanking($database, $id, (int) $season['id'], (int) $regionIds['EUW']);
    $key = bin2hex(random_bytes(16));
    rejectHistoryInsert($key, function () use ($database, $id, $before, $beforeRank, $season, $regionIds, $key): void {
        try {
            tryTransfer($id, 'EUW', 'ASIA', $key);
            throw new RuntimeException('База должна была отклонить запись истории.');
        } catch (PDOException $exception) {
            sameValue(3819, (int) ($exception->errorInfo[1] ?? 0));
        }
        sameValue($before, accountState($id));
        sameValue($beforeRank, openRanking($database, $id, (int) $season['id'], (int) $regionIds['EUW']));
        sameValue(null, openRanking($database, $id, (int) $season['id'], (int) $regionIds['ASIA']));
    });
});
