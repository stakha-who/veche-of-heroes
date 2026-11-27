<?php
declare(strict_types=1);

const DEMO_TIME_STEP_DAYS = 90;

function gameTime(PDO $database, int $ownerId, ?DateTimeImmutable $now = null): DateTimeImmutable
{
    $utc = new DateTimeZone('UTC');
    $now = $now === null ? new DateTimeImmutable('now', $utc) : $now->setTimezone($utc);
    if (getenv('APP_DEMO') !== '1') {
        return $now;
    }

    $query = $database->prepare('SELECT demo_days_elapsed FROM users WHERE id = ?');
    $query->execute([$ownerId]);
    return $now->modify('+' . (int) $query->fetchColumn() . ' days');
}

function advanceDemoTime(PDO $database, int $ownerId, DateTimeImmutable $now): void
{
    $database->beginTransaction();
    try {
        $query = $database->prepare('SELECT demo_days_elapsed FROM users WHERE id = ? FOR UPDATE');
        $query->execute([$ownerId]);
        $days = $query->fetchColumn();
        if ($days === false) {
            throw new DomainException('Кабинет не найден.');
        }

        $nextDays = (int) $days + DEMO_TIME_STEP_DAYS;
        $nextTime = $now->setTimezone(new DateTimeZone('UTC'))->modify('+' . $nextDays . ' days');
        if (activeSeason($database, $nextTime) === null) {
            throw new DomainException('Календарь сезонов до этой даты ещё не настроен.');
        }

        $update = $database->prepare('UPDATE users SET demo_days_elapsed = ? WHERE id = ?');
        $update->execute([$nextDays, $ownerId]);
        $database->commit();
    } catch (Throwable $exception) {
        $database->rollBack();
        throw $exception;
    }
}
