<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="only light">
    <title><?= e($pageTitle ?? 'Вече героев') ?> · Вече героев</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&amp;family=Cormorant+SC:wght@500;600&amp;family=EB+Garamond:wght@400;500&amp;display=swap">
    <link rel="stylesheet" href="/assets/site.css">
</head>
<body>
    <!--
    Icons: Copyright (c) 2026 Lucide Icons and Contributors, ISC License.
    Permission to use, copy, modify, and/or distribute this software for any
    purpose with or without fee is hereby granted, provided that the above
    copyright notice and this permission notice appear in all copies.
    THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
    WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
    MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
    ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
    WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
    ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
    OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
    The compass is derived from Feather.
    Copyright (c) 2013-present Cole Bemis, MIT License.
    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:
    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.
    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
    SOFTWARE.
    -->
    <svg class="icon-library" xmlns="http://www.w3.org/2000/svg" width="0" height="0" aria-hidden="true">
        <symbol id="icon-compass" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"/>
            <path d="m16.24 7.76-1.804 5.411a2 2 0 0 1-1.265 1.265L7.76 16.24l1.804-5.411a2 2 0 0 1 1.265-1.265z"/>
        </symbol>
        <symbol id="icon-coins" viewBox="0 0 24 24">
            <path d="M13.744 17.736a6 6 0 1 1-7.48-7.48M15 6h1v4m-9.866 4.768.866-.5 2 3.464"/>
            <circle cx="16" cy="8" r="6"/>
        </symbol>
        <symbol id="icon-swords" viewBox="0 0 24 24">
            <path d="m13 19 6-6m-4.5 4.5L3.586 6.586A2 2 0 0 1 3 5.172V3h2.172a2 2 0 0 1 1.414.586L17.5 14.5m-2.672-8.328 2.586-2.586A2 2 0 0 1 18.828 3H21v2.172a2 2 0 0 1-.586 1.414l-2.586 2.586M16 16l4 4m-1 1 2-2M5 14l4 4m-4 3-2-2m4.5-2.5L4 20"/>
        </symbol>
        <symbol id="icon-leaf" viewBox="0 0 24 24">
            <path d="M11 20a10 10 0 0 0 10-10 25.9 25.9 0 0 0-1.04-7.281 1 1 0 0 0-1.755-.325C15.833 5.5 13 5.5 9.8 6.1A7 7 0 0 0 11 20M2 21a5 5 0 0 1 2.911-4.544C7.613 15.212 8.351 15.24 11 13"/>
        </symbol>
        <symbol id="icon-shield" viewBox="0 0 24 24">
            <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>
        </symbol>
        <symbol id="icon-scroll" viewBox="0 0 24 24">
            <path d="M15 12h-5m5-4h-5m9 9V5a2 2 0 0 0-2-2H4m4 18h12a2 2 0 0 0 2-2v-1a1 1 0 0 0-1-1H11a1 1 0 0 0-1 1v1a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v2a1 1 0 0 0 1 1h3"/>
        </symbol>
        <symbol id="icon-buckle" viewBox="0 0 24 24">
            <path d="M2 9h4m12 0h4v6h-4M6 15H2V9m6-3h8l2 2v8l-2 2H8l-2-2V8zm2 3h4v6h-4zm4 3h5"/>
        </symbol>
        <symbol id="icon-mark" viewBox="0 0 24 24">
            <path d="m12 2 10 10-10 10L2 12zM12 7l5 5-5 5-5-5z"/>
        </symbol>
        <symbol id="icon-branch" viewBox="0 0 48 48">
            <path d="M7 41C18 35 30 21 35 8M16 34c-7 1-10-3-11-8 6-1 11 2 12 7m5-5c0-9-4-12-9-14-2 6 0 12 8 15m5-4c8 2 13-2 16-7-6-3-12-1-16 7m5-7c-2-7 1-12 6-15 3 6 1 11-6 15M20 31c6 2 11 0 14-3"/>
        </symbol>
    </svg>
    <header class="site-header">
        <div class="site-header-inner">
            <a class="brand" href="/"><svg class="icon brand-mark" aria-hidden="true"><use href="#icon-mark"/></svg>Вече героев</a>
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
