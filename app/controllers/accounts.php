<?php
declare(strict_types=1);

function accountsPage(): void
{
    $ownerId = requireLogin();
    render('accounts', ['pageTitle' => 'Мои аккаунты', 'accounts' => accountsByOwner(db(), $ownerId)]);
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
    render('account', [
        'pageTitle' => $account['player_name'],
        'account' => $account,
        'notice' => $notice['text'] ?? null,
        'items' => accountItems(db(), (int) $account['id']),
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
