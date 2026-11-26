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
