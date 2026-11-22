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

check('Нехватка золотых и недоступный маршрут не меняют профиль', function (): void {
    $id = testAccount('EUW', 2599);
    $before = accountState($id);
    expectDomainFailure(fn () => tryTransfer($id, 'EUW', 'ASIA'), 'Недостаточно золотых');
    sameValue($before, accountState($id));
    sameValue(0, transferCount($id));
});
