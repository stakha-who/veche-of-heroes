<?php
declare(strict_types=1);

function activeSeason(PDO $database, DateTimeImmutable $time): ?array
{
    $utc = $time->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    $query = $database->prepare(
        'SELECT * FROM seasons WHERE starts_at <= ? AND ends_at > ? ORDER BY starts_at DESC LIMIT 1'
    );
    $query->execute([$utc, $utc]);
    return $query->fetch() ?: null;
}

function requireSeason(PDO $database, DateTimeImmutable $time): array
{
    $season = activeSeason($database, $time);
    if ($season === null) {
        throw new DomainException('Сейчас нет активного рейтингового сезона.');
    }
    return $season;
}
