<?php
declare(strict_types=1);

function transferPage(): void
{
    $account = ownedAccountFromRequest();
    if ($account === null) {
        return;
    }
    $targetId = readId($_GET['target'] ?? null);
    $regions = db()->query('SELECT id, code, name FROM regions ORDER BY id')->fetchAll();
    $now = gameTime(db(), (int) $account['owner_id']);
    $quote = $targetId > 0 ? transferQuote(db(), $account, $targetId, $now) : null;

    $provisionalRank = null;
    if ($quote !== null && $quote['error'] === null) {
        $season = activeSeason(db(), $now);
        if ($season !== null) {
            $ranking = openRanking(db(), (int) $account['id'], (int) $season['id'], (int) $account['current_region_id']);
            $provisionalRank = $ranking['confirmed_rank'] ?? null;
        }
    }

    render('transfer', [
        'pageTitle' => 'Перенос аккаунта',
        'account' => $account,
        'regions' => $regions,
        'targetId' => $targetId,
        'quote' => $quote,
        'now' => $now,
        'provisionalRank' => $provisionalRank,
        'operationKey' => bin2hex(random_bytes(16)),
    ]);
}

function transferSubmit(): void
{
    $ownerId = requireLogin();
    $accountId = readId(postValue('account_id'));
    if (!validCsrf()) {
        render('message', ['pageTitle' => 'Форма устарела', 'message' => 'Открой условия переноса заново.'], 403);
        return;
    }
    if (accountByOwner(db(), $accountId, $ownerId) === null) {
        render('message', ['pageTitle' => 'Аккаунт не найден', 'message' => 'Открой аккаунт из своего кабинета.'], 404);
        return;
    }
    $sourceVersion = filter_var(postValue('source_version'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    try {
        $receipt = performTransfer(
            db(), $accountId, $ownerId, readId(postValue('source_id')),
            $sourceVersion === false ? -1 : $sourceVersion, readId(postValue('target_id')),
            postValue('operation_key'), gameTime(db(), $ownerId)
        );
        $_SESSION['transfer_notice'] = [
            'account_id' => $accountId,
            'text' => 'Перенос завершён. Стоимость — ' . $receipt['cost'] . ' золотых.',
        ];
        redirect('/account?id=' . $accountId);
    } catch (DomainException $exception) {
        render('message', [
            'pageTitle' => 'Перенос отклонён',
            'message' => $exception->getMessage(),
        ], 422);
    }
}

function transferHistoryPage(): void
{
    $account = ownedAccountFromRequest();
    if ($account === null) {
        return;
    }
    render('history', [
        'pageTitle' => 'История переносов',
        'account' => $account,
        'history' => transferHistory(db(), (int) $account['id']),
    ]);
}
