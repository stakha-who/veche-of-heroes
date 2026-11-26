<?php
declare(strict_types=1);

check('Удалить аккаунт может только владелец, удалённый профиль закрыт', function () use ($database, $ownerId): void {
    $id = testAccount();
    sameValue(false, removeOwnedAccount($database, $id, $ownerId + 1));
    sameValue(true, removeOwnedAccount($database, $id, $ownerId));
    sameValue(null, accountByOwner($database, $id, $ownerId));
    expectDomainFailure(fn () => tryTransfer($id, 'EUW', 'ASIA'), 'Аккаунт не найден');
});
