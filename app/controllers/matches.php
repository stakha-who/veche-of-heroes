<?php
declare(strict_types=1);

function matchStart(): void
{
    if (!validCsrf()) {
        render('message', ['pageTitle' => 'Форма устарела', 'message' => 'Открой профиль заново.'], 403);
        return;
    }
    $account = ownedAccountFromRequest();
    if ($account === null) {
        return;
    }
    $server = availableMatchServer(db(), (int) $account['current_region_id']);
    if ($server === null) {
        render('message', ['pageTitle' => 'Матч недоступен', 'message' => 'В текущем регионе нет доступных серверов матча.'], 422);
        return;
    }
    render('match', ['pageTitle' => 'Сервер матча', 'account' => $account, 'server' => $server]);
}
