<header class="dark-panel page-banner">
    <a class="back-link" href="/account?id=<?= e($account['id']) ?>"><span aria-hidden="true">←</span> <?= e($account['player_name']) ?></a>
    <h1>Перенос аккаунта</h1>
    <p class="profile-region"><span class="region-mark"><?= e($account['region_code']) ?></span>
        <?= e($account['gold']) ?> золотых</p>
</header>
<?php if ($notice !== null && $quote === null): ?>
    <p class="notice" role="alert"><?= e($notice) ?></p>
<?php endif; ?>

<div class="transfer-grid<?= $quote === null ? ' transfer-grid--empty' : '' ?>">
    <section class="paper-panel destination-panel">
        <h2>Куда перенести</h2>
        <form action="/transfer" method="get">
            <input type="hidden" name="id" value="<?= e($account['id']) ?>">
            <label>Регион назначения
                <select name="target" required>
                    <option value="">Выбери регион</option>
                    <?php foreach ($regions as $region): ?>
                        <option value="<?= e($region['id']) ?>" <?= (int) $region['id'] === $targetId ? 'selected' : '' ?>>
                            <?= e($region['name']) ?> (<?= e($region['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="button--secondary" type="submit">Проверить условия</button>
        </form>
    </section>

    <?php if ($quote !== null): ?>
        <section class="paper-panel transfer-quote">
            <h2>Условия переноса</h2>
            <?php if ($notice !== null): ?>
                <p class="notice" role="alert"><?= e($notice) ?></p>
            <?php endif; ?>
            <?php if ($quote['target'] !== null): ?>
                <div class="route">
                    <div>
                        <span class="route-label">Откуда</span>
                        <strong><?= e($quote['source']['code']) ?></strong>
                        <span><?= e($quote['source']['name']) ?></span>
                    </div>
                    <span class="route-arrow" aria-hidden="true">→</span>
                    <div>
                        <span class="route-label">Куда</span>
                        <strong><?= e($quote['target']['code']) ?></strong>
                        <span><?= e($quote['target']['name']) ?></span>
                    </div>
                </div>
            <?php endif; ?>
            <dl class="quote-facts">
                <div>
                    <dt>Стоимость</dt>
                    <dd><?= e($quote['price']) ?> золотых</dd>
                </div>
                <div>
                    <dt>Сейчас в кошельке</dt>
                    <dd><?= e($account['gold']) ?> золотых</dd>
                </div>
                <?php if ($quote['error'] === null): ?>
                    <div>
                        <dt>После переноса</dt>
                        <dd><?= e((int) $account['gold'] - $quote['price']) ?> золотых</dd>
                    </div>
                <?php endif; ?>
            </dl>
            <ul class="quote-details">
                <li>Уровень и инвентарь сохраняются.</li>
                <li>В новом регионе начнётся калибровка. Матчей для завершения: <?= e(PLACEMENT_GAMES) ?>.</li>
                <?php if ($season !== null): ?>
                    <li>Текущий сезон: <?= e($season['name']) ?>.</li>
                    <li>Предварительный ранг: <?= e($provisionalRank ?? 'не назначен') ?>.</li>
                <?php endif; ?>
                <li>Интервал между переносами — 90 восходов солнца.</li>
            </ul>
            <?php if ($quote['next_at'] !== null && $quote['next_at'] > $now): ?>
                <p>Следующая попытка доступна с <?= e($quote['next_at']->format('d.m.Y H:i:s')) ?> UTC.</p>
            <?php endif; ?>
            <?php if ($quote['source']['code'] === 'KR' && $quote['target'] !== null && $quote['target']['code'] !== 'KR'): ?>
                <p class="notice" role="note">После выезда из Кореи вернуться в KR переносом нельзя.</p>
            <?php endif; ?>
            <?php if ($quote['error'] !== null): ?>
                <p class="notice notice--error" role="alert"><?= e($quote['error']) ?></p>
            <?php else: ?>
                <form class="quote-confirm" action="/transfer" method="post">
                    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="account_id" value="<?= e($account['id']) ?>">
                    <input type="hidden" name="source_id" value="<?= e($account['current_region_id']) ?>">
                    <input type="hidden" name="source_version" value="<?= e($account['transfer_version']) ?>">
                    <input type="hidden" name="target_id" value="<?= e($targetId) ?>">
                    <input type="hidden" name="season_id" value="<?= e($season['id']) ?>">
                    <input type="hidden" name="provisional_rank" value="<?= e($provisionalRank ?? '') ?>">
                    <input type="hidden" name="operation_key" value="<?= e($operationKey) ?>">
                    <button type="submit">Перенести за <?= e($quote['price']) ?> золотых</button>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
