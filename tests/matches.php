<?php
declare(strict_types=1);

check('Три уникальных матчей после переноса завершают калибровку', function () use ($database, $ownerId, $now): void {
    $id = testAccount();
    $database->prepare('UPDATE accounts SET mmr = 1800 WHERE id = ?')->execute([$id]);
    $before = assignMatch($database, $id, $ownerId, $now);
    sameValue('EUW', $before['region_code']);
    $receipt = tryTransfer($id, 'EUW', 'ASIA');
    sameValue(1780, (int) accountState($id)['mmr']);
    expectDomainFailure(fn () => finishMatch($database, $id, $ownerId, (int) $before['id'],
        ['outcome' => 'win', 'mmr_after' => 1810, 'rank_points' => 10], $now), 'прежнему региону');
    for ($number = 1; $number <= PLACEMENT_GAMES; $number++) {
        $match = assignMatch($database, $id, $ownerId, $now);
        sameValue('ASIA', $match['region_code']);
        $result = demoResult($database, accountState($id), (int) $match['id'], 'win');
        finishMatch($database, $id, $ownerId, (int) $match['id'], $result, $now);
        finishMatch($database, $id, $ownerId, (int) $match['id'], $result, $now);
        $ranking = rankingById($database, (int) $receipt['target_ranking_id']);
        sameValue($number, (int) $ranking['placement_games']);
        sameValue($number === PLACEMENT_GAMES ? 'Серебро I' : null, $ranking['confirmed_rank']);
        if ($number === 2) {
            sameValue(1800, (int) accountState($id)['mmr']);
        }
    }
    sameValue(400, (int) accountState($id)['gold']);
    sameValue(1, transferCount($id));
});

check('Два разных исхода предварительного Алмаза после калибровки', function () use ($database, $ownerId, $now, $regionIds): void {
    $season = requireSeason($database, $now);
    foreach (['win' => 'Алмаз IV', 'loss' => 'Изумруд I'] as $outcome => $expectedRank) {
        $id = testAccount('ASIA');
        $database->prepare(
            'INSERT INTO rankings (account_id, season_id, region_id, confirmed_rank, placement_games) VALUES (?, ?, ?, ?, ?)'
        )->execute([$id, $season['id'], $regionIds['ASIA'], 'Алмаз IV', PLACEMENT_GAMES]);
        $receipt = tryTransfer($id, 'ASIA', 'EUW');
        $rankingId = (int) $receipt['target_ranking_id'];
        sameValue('Алмаз IV', rankingById($database, $rankingId)['provisional_rank']);
        sameValue(null, rankingById($database, $rankingId)['confirmed_rank']);
        for ($i = 1; $i <= PLACEMENT_GAMES; $i++) {
            $match = assignMatch($database, $id, $ownerId, $now);
            $result = demoResult($database, accountState($id), (int) $match['id'], $outcome);
            finishMatch($database, $id, $ownerId, (int) $match['id'], $result, $now);
        }
        $rank = rankingById($database, $rankingId);
        sameValue($expectedRank, $rank['confirmed_rank']);
        if ($outcome === 'loss') {
            sameValue(0, (int) $rank['rank_points']);
            sameValue(1450, (int) accountState($id)['mmr']);
        }
    }
});

check('Назначение сервера не меняет регион, баланс и историю', function () use ($database, $ownerId, &$regionIds, $now): void {
    $id = testAccount('RU');
    $before = accountState($id);
    $match = assignMatch($database, $id, $ownerId, $now);
    sameValue('RU', $match['region_code']);
    sameValue($before, accountState($id));
    sameValue(0, transferCount($id));
    $temporary = 'TMP' . bin2hex(random_bytes(2));
    $database->prepare('INSERT INTO regions (code, name) VALUES (?, ?)')->execute([$temporary, 'Тестовый регион без серверов']);
    $regionIds[$temporary] = $database->lastInsertId();
    $noServerId = testAccount($temporary);
    expectDomainFailure(fn () => assignMatch($database, $noServerId, $ownerId, $now), 'нет доступных');
});
