<header class="dark-panel page-banner page-banner--action">
    <h1>Мои аккаунты</h1>
    <a class="button" href="/account/new">Создать аккаунт</a>
</header>
<?php if ($notice !== null): ?>
    <p class="notice" role="status"><?= e($notice) ?></p>
<?php endif; ?>
<section class="paper-panel roster-panel" aria-label="Игровые аккаунты">
    <?php if (!$accounts): ?>
        <p>Игровых аккаунтов пока нет.</p>
    <?php else: ?>
        <ul class="roster">
            <?php foreach ($accounts as $account): ?>
                <li>
                    <a href="/account?id=<?= e($account['id']) ?>"><?= e($account['player_name']) ?></a>
                    <span class="region-mark"><?= e($account['region_code']) ?></span>
                    <span class="roster-balance"><?= e($account['gold']) ?> золотых</span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php if (getenv('APP_DEMO') === '1'): ?>
    <form class="roster-reset" action="/accounts/demo/reset" method="post" onsubmit="return confirm('Восстановить стартовые демонстрационные аккаунты? Созданные вручную аккаунты и изменения будут удалены.');">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <button class="button--secondary" type="submit">Откатить изменения</button>
    </form>
<?php endif; ?>
