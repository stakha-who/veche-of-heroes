<?php
declare(strict_types=1);

function lockedAccount(PDO $database, int $accountId, int $ownerId): array
{
    $query = $database->prepare('SELECT * FROM accounts WHERE id = ? AND owner_id = ? FOR UPDATE');
    $query->execute([$accountId, $ownerId]);
    $account = $query->fetch();
    if (!$account) {
        throw new DomainException('Аккаунт не найден в твоём кабинете.');
    }
    return $account;
}

function transferReceipt(PDO $database, int $accountId, string $operationKey): ?array
{
    $query = $database->prepare('SELECT * FROM transfers WHERE account_id = ? AND operation_key = ?');
    $query->execute([$accountId, $operationKey]);
    return $query->fetch() ?: null;
}

function performTransfer(
    PDO $database,
    int $accountId,
    int $ownerId,
    int $sourceId,
    int $sourceVersion,
    int $targetId,
    string $operationKey,
    DateTimeImmutable $now
): array {
    if (!preg_match('/\A[0-9a-f]{32}\z/', $operationKey)) {
        throw new DomainException('Открой условия переноса и подтверди операцию заново.');
    }

    $database->beginTransaction();
    try {
        $account = lockedAccount($database, $accountId, $ownerId);
        $previous = transferReceipt($database, $accountId, $operationKey);
        if ($previous !== null) {
            if ((int) $previous['source_region_id'] !== $sourceId
                || (int) $previous['source_version'] !== $sourceVersion
                || (int) $previous['target_region_id'] !== $targetId) {
                throw new DomainException('Этот номер операции уже использован с другими условиями.');
            }
            $database->commit();
            return $previous;
        }
        if ((int) $account['current_region_id'] !== $sourceId
            || (int) $account['transfer_version'] !== $sourceVersion) {
            throw new DomainException('Условия устарели. Проверь их заново.');
        }
        $quote = transferQuote($database, $account, $targetId, $now);
        if ($quote['error'] !== null) {
            throw new DomainException($quote['error']);
        }

        $time = $now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $goldAfter = (int) $account['gold'] - TRANSFER_PRICE;
        $update = $database->prepare(
            'UPDATE accounts
             SET current_region_id = ?, gold = ?, last_transfer_at = ?, transfer_version = transfer_version + 1
             WHERE id = ?'
        );
        $update->execute([$targetId, $goldAfter, $time, $accountId]);

        $save = $database->prepare(
            'INSERT INTO transfers
                (account_id, source_region_id, target_region_id, source_version, operation_key, cost, gold_after, transferred_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $save->execute([$accountId, $account['current_region_id'], $targetId, $account['transfer_version'],
            $operationKey, TRANSFER_PRICE, $goldAfter, $time]);
        $receipt = transferReceipt($database, $accountId, $operationKey);
        $database->commit();
        return $receipt;
    } catch (Throwable $exception) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }
        throw $exception;
    }
}
