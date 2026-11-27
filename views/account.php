<?php $demo = getenv('APP_DEMO') === '1'; ?>
<header class="dark-panel profile-card">
    <div class="profile-top">
        <div class="profile-heading">
            <span class="profile-seal" aria-hidden="true"></span>
            <div>
                <h1><?= e($account['player_name']) ?></h1>
                <p class="profile-region"><span class="region-mark"><?= e($account['region_code']) ?></span>
                    <?= e($account['region_name']) ?></p>
            </div>
        </div>
        <div class="actions profile-actions">
            <a class="button" href="/transfer?id=<?= e($account['id']) ?>">Перенести аккаунт</a>
            <a class="button button--secondary" href="/transfer/history?id=<?= e($account['id']) ?>">История переносов</a>
        </div>
    </div>
    <dl class="profile-facts">
        <div>
            <dt>Уровень</dt>
            <dd><?= e($account['level']) ?></dd>
        </div>
        <div>
            <dt>Кошелёк</dt>
            <dd><?= e($account['gold']) ?> <span>золотых</span></dd>
        </div>
        <?php if ($demo): ?>
            <div>
                <dt>Скрытый рейтинг (MMR, демо)</dt>
                <dd><?= e($account['mmr']) ?></dd>
            </div>
        <?php endif; ?>
    </dl>
</header>

<?php if ($notice !== null): ?>
    <p class="notice" role="status"><?= e($notice) ?></p>
<?php endif; ?>

<div class="profile-grid">
    <section class="paper-panel">
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
            <div class="season">
                <strong><?= e($season['name']) ?></strong>
                <p>Смена сезона — <?= e($seasonChange) ?>, <?= e($seasonEnd->format('H:i')) ?> UTC.</p>
            </div>
            <?php if (($ranking['confirmed_rank'] ?? null) !== null): ?>
                <p class="rank-name"><strong><?= e($ranking['confirmed_rank']) ?></strong></p>
                <p class="progress-label"><?= e($ranking['rank_points']) ?> из <?= e(POINTS_PER_RANK) ?> очков ранга</p>
                <progress aria-label="Очки ранга" value="<?= e($ranking['rank_points']) ?>" max="<?= e(POINTS_PER_RANK) ?>"></progress>
            <?php else: ?>
                <p class="rank-name"><strong>Калибровка</strong></p>
                <p class="progress-label"><?= e($ranking['placement_games'] ?? 0) ?> из <?= e(PLACEMENT_GAMES) ?> матчей</p>
                <progress aria-label="Матчи калибровки" value="<?= e($ranking['placement_games'] ?? 0) ?>" max="<?= e(PLACEMENT_GAMES) ?>"></progress>
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
        <?php if ($ranks): ?>
            <details class="rank-history">
                <summary>История рангов</summary>
                <ul class="rank-list">
                    <?php foreach ($ranks as $rank): ?>
                        <li><?= e($rank['season_name']) ?> · <?= e($rank['region_code']) ?> · <?= e($rank['confirmed_rank']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endif; ?>
        <form action="/match" method="post">
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="account_id" value="<?= e($account['id']) ?>">
            <button class="button--secondary" type="submit">Начать матч</button>
        </form>
    </section>

    <section class="paper-panel">
        <h2>Инвентарь</h2>
        <?php if (!$items): ?>
            <p>В инвентаре пока нет предметов.</p>
        <?php else: ?>
            <ul class="inventory">
                <?php foreach ($items as $item): ?>
                    <li><span><?= e($item['item_name']) ?></span> <strong>× <?= e($item['quantity']) ?></strong></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<?php if ($demo): ?>
    <section class="dark-panel demo-panel">
        <div class="demo-clock">
            <h2>Деморежим</h2>
            <p>Демовремя кабинета — <time datetime="<?= e($now->format(DATE_ATOM)) ?>"><?= e($now->format('d.m.Y H:i')) ?> UTC</time></p>
        </div>
        <div class="actions">
            <form action="/account/demo/time" method="post">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="account_id" value="<?= e($account['id']) ?>">
                <button class="button--secondary" type="submit">Пропустить 90 восходов солнца</button>
            </form>
            <form action="/account/demo/gold" method="post">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="account_id" value="<?= e($account['id']) ?>">
                <button class="button--secondary" type="submit">Добавить <?= e(TRANSFER_PRICE) ?> золотых</button>
            </form>
            <form action="/account/demo/item/add" method="post">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="account_id" value="<?= e($account['id']) ?>">
                <button class="button--secondary" type="submit">Добавить случайный предмет</button>
            </form>
            <form action="/account/demo/item/remove" method="post">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="account_id" value="<?= e($account['id']) ?>">
                <button class="button--secondary" type="submit"<?= $items ? '' : ' disabled' ?>>Удалить случайный предмет</button>
            </form>
        </div>
    </section>
<?php endif; ?>

<form class="profile-delete" action="/account/delete" method="post" onsubmit="return confirm('Удалить этот аккаунт?');">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="account_id" value="<?= e($account['id']) ?>">
    <button class="button--danger" type="submit">Удалить аккаунт</button>
</form>
