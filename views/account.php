<h1><?= e($account['player_name']) ?></h1>
<dl>
    <dt>Регион</dt><dd><?= e($account['region_name']) ?> (<?= e($account['region_code']) ?>)</dd>
    <dt>Уровень</dt><dd><?= e($account['level']) ?></dd>
    <dt>Кошелёк</dt><dd><?= e($account['gold']) ?> золотых</dd>
</dl>
<h2>Инвентарь</h2>
<?php if (!$items): ?>
    <p>В инвентаре пока нет предметов.</p>
<?php else: ?>
    <ul>
    <?php foreach ($items as $item): ?>
        <li><?= e($item['item_name']) ?> × <?= e($item['quantity']) ?></li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>
<p><a href="/transfer?id=<?= e($account['id']) ?>">Перенести аккаунт</a></p>
