<section class="dark-panel home-panel">
    <div class="home-crest" aria-hidden="true">
        <svg class="home-emblem" viewBox="0 0 80 80" fill="none">
            <path class="home-emblem-inner" d="M40 7 73 40 40 73 7 40Z"/>
            <path d="M40 1 79 40 40 79 1 40Z"/>
            <path d="M40 16 64 40 40 64 16 40Z"/>
            <use href="#icon-mark" x="24" y="24" width="32" height="32"/>
            <path class="home-emblem-pins" d="M40 8l2 2-2 2-2-2ZM70 38l2 2-2 2-2-2ZM40 68l2 2-2 2-2-2ZM10 38l2 2-2 2-2-2Z"/>
        </svg>
    </div>
    <h1>Вече героев</h1>
    <p>Личный кабинет игрока</p>
    <a class="button home-entry" href="<?= currentUserId() === null ? '/login' : '/accounts' ?>">
        Открыть кабинет
        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
    </a>
</section>
