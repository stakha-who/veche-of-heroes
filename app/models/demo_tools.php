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
