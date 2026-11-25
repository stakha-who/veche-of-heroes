<?php
declare(strict_types=1);

check('EUW → ASIA: одно списание и одна запись', function () use ($regionIds): void {
    $id = testAccount();
    $receipt = tryTransfer($id, 'EUW', 'ASIA');
    sameValue(2600, (int) $receipt['cost']);
    sameValue(400, (int) accountState($id)['gold']);
    sameValue((int) $regionIds['ASIA'], (int) accountState($id)['current_region_id']);
    sameValue(1, transferCount($id));
});

check('KR и RU: разрешения проверяются по направлению', function (): void {
    $korea = testAccount('KR');
    tryTransfer($korea, 'KR', 'EUW');
    $cases = [
        ['EUW', 'KR', 'Входящий перенос'],
        ['EUW', 'RU', 'Входящий перенос'],
        ['RU', 'EUW', 'Выезд из России'],
    ];
    foreach ($cases as [$source, $target, $message]) {
        $id = testAccount($source);
        expectDomainFailure(fn () => tryTransfer($id, $source, $target), $message);
        sameValue(3000, (int) accountState($id)['gold']);
        sameValue(0, transferCount($id));
    }
});

check('Кулдаун: отказ за секунду до границы и успех на границе', function () use ($now): void {
    $boundary = $now->sub(new DateInterval('PT2160H'));
    $early = testAccount('EUW', 3000, $boundary->add(new DateInterval('PT1S')));
    expectDomainFailure(fn () => tryTransfer($early, 'EUW', 'ASIA'), '90 восходов');
    sameValue(3000, (int) accountState($early)['gold']);
    $ready = testAccount('EUW', 3000, $boundary);
    tryTransfer($ready, 'EUW', 'ASIA');
    sameValue(400, (int) accountState($ready)['gold']);
});

check('Нехватка золотых не меняет профиль', function (): void {
    $id = testAccount('EUW', 2599);
    $before = accountState($id);
    expectDomainFailure(fn () => tryTransfer($id, 'EUW', 'ASIA'), 'Недостаточно золотых');
    sameValue($before, accountState($id));
    sameValue(0, transferCount($id));
});

check('Повтор подтверждения возвращает исходную запись', function (): void {
    $id = testAccount();
    $key = bin2hex(random_bytes(16));
    $first = tryTransfer($id, 'EUW', 'ASIA', $key);
    $second = tryTransfer($id, 'EUW', 'ASIA', $key);
    sameValue($first, $second);
    expectDomainFailure(fn () => tryTransfer($id, 'EUW', 'EUNE', $key), 'другими условиями');
    sameValue(400, (int) accountState($id)['gold']);
    sameValue(1, transferCount($id));
});

check('Вторая форма с прежней версией аккаунта отклоняется', function (): void {
    $id = testAccount('EUW', 6000);
    tryTransfer($id, 'EUW', 'ASIA');
    expectDomainFailure(fn () => tryTransfer($id, 'EUW', 'EUNE'), 'Условия устарели');
    sameValue(3400, (int) accountState($id)['gold']);
    sameValue(1, transferCount($id));
});

check('Ошибка сохранения истории откатывает всю операцию', function () use ($database): void {
    $id = testAccount();
    $before = accountState($id);
    $key = bin2hex(random_bytes(16));
    rejectHistoryInsert($key, function () use ($id, $before, $key): void {
        try {
            tryTransfer($id, 'EUW', 'ASIA', $key);
            throw new RuntimeException('База должна была отклонить запись истории.');
        } catch (PDOException $exception) {
            sameValue(3819, (int) ($exception->errorInfo[1] ?? 0));
        }
        sameValue($before, accountState($id));
        sameValue(0, transferCount($id));
    });
});

check('После выезда из KR обратный перенос закрыт даже после кулдауна', function () use ($database, $ownerId, $regionIds, $now): void {
    $id = testAccount('KR', 8000);
    tryTransfer($id, 'KR', 'EUW');
    $afterDeparture = accountState($id);
    $later = $now->add(new DateInterval('PT2160H'));
    expectDomainFailure(fn () => performTransfer($database, $id, $ownerId, (int) $regionIds['EUW'], 1,
        (int) $regionIds['KR'], bin2hex(random_bytes(16)), $later), 'Входящий перенос');
    sameValue($afterDeparture, accountState($id));
    sameValue(1, transferCount($id));
});

check('Владелец не может перенести чужой аккаунт', function () use ($database, $ownerId, $regionIds, $now): void {
    $id = testAccount();
    $before = accountState($id);
    expectDomainFailure(fn () => performTransfer($database, $id, $ownerId + 1, (int) $regionIds['EUW'], 0,
        (int) $regionIds['ASIA'], bin2hex(random_bytes(16)), $now), 'Аккаунт не найден');
    sameValue($before, accountState($id));
    sameValue(0, transferCount($id));
});

check('Закрытый целевой регион отклоняется до списания', function () use ($database, &$regionIds): void {
    $code = 'STOP' . bin2hex(random_bytes(2));
    $database->prepare('INSERT INTO regions (code, name, is_available) VALUES (?, ?, 0)')
        ->execute([$code, 'Закрытый регион для проверки']);
    $regionIds[$code] = $database->lastInsertId();
    $id = testAccount();
    $before = accountState($id);
    expectDomainFailure(fn () => tryTransfer($id, 'EUW', $code), 'Регион назначения сейчас недоступен');
    sameValue($before, accountState($id));
    sameValue(0, transferCount($id));
});
