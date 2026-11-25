<?php
declare(strict_types=1);

check('Возврат в пределах сезона переносит текущий ранг с исходного сервера', function () use ($database, $ownerId, $regionIds, $now): void {
    $id = testAccount('EUW', 9000);
    $season = requireSeason($database, $now);
    $start = new DateTimeImmutable($season['starts_at']);
    $database->prepare(
        'INSERT INTO rankings (account_id, season_id, region_id, confirmed_rank, placement_games) VALUES (?, ?, ?, ?, ?)'
    )->execute([$id, $season['id'], $regionIds['EUW'], 'Изумруд II', PLACEMENT_GAMES]);
    $first = performTransfer($database, $id, $ownerId, (int) $regionIds['EUW'], 0, (int) $regionIds['ASIA'],
        bin2hex(random_bytes(16)), $start->add(new DateInterval('PT1H')));
    $database->prepare('UPDATE rankings SET confirmed_rank = ?, placement_games = ? WHERE id = ?')
        ->execute(['Алмаз IV', PLACEMENT_GAMES, $first['target_ranking_id']]);
    $back = performTransfer($database, $id, $ownerId, (int) $regionIds['ASIA'], 1, (int) $regionIds['EUW'],
        bin2hex(random_bytes(16)), $start->add(new DateInterval('PT2161H')));
    $rank = rankingById($database, (int) $back['target_ranking_id']);
    sameValue('Алмаз IV', $rank['provisional_rank']);
    sameValue(null, $rank['confirmed_rank']);
    sameValue('Изумруд II', rankingHistory($database, $id)[1]['confirmed_rank']);
});

check('Граница сезона: прошлый ранг в архиве, новая калибровка без переноса ранга', function () use ($database, $ownerId, $regionIds, $now): void {
    $id = testAccount('ASIA');
    $old = requireSeason($database, $now);
    $boundary = new DateTimeImmutable($old['ends_at']);
    $new = requireSeason($database, $boundary);
    if ((int) $old['id'] === (int) $new['id']) {
        throw new RuntimeException('На границе ожидался новый сезон.');
    }
    $database->prepare(
        'INSERT INTO rankings (account_id, season_id, region_id, confirmed_rank, placement_games) VALUES (?, ?, ?, ?, ?)'
    )->execute([$id, $old['id'], $regionIds['ASIA'], 'Алмаз IV', PLACEMENT_GAMES]);
    $receipt = performTransfer($database, $id, $ownerId, (int) $regionIds['ASIA'], 0, (int) $regionIds['EUW'],
        bin2hex(random_bytes(16)), $boundary);
    sameValue((int) $new['id'], (int) $receipt['season_id']);
    sameValue(null, rankingById($database, (int) $receipt['target_ranking_id'])['provisional_rank']);
    sameValue('Алмаз IV', olderSeasonRank($database, $id, $new['starts_at'])['confirmed_rank']);
});
