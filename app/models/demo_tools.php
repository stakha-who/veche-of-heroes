<?php
declare(strict_types=1);

function addDemoGold(PDO $database, int $accountId, int $ownerId): void
{
    $query = $database->prepare(
        'UPDATE accounts SET gold = gold + ?
         WHERE id = ? AND owner_id = ? AND deleted_at IS NULL AND gold <= ?'
    );
    $query->execute([TRANSFER_PRICE, $accountId, $ownerId, 4294967295 - TRANSFER_PRICE]);
    if ($query->rowCount() !== 1) {
        throw new DomainException('Не удалось пополнить кошелёк.');
    }
}

function skipDemoCooldown(PDO $database, int $accountId, int $ownerId): void
{
    $query = $database->prepare(
        'UPDATE accounts SET last_transfer_at = NULL WHERE id = ? AND owner_id = ? AND deleted_at IS NULL'
    );
    $query->execute([$accountId, $ownerId]);
}

function addRandomDemoItem(PDO $database, int $accountId, int $ownerId): string
{
    $database->beginTransaction();
    try {
        lockedAccount($database, $accountId, $ownerId);
        $name = $database->query('SELECT name FROM item_catalog ORDER BY RAND() LIMIT 1')->fetchColumn();
        if ($name === false) {
            throw new DomainException('Каталог предметов пуст.');
        }
        $query = $database->prepare(
            'INSERT INTO account_items (account_id, item_name, quantity) VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE quantity = account_items.quantity + 1'
        );
        $query->execute([$accountId, $name]);
        $database->commit();
        return $name;
    } catch (Throwable $exception) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }
        throw $exception;
    }
}
