<?php
declare(strict_types=1);

const PLACEMENT_GAMES = 3;

function openRanking(PDO $database, int $accountId, int $seasonId, int $regionId): ?array
{
    $query = $database->prepare(
        'SELECT * FROM rankings
         WHERE account_id = ? AND season_id = ? AND region_id = ? AND closed_at IS NULL
         ORDER BY id DESC LIMIT 1'
    );
    $query->execute([$accountId, $seasonId, $regionId]);
    return $query->fetch() ?: null;
}

function rankingById(PDO $database, int $rankingId): ?array
{
    $query = $database->prepare('SELECT * FROM rankings WHERE id = ?');
    $query->execute([$rankingId]);
    return $query->fetch() ?: null;
}

function openCalibration(
    PDO $database,
    int $accountId,
    int $seasonId,
    int $regionId,
    ?string $provisionalRank,
    string $time
): int {
    $close = $database->prepare('UPDATE rankings SET closed_at = ? WHERE account_id = ? AND closed_at IS NULL');
    $close->execute([$time, $accountId]);
    $insert = $database->prepare(
        'INSERT INTO rankings (account_id, season_id, region_id, provisional_rank) VALUES (?, ?, ?, ?)'
    );
    $insert->execute([$accountId, $seasonId, $regionId, $provisionalRank]);
    return (int) $database->lastInsertId();
}

function olderSeasonRank(PDO $database, int $accountId, string $startsAt): ?array
{
    $query = $database->prepare(
        'SELECT rankings.confirmed_rank, seasons.name AS season_name
         FROM rankings JOIN seasons ON seasons.id = rankings.season_id
         WHERE rankings.account_id = ? AND seasons.starts_at < ? AND rankings.confirmed_rank IS NOT NULL
         ORDER BY seasons.starts_at DESC, rankings.id DESC LIMIT 1'
    );
    $query->execute([$accountId, $startsAt]);
    return $query->fetch() ?: null;
}

function rankingHistory(PDO $database, int $accountId): array
{
    $query = $database->prepare(
        'SELECT rankings.confirmed_rank, seasons.name AS season_name, regions.code AS region_code
         FROM rankings JOIN seasons ON seasons.id = rankings.season_id
         JOIN regions ON regions.id = rankings.region_id
         WHERE rankings.account_id = ? AND rankings.confirmed_rank IS NOT NULL
         ORDER BY seasons.starts_at DESC, rankings.id DESC'
    );
    $query->execute([$accountId]);
    return $query->fetchAll();
}

const POINTS_PER_RANK = 50;

function rankLadder(): array
{
    $ranks = [];
    foreach (['Железо', 'Бронза', 'Серебро', 'Золото', 'Платина', 'Изумруд', 'Алмаз'] as $tier) {
        foreach (['IV', 'III', 'II', 'I'] as $division) {
            $ranks[] = "$tier $division";
        }
    }
    return [...$ranks, 'Мастер', 'Грандмастер', 'Претендент'];
}

function rankAfterMatch(array $ranking, string $outcome, bool $isCalibration, bool $lastPlacement): array
{
    $ladder = rankLadder();
    $current = $ranking['confirmed_rank'] ?? $ranking['provisional_rank'] ?? 'Серебро I';
    $index = array_search($current, $ladder, true);
    if ($index === false) {
        throw new DomainException('Не удалось определить текущую ступень рейтинга.');
    }
    $change = $outcome === 'win' ? 10 : -8;
    $points = (int) $ranking['rank_points'] + ($isCalibration ? $change * 2 : $change);
    while ($points >= POINTS_PER_RANK && $index < count($ladder) - 1) {
        $points -= POINTS_PER_RANK;
        $index++;
    }
    while ($points < 0 && $index > 0) {
        $points += POINTS_PER_RANK;
        $index--;
    }
    if ($lastPlacement) {
        $index = min($index, (int) array_search('Алмаз III', $ladder, true));
    }
    return [$ladder[$index], max(0, min(POINTS_PER_RANK - 1, $points))];
}
