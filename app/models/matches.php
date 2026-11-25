<?php
declare(strict_types=1);

function availableMatchServer(PDO $database, int $regionId): ?array
{
    $query = $database->prepare(
        'SELECT s.id, s.name, r.code AS region_code
         FROM match_servers s JOIN regions r ON r.id = s.region_id
         WHERE s.region_id = ? AND s.is_available = 1 AND r.is_available = 1
         ORDER BY s.id LIMIT 1'
    );
    $query->execute([$regionId]);
    return $query->fetch() ?: null;
}

function matchByAccount(PDO $database, int $matchId, int $accountId): ?array
{
    $query = $database->prepare(
        'SELECT m.*, s.name AS server_name, r.code AS region_code,
            (SELECT COUNT(*) FROM matches older WHERE older.account_id = m.account_id AND older.id < m.id) AS account_number
         FROM matches m JOIN match_servers s ON s.id = m.match_server_id
         JOIN regions r ON r.id = s.region_id
         WHERE m.id = ? AND m.account_id = ?'
    );
    $query->execute([$matchId, $accountId]);
    return $query->fetch() ?: null;
}

function assignMatch(PDO $database, int $accountId, int $ownerId, DateTimeImmutable $now): array
{
    $database->beginTransaction();
    try {
        $account = lockedAccount($database, $accountId, $ownerId);
        $regionId = (int) $account['current_region_id'];
        $server = availableMatchServer($database, $regionId);
        if ($server === null) {
            throw new DomainException('В текущем регионе нет доступных серверов матча.');
        }
        $season = requireSeason($database, $now);
        $seasonId = (int) $season['id'];
        $time = $now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $ranking = openRanking($database, $accountId, $seasonId, $regionId);
        $rankingId = $ranking === null
            ? openCalibration($database, $accountId, $seasonId, $regionId, null, $time)
            : (int) $ranking['id'];

        $pending = $database->prepare(
            'SELECT m.id FROM matches m JOIN match_servers s ON s.id = m.match_server_id
             WHERE m.account_id = ? AND m.ranking_id = ? AND m.completed_at IS NULL AND s.is_available = 1
             ORDER BY m.id DESC LIMIT 1'
        );
        $pending->execute([$accountId, $rankingId]);
        $matchId = $pending->fetchColumn();
        if ($matchId === false) {
            $insert = $database->prepare(
                'INSERT INTO matches (account_id, ranking_id, match_server_id, started_at) VALUES (?, ?, ?, ?)'
            );
            $insert->execute([$accountId, $rankingId, $server['id'], $time]);
            $matchId = $database->lastInsertId();
        }
        $match = matchByAccount($database, (int) $matchId, $accountId);
        $database->commit();
        return $match;
    } catch (Throwable $exception) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }
        throw $exception;
    }
}

function finishMatch(
    PDO $database,
    int $accountId,
    int $ownerId,
    int $matchId,
    array $result,
    DateTimeImmutable $now
): array {
    if (!in_array($result['outcome'] ?? null, ['win', 'loss'], true)) {
        throw new DomainException('Укажи результат матча.');
    }
    $database->beginTransaction();
    try {
        $account = lockedAccount($database, $accountId, $ownerId);
        $match = matchByAccount($database, $matchId, $accountId);
        if ($match === null) {
            throw new DomainException('Матч не найден.');
        }
        if ($match['completed_at'] !== null) {
            if ($match['outcome'] !== $result['outcome']) {
                throw new DomainException('Для матча уже сохранён другой результат.');
            }
            $database->commit();
            return $match;
        }

        $ranking = rankingById($database, (int) $match['ranking_id']);
        $season = requireSeason($database, $now);
        if ($ranking === null || $ranking['closed_at'] !== null
            || (int) $ranking['region_id'] !== (int) $account['current_region_id']
            || (int) $ranking['season_id'] !== (int) $season['id']) {
            throw new DomainException('Матч относится к прежнему региону или сезону.');
        }
        $placements = (int) $ranking['placement_games'];
        $isCalibration = $ranking['confirmed_rank'] === null;
        if ($isCalibration) {
            $placements = min(PLACEMENT_GAMES, $placements + 1);
        }
        $lastPlacement = $isCalibration && $placements === PLACEMENT_GAMES;
        [$rank, $points] = rankAfterMatch($ranking, $result['outcome'], $isCalibration, $lastPlacement);
        $provisionalRank = $isCalibration ? $rank : $ranking['provisional_rank'];
        $confirmedRank = !$isCalibration || $lastPlacement ? $rank : null;
        $updateRanking = $database->prepare(
            'UPDATE rankings SET placement_games = ?, rank_points = ?, provisional_rank = ?, confirmed_rank = ? WHERE id = ?'
        );
        $updateRanking->execute([$placements, $points, $provisionalRank, $confirmedRank, $ranking['id']]);

        if (!is_int($result['mmr_after'] ?? null)) {
            throw new DomainException('Рейтинговая система не передала новое значение MMR.');
        }
        $updateAccount = $database->prepare('UPDATE accounts SET mmr = ? WHERE id = ?');
        $updateAccount->execute([$result['mmr_after'], $accountId]);

        $time = $now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $write = $database->prepare('UPDATE matches SET outcome = ?, completed_at = ?, mmr_after = ? WHERE id = ?');
        $write->execute([$result['outcome'], $time, $result['mmr_after'], $matchId]);
        $match = matchByAccount($database, $matchId, $accountId);
        $database->commit();
        return $match;
    } catch (Throwable $exception) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }
        throw $exception;
    }
}
