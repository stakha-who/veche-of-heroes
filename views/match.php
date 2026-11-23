<h1>Матч №<?= e($match['id']) ?></h1>
<p><?= e($account['player_name']) ?> · <?= e($match['server_name']) ?> (<?= e($match['region_code']) ?>)</p>
<?php if ($match['outcome'] !== null): ?>
    <p role="status">Результат принят — <?= $match['outcome'] === 'win' ? 'победа' : 'поражение' ?>.</p>
<?php elseif (getenv('APP_DEMO') === '1'): ?>
    <form action="/match/demo" method="post">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="account_id" value="<?= e($account['id']) ?>">
        <input type="hidden" name="match_id" value="<?= e($match['id']) ?>">
        <label>Демонстрационный результат
            <select name="outcome">
                <option value="win">Победа</option>
                <option value="loss">Поражение</option>
            </select>
        </label>
        <button type="submit">Завершить матч</button>
    </form>
<?php endif; ?>
<a href="/account?id=<?= e($account['id']) ?>">Вернуться к аккаунту</a>
