<?php
declare(strict_types=1);

function transferPage(): void
{
    $account = ownedAccountFromRequest();
    if ($account === null) {
        return;
    }
    renderTransferForm($account, readId($_GET['target'] ?? null));
}

function renderTransferForm(array $account, int $targetId, ?string $notice = null): void
{
    $regions = db()->query('SELECT id, code, name FROM regions ORDER BY id')->fetchAll();
    $now = gameTime(db(), (int) $account['owner_id']);
    $quote = $targetId > 0 ? transferQuote(db(), $account, $targetId, $now) : null;

    $season = null;
    $provisionalRank = null;
    if ($quote !== null && $quote['error'] === null) {
        $season = activeSeason(db(), $now);
        if ($season === null) {
            $quote['error'] = 'Сейчас нет активного рейтингового сезона.';
        } else {
            $provisionalRank = transferProvisionalRank(
                db(), (int) $account['id'], (int) $season['id'], (int) $account['current_region_id']
            );
        }
    }

    render('transfer', [
        'pageTitle' => 'Перенос аккаунта',
        'account' => $account,
        'regions' => $regions,
        'targetId' => $targetId,
        'quote' => $quote,
        'now' => $now,
        'season' => $season,
        'provisionalRank' => $provisionalRank,
        'notice' => $notice,
        'operationKey' => bin2hex(random_bytes(16)),
    ], $notice === null ? 200 : 409);
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
    $targetId = readId(postValue('target_id'));
    $rankField = $_POST['provisional_rank'] ?? null;
    $shownSeasonId = is_string($rankField) ? readId(postValue('season_id')) : 0;
    $shownRank = is_string($rankField) && $rankField !== '' ? $rankField : null;
    try {
        $receipt = performTransfer(
            db(), $accountId, $ownerId, readId(postValue('source_id')),
            $sourceVersion === false ? -1 : $sourceVersion, $targetId,
            postValue('operation_key'), $shownSeasonId, $shownRank
        );
        $_SESSION['transfer_notice'] = [
            'account_id' => $accountId,
            'text' => 'Перенос завершён. Стоимость — ' . $receipt['cost'] . ' золотых.',
        ];
        redirect('/account?id=' . $accountId);
    } catch (StaleTransferConditions $exception) {
        $account = accountByOwner(db(), $accountId, $ownerId);
        if ($account === null) {
            render('message', ['pageTitle' => 'Аккаунт не найден', 'message' => 'Открой аккаунт из своего кабинета.'], 404);
            return;
        }
        renderTransferForm($account, $targetId, $exception->getMessage());
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
