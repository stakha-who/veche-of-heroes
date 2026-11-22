<h1>История переносов</h1>
<p><a href="/account?id=<?= e($account['id']) ?>"><?= e($account['player_name']) ?></a></p>
<?php if (!$history): ?>
    <p>У этого аккаунта ещё не было переносов.</p>
<?php else: ?>
    <table>
        <thead><tr><th>Когда (UTC)</th><th>Маршрут</th><th>Списано</th></tr></thead>
        <tbody>
        <?php foreach ($history as $transfer): ?>
            <tr>
                <td><?= e($transfer['transferred_at']) ?></td>
                <td><?= e($transfer['source_code']) ?> → <?= e($transfer['target_code']) ?></td>
                <td><?= e($transfer['cost']) ?> золотых</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
