<?php
declare(strict_types=1);

const TRANSFER_PRICE = 2600;

function regionById(PDO $database, int $id): ?array
{
    $query = $database->prepare('SELECT id, code, name, is_available FROM regions WHERE id = ?');
    $query->execute([$id]);
    return $query->fetch() ?: null;
}

function routeAllowed(PDO $database, int $sourceId, int $targetId): bool
{
    $query = $database->prepare(
        'SELECT 1 FROM transfer_routes WHERE source_region_id = ? AND target_region_id = ?'
    );
    $query->execute([$sourceId, $targetId]);
    return $query->fetchColumn() !== false;
}

function transferQuote(PDO $database, array $account, int $targetId, DateTimeImmutable $now): array
{
    $sourceId = (int) $account['current_region_id'];
    $source = regionById($database, $sourceId);
    $target = regionById($database, $targetId);
    $quote = [
        'source' => $source,
        'target' => $target,
        'price' => TRANSFER_PRICE,
        'error' => null,
    ];

    if ($target === null) {
        $quote['error'] = 'Выбери существующий регион назначения.';
    } elseif ($sourceId === $targetId) {
        $quote['error'] = 'Аккаунт уже находится в этом регионе.';
    } elseif (!(bool) $target['is_available']) {
        $quote['error'] = 'Регион назначения сейчас недоступен.';
    } elseif (!routeAllowed($database, $sourceId, $targetId)) {
        if ($source['code'] === 'RU') {
            $quote['error'] = 'Выезд из России закрыт.';
        } elseif ($target['code'] === 'RU' || $target['code'] === 'KR') {
            $quote['error'] = 'Входящий перенос в этот регион закрыт.';
        } else {
            $quote['error'] = 'Перенос по этому маршруту недоступен.';
        }
    } elseif ((int) $account['gold'] < TRANSFER_PRICE) {
        $quote['error'] = 'Недостаточно золотых для переноса.';
    }

    return $quote;
}
