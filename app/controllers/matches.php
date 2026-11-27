<?php
declare(strict_types=1);

function matchStart(): void
{
    $ownerId = requireLogin();
    if (!validCsrf()) {
        render('message', ['pageTitle' => 'Форма устарела', 'message' => 'Открой профиль заново.'], 403);
        return;
    }
    $account = ownedAccountFromRequest();
    if ($account === null) {
        return;
    }
    try {
        $match = assignMatch(db(), (int) $account['id'], $ownerId, gameTime(db(), $ownerId));
        redirect('/match?id=' . $account['id'] . '&game=' . $match['id']);
    } catch (DomainException $exception) {
        render('message', ['pageTitle' => 'Матч недоступен', 'message' => $exception->getMessage()], 422);
    }
}

function matchPage(): void
{
    $account = ownedAccountFromRequest();
    if ($account === null) {
        return;
    }
    $matchId = readId($_GET['game'] ?? null);
    $match = matchByAccount(db(), $matchId, (int) $account['id']);
    if ($match === null) {
        render('message', ['pageTitle' => 'Матч не найден', 'message' => 'Запусти матч из профиля.'], 404);
        return;
    }
    render('match', ['pageTitle' => 'Матч', 'account' => $account, 'match' => $match]);
}

function demoMatchSubmit(): void
{
    $ownerId = requireLogin();
    if (getenv('APP_DEMO') !== '1' || !validCsrf()) {
        render('message', ['pageTitle' => 'Действие недоступно', 'message' => 'Демонстрационный результат недоступен.'], 403);
        return;
    }
    $account = ownedAccountFromRequest();
    if ($account === null) {
        return;
    }
    $matchId = readId(postValue('match_id'));
    $outcome = postValue('outcome');
    try {
        $result = demoResult(db(), $account, $matchId, $outcome);
        finishMatch(db(), (int) $account['id'], $ownerId, $matchId, $result, gameTime(db(), $ownerId));
        redirect('/match?id=' . $account['id'] . '&game=' . $matchId);
    } catch (DomainException $exception) {
        render('message', ['pageTitle' => 'Результат отклонён', 'message' => $exception->getMessage()], 422);
    }
}
