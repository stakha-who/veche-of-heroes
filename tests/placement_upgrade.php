<?php
declare(strict_types=1);

check('Обновление завершает начатую калибровку по третьему матчу', function () use ($database, $regionIds, $now): void {
    $id = testAccount();
    $doneAccount = testAccount();
    $season = requireSeason($database, $now);
    $regionId = (int) $regionIds['EUW'];
    $server = availableMatchServer($database, $regionId);
    $create = $database->prepare(
        'INSERT INTO rankings (account_id, season_id, region_id, provisional_rank, confirmed_rank, rank_points, placement_games)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $create->execute([$id, $season['id'], $regionId, 'Алмаз IV', null, 20, 4]);
    $rankingId = (int) $database->lastInsertId();
    $create->execute([$doneAccount, $season['id'], $regionId, 'Изумруд I', 'Платина I', 15, 5]);
    $completedId = (int) $database->lastInsertId();
    $match = $database->prepare(
        'INSERT INTO matches (account_id, ranking_id, match_server_id, started_at, completed_at, outcome)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $time = $now->format('Y-m-d H:i:s');
    foreach (['win', 'win', 'loss', 'win'] as $outcome) {
        $match->execute([$id, $rankingId, $server['id'], $time, $time, $outcome]);
    }

    $migration = file_get_contents(__DIR__ . '/../database/migrations/015_three_placement_matches.sql');
    $database->exec($migration);
    $rank = rankingById($database, $rankingId);
    sameValue('Изумруд I', $rank['confirmed_rank']);
    sameValue(PLACEMENT_GAMES, (int) $rank['placement_games']);
    sameValue(20, (int) $rank['rank_points']);
    $oldRank = rankingById($database, $completedId);
    sameValue('Платина I', $oldRank['confirmed_rank']);
    sameValue(5, (int) $oldRank['placement_games']);
});
