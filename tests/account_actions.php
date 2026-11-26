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

check('Пропуск ожидания сохраняет историю и запреты маршрутов', function () use ($database, $ownerId): void {
    $id = testAccount('EUW', 6000);
    tryTransfer($id, 'EUW', 'ASIA');
    expectDomainFailure(fn () => tryTransfer($id, 'ASIA', 'EUW', null, 1), '90 восходов');
    skipDemoCooldown($database, $id, $ownerId);
    sameValue(1, transferCount($id));
    sameValue(3400, (int) accountState($id)['gold']);
    expectDomainFailure(fn () => tryTransfer($id, 'ASIA', 'KR', null, 1), 'Входящий перенос');
    tryTransfer($id, 'ASIA', 'EUW', null, 1);
    sameValue(2, transferCount($id));
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
    performTransfer($database, $id, $demoOwner, (int) $regionIds['EUW'], (int) accountState($id)['transfer_version'],
        (int) $regionIds['ASIA'], bin2hex(random_bytes(16)), $now);
    $match = assignMatch($database, $id, $demoOwner, $now);
    finishMatch($database, $id, $demoOwner, (int) $match['id'],
        demoResult($database, accountState($id), (int) $match['id'], 'win'), $now);
    removeRandomDemoItem($database, $id, $demoOwner);
    sameValue(true, removeOwnedAccount($database, $id, $demoOwner));

    resetDemoAccounts($database, $demoOwner, $now);
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
