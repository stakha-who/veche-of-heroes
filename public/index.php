<?php
declare(strict_types=1);

require __DIR__ . '/../app/database.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (PHP_SAPI === 'cli-server' && $path === '/assets/site.css') {
    return false;
}
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($path === '/health') {
    header('Content-Type: text/plain; charset=utf-8');
    try {
        db()->query('SELECT 1');
        echo 'OK';
    } catch (PDOException $exception) {
        http_response_code(503);
        echo 'База данных недоступна';
    }
    return;
}

require __DIR__ . '/../app/view.php';
require __DIR__ . '/../app/session.php';
require __DIR__ . '/../app/controllers/auth.php';
require __DIR__ . '/../app/models/accounts.php';
require __DIR__ . '/../app/controllers/accounts.php';
require __DIR__ . '/../app/models/transfers.php';
require __DIR__ . '/../app/models/seasons.php';
require __DIR__ . '/../app/models/rankings.php';
require __DIR__ . '/../app/models/transfer_service.php';
require __DIR__ . '/../app/controllers/transfers.php';
require __DIR__ . '/../app/models/matches.php';
require __DIR__ . '/../app/models/demo_results.php';
require __DIR__ . '/../app/controllers/matches.php';

startSession();

$routes = [
    'GET /' => static fn () => render('home'),
    'GET /accounts' => 'accountsPage',
    'GET /account' => 'accountPage',
    'GET /account/new' => 'accountCreatePage',
    'POST /account/new' => 'accountCreateSubmit',
    'GET /transfer' => 'transferPage',
    'GET /transfer/history' => 'transferHistoryPage',
    'POST /transfer' => 'transferSubmit',
    'POST /match' => 'matchStart',
    'GET /match' => 'matchPage',
    'POST /match/demo' => 'demoMatchSubmit',
    'GET /login' => 'loginPage',
    'POST /login' => 'loginSubmit',
    'POST /logout' => 'logoutSubmit',
];
try {
    $handler = $routes[$method . ' ' . $path] ?? null;
    if ($handler === null) {
        render('message', ['pageTitle' => 'Страница не найдена', 'message' => 'Проверь адрес страницы.'], 404);
        return;
    }
    $handler();
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    render('message', ['pageTitle' => 'Ошибка приложения', 'message' => 'Попробуй открыть страницу ещё раз.'], 500);
}
