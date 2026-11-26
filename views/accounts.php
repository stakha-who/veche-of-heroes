<h1>Мои аккаунты</h1>
<?php if ($notice !== null): ?>
    <p role="status"><?= e($notice) ?></p>
<?php endif; ?>
<p><a href="/account/new">Создать аккаунт</a></p>
<?php if (!$accounts): ?>
    <p>Игровых аккаунтов пока нет.</p>
<?php else: ?>
    <ul>
        <?php foreach ($accounts as $account): ?>
            <li><a href="/account?id=<?= e($account['id']) ?>"><?= e($account['player_name']) ?></a>
                · <?= e($account['region_code']) ?> · <?= e($account['gold']) ?> золотых</li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if (getenv('APP_DEMO') === '1'): ?>
    <form action="/accounts/demo/reset" method="post" onsubmit="return confirm('Восстановить стартовые демонстрационные аккаунты? Созданные вручную аккаунты и изменения будут удалены.');">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <button type="submit">Откатить изменения</button>
    </form>
<?php endif; ?>
