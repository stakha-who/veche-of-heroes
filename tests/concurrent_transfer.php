<?php
declare(strict_types=1);

function waitForTransferLocks(PDO $database, array $workers): void
{
    $connections = [];
    $deadline = microtime(true) + 8;
    do {
        foreach ($workers as $number => [$process, $output]) {
            if (isset($connections[$number])) {
                continue;
            }
            $line = fgets($output);
            if ($line !== false) {
                $connectionId = (int) trim($line);
                if ($connectionId <= 0) {
                    throw new RuntimeException('Не удалось подключить процесс к MySQL: ' . trim($line));
                }
                $connections[$number] = $connectionId;
            } elseif (!proc_get_status($process)['running']) {
                throw new RuntimeException('Процесс переноса завершился до подключения к MySQL.');
            }
        }
        $waiting = 0;
        foreach ($database->query('SHOW FULL PROCESSLIST')->fetchAll() as $connection) {
            if ($connection['Command'] === 'Execute'
                && in_array((int) $connection['Id'], $connections, true)
                && str_contains(strtoupper((string) $connection['Info']), 'FOR UPDATE')) {
                $waiting++;
            }
        }
        if ($waiting === count($workers)) {
            return;
        }
        usleep(20000);
    } while (microtime(true) < $deadline);
    throw new RuntimeException('Два запроса не дошли до блокировки записи аккаунта.');
}

check('Два одновременных подтверждения дают одно списание и одну запись', function () use ($database, $ownerId, $regionIds, $now): void {
    $id = testAccount('EUW', 6000);
    $targetId = (int) $regionIds['ASIA'];
    $workers = [];
    $results = [];
    $keys = [bin2hex(random_bytes(16)), bin2hex(random_bytes(16))];
    $database->beginTransaction();
    try {
        lockedAccount($database, $id, $ownerId);
        foreach ($keys as $key) {
            $command = [PHP_BINARY, __DIR__ . '/transfer_worker.php', (string) $id, (string) $ownerId,
                (string) $regionIds['EUW'], (string) $targetId, $key, $now->format(DATE_ATOM)];
            $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['redirect', 1]], $pipes);
            if ($process === false) {
                throw new RuntimeException('Не удалось запустить процесс переноса.');
            }
            $workers[] = [$process, $pipes[1]];
            stream_set_blocking($pipes[1], false);
        }
        waitForTransferLocks($database, $workers);
        $database->rollBack();

        $deadline = microtime(true) + 10;
        foreach ($workers as [$process, $output]) {
            while (proc_get_status($process)['running'] && microtime(true) < $deadline) {
                usleep(20000);
            }
            if (proc_get_status($process)['running']) {
                throw new RuntimeException('Процесс переноса не завершился после снятия блокировки.');
            }
            $results[] = trim(stream_get_contents($output));
        }
    } finally {
        if ($database->inTransaction()) {
            $database->rollBack();
        }
        foreach ($workers as [$process, $output]) {
            if (proc_get_status($process)['running']) {
                proc_terminate($process);
            }
            fclose($output);
            proc_close($process);
        }
    }

    sort($results);
    sameValue(['ok', 'stale'], $results);
    $account = accountState($id);
    sameValue($targetId, (int) $account['current_region_id']);
    sameValue(3400, (int) $account['gold']);
    sameValue(1, (int) $account['transfer_version']);
    sameValue(1, transferCount($id));
});
