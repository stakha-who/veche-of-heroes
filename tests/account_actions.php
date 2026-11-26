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
