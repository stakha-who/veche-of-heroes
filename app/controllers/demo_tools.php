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

function demoResetSubmit(): void
{
    $ownerId = requireLogin();
    if (getenv('APP_DEMO') !== '1' || !validCsrf()) {
        render('message', ['pageTitle' => 'Откат недоступен', 'message' => 'Вернись в список аккаунтов.'], 403);
        return;
    }
    try {
        resetDemoAccounts(db(), $ownerId, new DateTimeImmutable());
        unset($_SESSION['account_action'], $_SESSION['transfer_notice'], $_SESSION['csrf']);
        $_SESSION['accounts_notice'] = 'Стартовые демонстрационные аккаунты восстановлены.';
        redirect('/accounts');
    } catch (DomainException $exception) {
        render('message', ['pageTitle' => 'Откат недоступен', 'message' => $exception->getMessage()], 422);
    }
}

function demoTimeSubmit(): void
{
    $account = demoAccountFromPost();
    if ($account === null) {
        return;
    }
    try {
        advanceDemoTime(db(), (int) $account['owner_id'], new DateTimeImmutable());
        $message = 'Демовремя переведено на 90 дней вперёд для всех твоих аккаунтов.';
    } catch (DomainException $exception) {
        $message = $exception->getMessage();
    }
    $_SESSION['account_action'] = ['account_id' => (int) $account['id'], 'text' => $message];
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
