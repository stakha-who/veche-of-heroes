<?php
declare(strict_types=1);

function accountsPage(): void
{
    $ownerId = requireLogin();
    $notice = $_SESSION['accounts_notice'] ?? null;
    unset($_SESSION['accounts_notice']);
    render('accounts', [
        'pageTitle' => 'Мои аккаунты',
        'accounts' => accountsByOwner(db(), $ownerId),
        'notice' => $notice,
    ]);
}

function accountPage(): void
{
    $account = ownedAccountFromRequest();
    if ($account === null) {
        return;
    }
    $notice = $_SESSION['transfer_notice'] ?? null;
    if ($notice !== null && $notice['account_id'] === (int) $account['id']) {
        unset($_SESSION['transfer_notice']);
    } else {
        $notice = null;
    }
    $action = $_SESSION['account_action'] ?? null;
    if ($action !== null && $action['account_id'] === (int) $account['id']) {
        unset($_SESSION['account_action']);
        $notice = $action;
    }
    $database = db();
    $now = gameTime($database, (int) $account['owner_id']);
    $season = activeSeason($database, $now);
    $ranking = $season === null ? null : openRanking(
        $database, (int) $account['id'], (int) $season['id'], (int) $account['current_region_id']
    );
    $olderRank = $season === null || ($ranking['confirmed_rank'] ?? null) !== null
        ? null : olderSeasonRank($database, (int) $account['id'], $season['starts_at']);

    render('account', [
        'pageTitle' => $account['player_name'],
        'account' => $account,
        'notice' => $notice['text'] ?? null,
        'items' => accountItems($database, (int) $account['id']),
        'season' => $season,
        'now' => $now,
        'ranking' => $ranking,
        'olderRank' => $olderRank,
        'ranks' => rankingHistory($database, (int) $account['id']),
    ]);
}

function accountCreatePage(string $message = '', int $status = 200): void
{
    requireLogin();
    render('account-new', [
        'pageTitle' => 'Создать аккаунт',
        'message' => $message,
        'regions' => availableRegions(db()),
        'playerName' => trim(postValue('player_name')),
        'selectedRegion' => readId(postValue('region_id')),
    ], $status);
}

function accountCreateSubmit(): void
{
    $ownerId = requireLogin();
    if (!validCsrf()) {
        accountCreatePage('Форма устарела. Открой её заново.', 403);
        return;
    }
    $name = trim(postValue('player_name'));
    $regionId = readId(postValue('region_id'));
    if (!preg_match('/\A[\p{L}\p{N} _-]{2,30}\z/u', $name) || $regionId === 0) {
        accountCreatePage('Укажи имя из 2–30 букв, цифр или пробелов и выбери регион.', 422);
        return;
    }

    try {
        $query = db()->prepare(
            'INSERT INTO accounts (owner_id, player_name, current_region_id)
             SELECT ?, ?, id FROM regions WHERE id = ? AND is_available = 1'
        );
        $query->execute([$ownerId, $name, $regionId]);
        if ($query->rowCount() !== 1) {
            accountCreatePage('Этот регион сейчас недоступен.', 422);
            return;
        }
    } catch (PDOException $exception) {
        if (($exception->errorInfo[1] ?? null) !== 1062) {
            throw $exception;
        }
        accountCreatePage('Такое имя игрока уже занято.', 422);
        return;
    }

    redirect('/account?id=' . db()->lastInsertId());
}

function accountDeleteSubmit(): void
{
    $ownerId = requireLogin();
    if (!validCsrf()) {
        render('message', ['pageTitle' => 'Форма устарела', 'message' => 'Открой профиль заново.'], 403);
        return;
    }
    $accountId = readId(postValue('account_id'));
    if (!removeOwnedAccount(db(), $accountId, $ownerId)) {
        render('message', ['pageTitle' => 'Аккаунт не найден', 'message' => 'Открой аккаунт из своего кабинета.'], 404);
        return;
    }
    $_SESSION['accounts_notice'] = 'Аккаунт удалён.';
    redirect('/accounts');
}
