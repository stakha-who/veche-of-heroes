<?php
$demo = getenv('APP_DEMO') === '1';
$months = [
    1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
    'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря',
];
?>
<p class="chapter-label"><span class="chapter-number" aria-hidden="true">01</span> ПРОФИЛЬ</p>
<header class="dark-panel profile-card<?= $demo ? ' profile-card--demo' : '' ?>">
    <?php if ($demo): ?>
        <aside class="date-plaque" aria-label="Демонстрационное время кабинета">
            <span class="date-tab" aria-hidden="true"></span>
            <svg class="icon date-compass" aria-hidden="true"><use href="#icon-compass"/></svg>
            <span class="date-label">Демодата</span>
            <time datetime="<?= e($now->format(DATE_ATOM)) ?>">
                <span><?= e($now->format('Y')) ?></span>
                <strong><?= e($now->format('j')) ?></strong>
                <span><?= e($months[(int) $now->format('n')]) ?></span>
                <span class="date-time"><?= e($now->format('H:i')) ?> UTC</span>
            </time>
        </aside>
    <?php endif; ?>
    <div class="profile-identity">
        <h1><?= e($account['player_name']) ?></h1>
        <p class="profile-region"><span class="region-mark"><?= e($account['region_code']) ?></span>
            <?= e($account['region_name']) ?></p>
        <dl class="profile-facts">
            <div>
                <dt><svg class="icon" aria-hidden="true"><use href="#icon-coins"/></svg> Кошелёк</dt>
                <dd><?= e($account['gold']) ?> <span>золотых</span></dd>
            </div>
            <?php if ($demo): ?>
                <div>
                    <dt><svg class="icon" aria-hidden="true"><use href="#icon-mark"/></svg> Скрытый рейтинг (MMR)</dt>
                    <dd><?= e($account['mmr']) ?></dd>
                </div>
            <?php endif; ?>
        </dl>
        <div class="actions profile-actions">
            <a class="button" href="/transfer?id=<?= e($account['id']) ?>">Перенести аккаунт</a>
            <a class="button button--secondary" href="/transfer/history?id=<?= e($account['id']) ?>"><svg class="icon" aria-hidden="true"><use href="#icon-scroll"/></svg> История переносов</a>
        </div>
    </div>
    <div class="profile-badge">
        <div class="hero-portrait hero-portrait--<?= e(accountAvatarId($account)) ?>" aria-hidden="true"></div>
        <svg class="branch-engraving" aria-hidden="true"><use href="#icon-branch"/></svg>
        <dl class="level-plaque">
            <dt>Уровень</dt>
            <dd><?= e($account['level']) ?></dd>
        </dl>
    </div>
</header>

<?php if ($notice !== null): ?>
    <p class="notice" role="status"><?= e($notice) ?></p>
<?php endif; ?>

<div class="profile-grid">
    <section class="profile-rank" aria-labelledby="profile-rank">
        <h2 id="profile-rank" class="chapter-label"><span class="chapter-number" aria-hidden="true">02</span> РЕЙТИНГ</h2>
        <div class="paper-panel rank-card">
            <span class="paper-bookmark" aria-hidden="true"><svg class="icon"><use href="#icon-leaf"/></svg></span>
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
                    <progress class="placement-progress" aria-label="Матчи калибровки" value="<?= e($ranking['placement_games'] ?? 0) ?>" max="<?= e(PLACEMENT_GAMES) ?>"></progress>
                    <?php if (($ranking['provisional_rank'] ?? null) !== null): ?>
                        <p>Предварительный ранг — <?= e($ranking['provisional_rank']) ?> · <?= e($ranking['rank_points']) ?> из <?= e(POINTS_PER_RANK) ?> очков ранга</p>
                    <?php endif; ?>
                    <?php if ($olderRank !== null): ?>
                        <p><?= e($olderRank['season_name']) ?> — <?= e($olderRank['confirmed_rank']) ?></p>
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
                <button class="button--secondary" type="submit"><svg class="icon" aria-hidden="true"><use href="#icon-swords"/></svg> Начать матч</button>
            </form>
        </div>
    </section>

    <section class="profile-inventory" aria-labelledby="profile-inventory">
        <h2 id="profile-inventory" class="chapter-label"><span class="chapter-number" aria-hidden="true">03</span> ИНВЕНТАРЬ</h2>
        <div class="paper-panel inventory-panel">
            <?php if (!$items): ?>
                <p>В инвентаре пока нет предметов.</p>
            <?php else: ?>
                <ul class="inventory">
                    <?php foreach ($items as $item): ?>
                        <?php
                        $itemIcon = match ($item['item_name']) {
                            'Обережный пояс', 'Кованая пряжка' => 'buckle',
                            'Ратный клинок', 'Сосновое копьё' => 'swords',
                            'Липовый щит', 'Медные наручи' => 'shield',
                            'Серебряная гривна' => 'coins',
                            default => 'mark',
                        };
                        ?>
                        <li>
                            <div class="inventory-slot">
                                <svg class="icon" aria-hidden="true"><use href="#icon-<?= e($itemIcon) ?>"/></svg>
                                <strong>×<?= e($item['quantity']) ?></strong>
                            </div>
                            <span><?= e($item['item_name']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php if ($demo): ?>
    <section class="profile-demo" aria-labelledby="profile-demo">
        <h2 id="profile-demo" class="chapter-label"><span class="chapter-number" aria-hidden="true">04</span> ДЕМОРЕЖИМ</h2>
        <div class="dark-panel demo-panel actions">
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
