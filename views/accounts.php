<h1>Мои аккаунты</h1>
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
