<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path !== '/') {
    http_response_code(404);
    echo 'Страница не найдена';
    return;
}
require __DIR__ . '/../views/home.php';
