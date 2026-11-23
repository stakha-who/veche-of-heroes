<?php
declare(strict_types=1);

function availableMatchServer(PDO $database, int $regionId): ?array
{
    $query = $database->prepare(
        'SELECT s.id, s.name, r.code AS region_code
         FROM match_servers s JOIN regions r ON r.id = s.region_id
         WHERE s.region_id = ? AND s.is_available = 1 AND r.is_available = 1
         ORDER BY s.id LIMIT 1'
    );
    $query->execute([$regionId]);
    return $query->fetch() ?: null;
}
