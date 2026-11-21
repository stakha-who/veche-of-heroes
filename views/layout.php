<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Вече героев') ?> · Вече героев</title>
</head>
<body>
    <header>
        <a href="/">Вече героев</a>
        <?php if (currentUserId() !== null): ?>
            <a href="/accounts">Мои аккаунты</a>
            <form action="/logout" method="post">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <button type="submit">Выйти</button>
            </form>
        <?php endif; ?>
    </header>
    <main>
        <?php require __DIR__ . '/' . $view . '.php'; ?>
    </main>
</body>
</html>
