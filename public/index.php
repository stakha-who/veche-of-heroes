<?php
declare(strict_types=1);

require __DIR__ . '/../app/database.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
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

startSession();

$routes = [
    'GET /' => static fn () => render('home'),
    'GET /accounts' => static function (): void {
        requireLogin();
        render('message', ['pageTitle' => 'Кабинет', 'message' => 'Вход выполнен.']);
    },
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
