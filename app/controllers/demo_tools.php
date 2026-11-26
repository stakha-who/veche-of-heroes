<?php
declare(strict_types=1);

function demoAccountFromPost(): ?array
{
    requireLogin();
    if (getenv('APP_DEMO') !== '1' || !validCsrf()) {
        render('message', ['pageTitle' => 'Действие недоступно', 'message' => 'Вернись на страницу аккаунта.'], 403);
        return null;
    }
    return ownedAccountFromRequest();
}

function demoGoldSubmit(): void
{
    $account = demoAccountFromPost();
    if ($account === null) {
        return;
    }
    try {
        addDemoGold(db(), (int) $account['id'], (int) $account['owner_id']);
        $_SESSION['account_action'] = ['account_id' => (int) $account['id'], 'text' => 'Кошелёк пополнен.'];
        redirect('/account?id=' . $account['id']);
    } catch (DomainException $exception) {
        render('message', ['pageTitle' => 'Пополнение отклонено', 'message' => $exception->getMessage()], 422);
    }
}

function demoCooldownSubmit(): void
{
    $account = demoAccountFromPost();
    if ($account === null) {
        return;
    }
    skipDemoCooldown(db(), (int) $account['id'], (int) $account['owner_id']);
    $_SESSION['account_action'] = ['account_id' => (int) $account['id'], 'text' => 'Ожидание переноса пропущено.'];
    redirect('/account?id=' . $account['id']);
}

function demoItemAddSubmit(): void
{
    $account = demoAccountFromPost();
    if ($account === null) {
        return;
    }
    try {
        $name = addRandomDemoItem(db(), (int) $account['id'], (int) $account['owner_id']);
        $_SESSION['account_action'] = ['account_id' => (int) $account['id'], 'text' => 'Добавлен предмет «' . $name . '».'];
        redirect('/account?id=' . $account['id']);
    } catch (DomainException $exception) {
        render('message', ['pageTitle' => 'Предмет не добавлен', 'message' => $exception->getMessage()], 422);
    }
}

function demoItemRemoveSubmit(): void
{
    $account = demoAccountFromPost();
    if ($account === null) {
        return;
    }
    try {
        $name = removeRandomDemoItem(db(), (int) $account['id'], (int) $account['owner_id']);
        $text = $name === null ? 'Инвентарь уже пуст.' : 'Удалён предмет «' . $name . '».';
        $_SESSION['account_action'] = ['account_id' => (int) $account['id'], 'text' => $text];
        redirect('/account?id=' . $account['id']);
    } catch (DomainException $exception) {
        render('message', ['pageTitle' => 'Предмет не удалён', 'message' => $exception->getMessage()], 422);
    }
}
