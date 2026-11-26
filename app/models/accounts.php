<?php
declare(strict_types=1);

function accountsByOwner(PDO $database, int $ownerId): array
{
    $query = $database->prepare(
        'SELECT a.*, r.code AS region_code, r.name AS region_name
         FROM accounts a JOIN regions r ON r.id = a.current_region_id
         WHERE a.owner_id = ? AND a.deleted_at IS NULL ORDER BY a.id'
    );
    $query->execute([$ownerId]);
    return $query->fetchAll();
}

function accountByOwner(PDO $database, int $accountId, int $ownerId): ?array
{
    $query = $database->prepare(
        'SELECT a.*, r.code AS region_code, r.name AS region_name
         FROM accounts a JOIN regions r ON r.id = a.current_region_id
         WHERE a.id = ? AND a.owner_id = ? AND a.deleted_at IS NULL'
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
    $input = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? ($_POST['account_id'] ?? null) : ($_GET['id'] ?? null);
    $accountId = readId($input);
    $account = accountByOwner(db(), $accountId, $ownerId);
    if ($account === null) {
        render('message', ['pageTitle' => 'Аккаунт не найден', 'message' => 'Открой аккаунт из своего кабинета.'], 404);
    }
    return $account;
}

function removeOwnedAccount(PDO $database, int $accountId, int $ownerId): bool
{
    $query = $database->prepare(
        'UPDATE accounts SET deleted_at = UTC_TIMESTAMP()
         WHERE id = ? AND owner_id = ? AND deleted_at IS NULL'
    );
    $query->execute([$accountId, $ownerId]);
    return $query->rowCount() === 1;
}
