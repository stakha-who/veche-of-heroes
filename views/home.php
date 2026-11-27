<section class="dark-panel home-panel">
    <span class="profile-seal" aria-hidden="true"></span>
    <h1>Вече героев</h1>
    <p>Личный кабинет игрока</p>
    <a class="button" href="<?= currentUserId() === null ? '/login' : '/accounts' ?>">Открыть кабинет</a>
</section>
