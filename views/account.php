<h1><?= e($account['player_name']) ?></h1>
<?php if ($notice !== null): ?>
    <p role="status"><?= e($notice) ?></p>
<?php endif; ?>
<dl>
    <dt>Регион</dt><dd><?= e($account['region_name']) ?> (<?= e($account['region_code']) ?>)</dd>
    <dt>Уровень</dt><dd><?= e($account['level']) ?></dd>
    <dt>Кошелёк</dt><dd><?= e($account['gold']) ?> золотых</dd>
    <?php if (getenv('APP_DEMO') === '1'): ?>
        <dt>Скрытый рейтинг (MMR, демо)</dt><dd><?= e($account['mmr']) ?></dd>
    <?php endif; ?>
</dl>
<?php if (getenv('APP_DEMO') === '1'): ?>
<form action="/account/demo/gold" method="post">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="account_id" value="<?= e($account['id']) ?>">
    <button type="submit">Добавить <?= e(TRANSFER_PRICE) ?> золотых</button>
</form>
    <p>Демовремя кабинета — <time datetime="<?= e($now->format(DATE_ATOM)) ?>"><?= e($now->format('d.m.Y H:i')) ?> UTC</time></p>
    <form action="/account/demo/time" method="post">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="account_id" value="<?= e($account['id']) ?>">
        <button type="submit">Пропустить 90 восходов солнца</button>
    </form>
<?php endif; ?>

<section>
    <h2>Рейтинг</h2>

    <?php if ($season !== null): ?>
        <?php
        $seasonEnd = new DateTimeImmutable($season['ends_at'], new DateTimeZone('UTC'));
        $seasonChange = match ($seasonEnd->format('m-d')) {
            '01-01' => '1 января ' . $seasonEnd->format('Y'),
            '07-01' => '1 июля ' . $seasonEnd->format('Y'),
            default => $seasonEnd->format('d.m.Y'),
        };
        ?>
        <p><?= e($season['name']) ?></p>
        <p>Смена сезона — <?= e($seasonChange) ?>, <?= e($seasonEnd->format('H:i')) ?> UTC.</p>
        <?php if (($ranking['confirmed_rank'] ?? null) !== null): ?>
            <p><strong><?= e($ranking['confirmed_rank']) ?></strong> · <?= e($ranking['rank_points']) ?> из <?= e(POINTS_PER_RANK) ?> очков ранга</p>
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
<?php if (getenv('APP_DEMO') === '1'): ?>
    <form action="/account/demo/item/add" method="post">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="account_id" value="<?= e($account['id']) ?>">
        <button type="submit">Добавить случайный предмет</button>
    </form>
    <form action="/account/demo/item/remove" method="post">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="account_id" value="<?= e($account['id']) ?>">
        <button type="submit"<?= $items ? '' : ' disabled' ?>>Удалить случайный предмет</button>
    </form>
<?php endif; ?>
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
