<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="only light">
    <title><?= e($pageTitle ?? 'Вече героев') ?> · Вече героев</title>
    <link rel="stylesheet" href="/assets/site.css">
</head>
<body>
    <header class="site-header">
        <div class="site-header-inner">
            <a class="brand" href="/"><span class="brand-mark" aria-hidden="true"></span>Вече героев</a>
            <?php if (currentUserId() !== null): ?>
                <nav class="site-nav" aria-label="Навигация кабинета">
                    <a href="/accounts"<?= $view === 'accounts' ? ' aria-current="page"' : '' ?>>Мои аккаунты</a>
                </nav>
                <form class="session-form" action="/logout" method="post">
                    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                    <button class="button--secondary" type="submit">Выйти</button>
                </form>
            <?php endif; ?>
        </div>
    </header>
    <main class="shell view-<?= e($view) ?>">
        <?php require __DIR__ . '/' . $view . '.php'; ?>
    </main>
</body>
</html>
