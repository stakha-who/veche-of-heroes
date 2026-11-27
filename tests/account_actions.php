<?php
declare(strict_types=1);

check('Удалить аккаунт может только владелец, удалённый профиль закрыт', function () use ($database, $ownerId): void {
    $id = testAccount();
    sameValue(false, removeOwnedAccount($database, $id, $ownerId + 1));
    sameValue(true, removeOwnedAccount($database, $id, $ownerId));
    sameValue(null, accountByOwner($database, $id, $ownerId));
    expectDomainFailure(fn () => tryTransfer($id, 'EUW', 'ASIA'), 'Аккаунт не найден');
});

check('Кнопка золотых пополняет только кошелёк владельца', function () use ($database, $ownerId): void {
    $id = testAccount('EUW', 100);
    expectDomainFailure(fn () => addDemoGold($database, $id, $ownerId + 1), 'Не удалось пополнить');
    addDemoGold($database, $id, $ownerId);
    sameValue(2700, (int) accountState($id)['gold']);
    sameValue(0, transferCount($id));
});

check('Демовремя истекает кулдаун по календарю и открывает новый сезон', function () use ($database, $ownerId, $regionIds, $now): void {
    $year = (int) $now->format('Y');
    $start = new DateTimeImmutable("$year-11-11 23:57:00", new DateTimeZone('UTC'));
    $guestId = (int) $database->query("SELECT id FROM users WHERE username = 'guest'")->fetchColumn();
    $guestTime = gameTime($database, $guestId, $start);
    $accountId = testAccount('EUW', 6000);
    $secondAccountId = testAccount('EUW', 3000, $start);
    $origin = (int) $regionIds['EUW'];
    $target = (int) $regionIds['ASIA'];
    $receipt = confirmTestTransfer($database, $accountId, $ownerId, $origin, 0, $target,
        bin2hex(random_bytes(16)), $start);
    $oldSeason = requireSeason($database, $start);
    sameValue(($year + 1) . '-01-01 00:00:00', $oldSeason['ends_at']);
    sameValue('Ещё не прошло 90 восходов солнца после прошлого переноса.',
        transferQuote($database, accountState($accountId), $origin, $start)['error']);
    sameValue('Ещё не прошло 90 восходов солнца после прошлого переноса.',
        transferQuote($database, accountState($secondAccountId), $target, $start)['error']);

    advanceDemoTime($database, $ownerId, $start);
    $after = gameTime($database, $ownerId, $start);
    sameValue(($year + 1) . '-02-09 23:57:00', $after->format('Y-m-d H:i:s'));
    sameValue($guestTime->getTimestamp(), gameTime($database, $guestId, $start)->getTimestamp());

    $history = transferHistory($database, $accountId);
    sameValue(1, count($history));
    sameValue($receipt['transferred_at'], $history[0]['transferred_at']);
    sameValue($receipt['transferred_at'], accountState($accountId)['last_transfer_at']);
    sameValue(1500, (int) $history[0]['mmr_before']);
    sameValue(1480, (int) $history[0]['mmr_after']);
    sameValue(3400, (int) accountState($accountId)['gold']);
    sameValue(null, transferQuote($database, accountState($secondAccountId), $target, $after)['error']);
    sameValue('Входящий перенос в этот регион закрыт.',
        transferQuote($database, accountState($accountId), (int) $regionIds['KR'], $after)['error']);

    $season = requireSeason($database, $after);
    sameValue($oldSeason['ends_at'], $season['starts_at']);
    sameValue(($year + 1) . '-07-01 00:00:00', $season['ends_at']);
    sameValue(null, openRanking($database, $accountId, (int) $season['id'], $target));
    $back = confirmTestTransfer($database, $accountId, $ownerId, $target, 1, $origin,
        bin2hex(random_bytes(16)), $after);
    sameValue((int) $season['id'], (int) $back['season_id']);
    sameValue($after->format('Y-m-d H:i:s'), $back['transferred_at']);
    sameValue(2, transferCount($accountId));
});

check('Случайный предмет выбирается из каталога и сохраняется после переноса', function () use ($database, $ownerId): void {
    $id = testAccount('EUW', 3000);
    expectDomainFailure(fn () => addRandomDemoItem($database, $id, $ownerId + 1), 'Аккаунт не найден');
    $name = addRandomDemoItem($database, $id, $ownerId);
    $catalog = $database->query('SELECT name FROM item_catalog')->fetchAll(PDO::FETCH_COLUMN);
    sameValue(true, in_array($name, $catalog, true));
    $items = accountItems($database, $id);
    sameValue([['item_name' => $name, 'quantity' => 1]], $items);
    tryTransfer($id, 'EUW', 'ASIA');
    sameValue($items, accountItems($database, $id));
});

check('Случайное удаление убирает один экземпляр и переносит оставшийся инвентарь', function () use ($database, $ownerId): void {
    $id = testAccount('EUW', 3000);
    $insert = $database->prepare('INSERT INTO account_items (account_id, item_name, quantity) VALUES (?, ?, ?)');
    $insert->execute([$id, 'Берестяной оберег', 2]);
    expectDomainFailure(fn () => removeRandomDemoItem($database, $id, $ownerId + 1), 'Аккаунт не найден');
    sameValue('Берестяной оберег', removeRandomDemoItem($database, $id, $ownerId));
    $items = [['item_name' => 'Берестяной оберег', 'quantity' => 1]];
    sameValue($items, accountItems($database, $id));
    tryTransfer($id, 'EUW', 'ASIA');
    sameValue($items, accountItems($database, $id));
    sameValue('Берестяной оберег', removeRandomDemoItem($database, $id, $ownerId));
    sameValue([], accountItems($database, $id));
    sameValue(null, removeRandomDemoItem($database, $id, $ownerId));
});

check('Откат восстанавливает демо-профили с предметами, рангами и исходными регионами', function () use ($database, $ownerId, $now, $regionIds): void {
    $demoOwner = (int) $database->query("SELECT id FROM users WHERE username = 'demo'")->fetchColumn();
    $otherId = testAccount();
    $otherBefore = accountState($otherId);
    resetDemoAccounts($database, $demoOwner, $now);
    $find = $database->prepare('SELECT id FROM accounts WHERE owner_id = ? AND player_name = ?');
    $find->execute([$demoOwner, 'Яромир']);
    $id = (int) $find->fetchColumn();
    $beforeItems = accountItems($database, $id);

    $database->prepare('INSERT INTO accounts (owner_id, player_name, current_region_id) VALUES (?, ?, ?)')
        ->execute([$demoOwner, 'Демо-тест-' . bin2hex(random_bytes(6)), $regionIds['EUNE']]);
    $createdId = (int) $database->lastInsertId();
    assignMatch($database, $createdId, $demoOwner, $now);
    addRandomDemoItem($database, $createdId, $demoOwner);
    addDemoGold($database, $id, $demoOwner);
    confirmTestTransfer($database, $id, $demoOwner, (int) $regionIds['EUW'], (int) accountState($id)['transfer_version'],
        (int) $regionIds['ASIA'], bin2hex(random_bytes(16)), $now);
    $match = assignMatch($database, $id, $demoOwner, $now);
    finishMatch($database, $id, $demoOwner, (int) $match['id'],
        demoResult($database, accountState($id), (int) $match['id'], 'win'), $now);
    removeRandomDemoItem($database, $id, $demoOwner);
    sameValue(true, removeOwnedAccount($database, $id, $demoOwner));
    advanceDemoTime($database, $demoOwner, $now);

    resetDemoAccounts($database, $demoOwner, $now);
    sameValue($now->getTimestamp(), gameTime($database, $demoOwner, $now)->getTimestamp());
    $restored = accountByOwner($database, $id, $demoOwner);
    sameValue('EUW', $restored['region_code']);
    sameValue(3000, (int) $restored['gold']);
    sameValue(1800, (int) $restored['mmr']);
    sameValue($beforeItems, accountItems($database, $id));
    sameValue(null, matchByAccount($database, (int) $match['id'], $id));
    sameValue(0, transferCount($id));
    $findCreated = $database->prepare('SELECT COUNT(*) FROM accounts WHERE id = ?');
    $findCreated->execute([$createdId]);
    sameValue(0, (int) $findCreated->fetchColumn());
    sameValue($otherBefore, accountState($otherId));
    $season = requireSeason($database, $now);
    $rank = openRanking($database, $id, (int) $season['id'], (int) $regionIds['EUW']);
    sameValue('Изумруд II', $rank['confirmed_rank']);
    sameValue(45, (int) $rank['rank_points']);
    $first = assignMatch($database, $id, $demoOwner, $now);
    sameValue(0, (int) $first['account_number']);
    resetDemoAccounts($database, $demoOwner, $now);
});
