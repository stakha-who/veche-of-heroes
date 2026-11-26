<h1><?= e($account['player_name']) ?></h1>
<?php if ($notice !== null): ?>
    <p role="status"><?= e($notice) ?></p>
<?php endif; ?>
<dl>
    <dt>Регион</dt><dd><?= e($account['region_name']) ?> (<?= e($account['region_code']) ?>)</dd>
    <dt>Уровень</dt><dd><?= e($account['level']) ?></dd>
    <dt>Кошелёк</dt><dd><?= e($account['gold']) ?> золотых</dd>
</dl>
<?php if (getenv('APP_DEMO') === '1'): ?>
<form action="/account/demo/gold" method="post">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="account_id" value="<?= e($account['id']) ?>">
    <button type="submit">Добавить <?= e(TRANSFER_PRICE) ?> золотых</button>
</form>
<?php endif; ?>

<section>
    <h2>Рейтинг</h2>
    <?php if ($season !== null): ?>
        <p><?= e($season['name']) ?></p>
        <?php if (($ranking['confirmed_rank'] ?? null) !== null): ?>
            <p><strong><?= e($ranking['confirmed_rank']) ?></strong> · <?= e($ranking['rank_points']) ?> очков ранга</p>
        <?php else: ?>
            <p>Калибровка · <?= e($ranking['placement_games'] ?? 0) ?> из <?= e(PLACEMENT_GAMES) ?> матчей</p>
            <?php if (($ranking['provisional_rank'] ?? null) !== null): ?>
                <p>Предварительный ранг — <?= e($ranking['provisional_rank']) ?> · <?= e($ranking['rank_points']) ?> из <?= e(POINTS_PER_RANK) ?> очков ранга</p>
            <?php endif; ?>
            <?php if ($olderRank !== null): ?>
                <p>Прошлый сезон — <?= e($olderRank['confirmed_rank']) ?></p>
            <?php endif; ?>
        <?php endif; ?>
    <?php else: ?>
        <p>Сейчас нет активного сезона.</p>
    <?php endif; ?>
</section>
<?php if ($ranks): ?>
    <details>
        <summary>История рангов</summary>
        <ul>
        <?php foreach ($ranks as $rank): ?>
            <li><?= e($rank['season_name']) ?> · <?= e($rank['region_code']) ?> · <?= e($rank['confirmed_rank']) ?></li>
        <?php endforeach; ?>
        </ul>
    </details>
<?php endif; ?>
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
<p><a href="/transfer/history?id=<?= e($account['id']) ?>">История переносов</a></p>
<form action="/match" method="post">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="account_id" value="<?= e($account['id']) ?>">
    <button type="submit">Начать матч</button>
</form>
<form action="/account/delete" method="post" onsubmit="return confirm('Удалить этот аккаунт?');">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="account_id" value="<?= e($account['id']) ?>">
    <button type="submit">Удалить аккаунт</button>
</form>
