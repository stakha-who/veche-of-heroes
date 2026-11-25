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

    return [
        'outcome' => $outcome,
        'mmr_after' => (int) $account['mmr'] + ($outcome === 'win' ? 10 : -10),
    ];
}
