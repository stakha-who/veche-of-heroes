<?php
declare(strict_types=1);

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
