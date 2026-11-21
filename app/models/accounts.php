<?php
declare(strict_types=1);

function accountsByOwner(PDO $database, int $ownerId): array
{
    $query = $database->prepare(
        'SELECT a.*, r.code AS region_code, r.name AS region_name
         FROM accounts a JOIN regions r ON r.id = a.current_region_id
         WHERE owner_id = ? ORDER BY a.id'
    );
    $query->execute([$ownerId]);
    return $query->fetchAll();
}

function accountByOwner(PDO $database, int $accountId, int $ownerId): ?array
{
    $query = $database->prepare(
        'SELECT a.*, r.code AS region_code, r.name AS region_name
         FROM accounts a JOIN regions r ON r.id = a.current_region_id
         WHERE a.id = ? AND a.owner_id = ?'
    );
    $query->execute([$accountId, $ownerId]);
    return $query->fetch() ?: null;
}

function accountItems(PDO $database, int $accountId): array
{
    $query = $database->prepare('SELECT item_name, quantity FROM account_items WHERE account_id = ? ORDER BY item_name');
    $query->execute([$accountId]);
    return $query->fetchAll();
}

function availableRegions(PDO $database): array
{
    return $database->query('SELECT id, code, name FROM regions WHERE is_available = 1 ORDER BY id')->fetchAll();
}

function ownedAccountFromRequest(): ?array
{
    $ownerId = requireLogin();
    $accountId = readId($_GET['id'] ?? $_POST['account_id'] ?? null);
    $account = accountByOwner(db(), $accountId, $ownerId);
    if ($account === null) {
        render('message', ['pageTitle' => 'Аккаунт не найден', 'message' => 'Открой аккаунт из своего кабинета.'], 404);
    }
    return $account;
}
