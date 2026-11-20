<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path === '/health') {
    require __DIR__ . '/../app/database.php';
    header('Content-Type: text/plain; charset=utf-8');
    try {
        connectDatabase()->query('SELECT 1');
        echo 'OK';
    } catch (PDOException $exception) {
        http_response_code(503);
        echo 'База данных недоступна';
    }
    return;
}
if ($path !== '/') {
    http_response_code(404);
    echo 'Страница не найдена';
    return;
}
require __DIR__ . '/../views/home.php';
