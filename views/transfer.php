<h1>Перенос аккаунта</h1>
<p><a href="/account?id=<?= e($account['id']) ?>"><?= e($account['player_name']) ?></a>
    · <?= e($account['region_code']) ?> · <?= e($account['gold']) ?> золотых</p>
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
    <button type="submit">Проверить условия</button>
</form>

<?php if ($quote !== null): ?>
    <section>
        <h2>Условия переноса</h2>
        <?php if ($quote['target'] !== null): ?>
            <p><?= e($quote['source']['code']) ?> → <?= e($quote['target']['code']) ?></p>
        <?php endif; ?>
        <p>Стоимость: <strong><?= e($quote['price']) ?> золотых</strong></p>
        <p>Уровень и инвентарь переезжают с аккаунтом.</p>
        <p>На новом сервере начнётся калибровка. Матчей для завершения: <?= e(PLACEMENT_GAMES) ?>.</p>
        <?php if ($provisionalRank !== null): ?>
            <p>Предварительный ранг — <?= e($provisionalRank) ?>.</p>
        <?php endif; ?>
        <p>Интервал между переносами — 90 восходов солнца.</p>
        <?php if ($quote['next_at'] !== null && $quote['next_at'] > new DateTimeImmutable()): ?>
            <p>Следующая попытка доступна с <?= e($quote['next_at']->format('d.m.Y H:i:s')) ?> UTC.</p>
        <?php endif; ?>
        <?php if ($quote['source']['code'] === 'KR' && $quote['target'] !== null && $quote['target']['code'] !== 'KR'): ?>
            <p role="note">После выезда из Кореи вернуться в KR переносом нельзя.</p>
        <?php endif; ?>
        <?php if ($quote['error'] !== null): ?>
            <p role="alert"><?= e($quote['error']) ?></p>
        <?php endif; ?>
        <?php if ($quote['error'] === null): ?>
            <form action="/transfer" method="post">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="account_id" value="<?= e($account['id']) ?>">
                <input type="hidden" name="source_id" value="<?= e($account['current_region_id']) ?>">
                <input type="hidden" name="source_version" value="<?= e($account['transfer_version']) ?>">
                <input type="hidden" name="target_id" value="<?= e($targetId) ?>">
                <input type="hidden" name="operation_key" value="<?= e($operationKey) ?>">
                <button type="submit">Перенести за <?= e($quote['price']) ?> золотых</button>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>
