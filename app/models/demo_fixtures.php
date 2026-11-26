<?php
declare(strict_types=1);

function demoAccountFixtures(string $username): array
{
    return match ($username) {
        'demo' => [
            ['Яромир', 'EUW', 27, 3000, 1800, null, ['Изумруд II', 45], ['Обережный пояс' => 1, 'Ратный клинок' => 1]],
            ['Велимир', 'EUNE', 21, 2600, 1650, null, ['Золото II', 6], ['Сосновое копьё' => 1]],
            ['Любомир', 'ASIA', 36, 4300, 1920, '-91 days', ['Алмаз IV', 24], ['Серебряная гривна' => 1]],
            ['Добрыня', 'KR', 31, 4200, 1730, null, ['Платина II', 40], ['Липовый щит' => 1]],
            ['Мирослава', 'RU', 24, 4200, 1680, null, null, ['Берестяной оберег' => 2]],
            ['Ратибор', 'EUW', 12, 1200, 1500, null, null, []],
            ['Весна', 'EUW', 17, 4000, 1500, '-1 day', null, []],
            ['Зоряна', 'EUW', 18, 4000, 1500, null, null, []],
        ],
        'guest' => [
            ['Лада', 'EUNE', 9, 3200, 1500, null, null, []],
        ],
        default => [],
    };
}

function addDemoAccount(
    PDO $database,
    int $ownerId,
    array $fixture,
    array $regionIds,
    DateTimeImmutable $now,
    ?int $accountId = null
): int {
    [$player, $code, $level, $gold, $mmr, $cooldown, $rank, $items] = $fixture;
    $lastTransfer = $cooldown === null ? null : $now->modify($cooldown)->format('Y-m-d H:i:s');
    if ($accountId === null) {
        $add = $database->prepare(
            'INSERT INTO accounts (owner_id, player_name, current_region_id, level, gold, mmr, last_transfer_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $add->execute([$ownerId, $player, $regionIds[$code], $level, $gold, $mmr, $lastTransfer]);
        $accountId = (int) $database->lastInsertId();
    } else {
        $restore = $database->prepare(
            'UPDATE accounts SET current_region_id = ?, level = ?, gold = ?, mmr = ?, last_transfer_at = ?,
                deleted_at = NULL, transfer_version = transfer_version + 1
             WHERE id = ? AND owner_id = ?'
        );
        $restore->execute([$regionIds[$code], $level, $gold, $mmr, $lastTransfer, $accountId, $ownerId]);
    }

    $addItem = $database->prepare('INSERT INTO account_items (account_id, item_name, quantity) VALUES (?, ?, ?)');
    foreach ($items as $name => $quantity) {
        $addItem->execute([$accountId, $name, $quantity]);
    }

    $season = activeSeason($database, $now);
    $addRank = $database->prepare(
        'INSERT INTO rankings (account_id, season_id, region_id, confirmed_rank, rank_points, placement_games, closed_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    if ($rank !== null && $season !== null) {
        $addRank->execute([$accountId, $season['id'], $regionIds[$code], $rank[0], $rank[1], PLACEMENT_GAMES, null]);
    }

    if ($player === 'Зоряна') {
        $before = $database->prepare('SELECT id FROM seasons WHERE ends_at <= ? ORDER BY ends_at DESC LIMIT 1');
        $before->execute([$now->format('Y-m-d H:i:s')]);
        $previousId = $before->fetchColumn();
        if ($previousId !== false) {
            $addRank->execute([$accountId, $previousId, $regionIds[$code], 'Золото I', 30, PLACEMENT_GAMES, $now->format('Y-m-d H:i:s')]);
        }
    }
    if ($player === 'Любомир' && $season !== null) {
        $addRank->execute([$accountId, $season['id'], $regionIds['EUW'], 'Изумруд I', 20, PLACEMENT_GAMES, $now->format('Y-m-d H:i:s')]);
    }
    return $accountId;
}

function resetDemoAccounts(PDO $database, int $ownerId, DateTimeImmutable $now): void
{
    $database->beginTransaction();
    try {
        $user = $database->prepare('SELECT username FROM users WHERE id = ? FOR UPDATE');
        $user->execute([$ownerId]);
        $fixtures = demoAccountFixtures((string) $user->fetchColumn());
        if (!$fixtures) {
            throw new DomainException('У этого пользователя нет стартовых демонстрационных аккаунтов.');
        }

        $owned = $database->prepare('SELECT player_name, id FROM accounts WHERE owner_id = ? FOR UPDATE');
        $owned->execute([$ownerId]);
        $remaining = $owned->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach (['matches', 'transfers', 'rankings', 'account_items'] as $table) {
            $delete = $database->prepare(
                "DELETE data FROM $table AS data JOIN accounts AS a ON a.id = data.account_id WHERE a.owner_id = ?"
            );
            $delete->execute([$ownerId]);
        }

        $regions = $database->query('SELECT code, id FROM regions')->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($fixtures as $fixture) {
            $name = $fixture[0];
            $id = isset($remaining[$name]) ? (int) $remaining[$name] : null;
            addDemoAccount($database, $ownerId, $fixture, $regions, $now, $id);
            unset($remaining[$name]);
        }

        $deleteAccount = $database->prepare('DELETE FROM accounts WHERE id = ? AND owner_id = ?');
        foreach ($remaining as $id) {
            $deleteAccount->execute([$id, $ownerId]);
        }
        $database->commit();
    } catch (Throwable $exception) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }
        throw $exception;
    }
}
