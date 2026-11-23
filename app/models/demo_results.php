<?php
declare(strict_types=1);

function demoResult(PDO $database, array $account, int $matchId, string $outcome): array
{
    if (!in_array($outcome, ['win', 'loss'], true)) {
        throw new DomainException('Укажи победу или поражение.');
    }
    $match = matchByAccount($database, $matchId, (int) $account['id']);
    if ($match === null) {
        throw new DomainException('Матч не найден.');
    }

    $ranking = rankingById($database, (int) $match['ranking_id']);

    $rank = $ranking['provisional_rank'] ?? 'Серебро I';
    if ($rank === 'Алмаз IV' && $outcome === 'loss') {
        $rank = 'Изумруд I';
    }

    return [
        'outcome' => $outcome,
        'confirmed_rank' => $rank,
        'rank_points' => max(0, (int) $ranking['rank_points'] + ($outcome === 'win' ? 10 : -5)),
        'mmr_after' => (int) $account['mmr'] + ($outcome === 'win' ? 10 : -10),
    ];
}
