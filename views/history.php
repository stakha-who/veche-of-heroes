<?php $demo = getenv('APP_DEMO') === '1'; ?>
<header class="dark-panel page-banner">
    <a class="back-link" href="/account?id=<?= e($account['id']) ?>"><span aria-hidden="true">←</span> <?= e($account['player_name']) ?></a>
    <h1>История переносов</h1>
</header>
<section class="paper-panel ledger">
    <?php if (!$history): ?>
        <p>У этого аккаунта ещё не было переносов.</p>
    <?php else: ?>
        <table class="ledger-table<?= $demo ? ' ledger-table--demo' : '' ?>" aria-label="Выполненные переносы">
            <thead>
                <tr>
                    <th scope="col">Когда (UTC)</th><th scope="col">Маршрут</th><th scope="col">Списано</th>
                    <?php if ($demo): ?>
                        <th scope="col">MMR (демо)</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $transfer): ?>
                    <tr>
                        <td><?= e($transfer['transferred_at']) ?></td>
                        <td class="ledger-route"><?= e($transfer['source_code']) ?> → <?= e($transfer['target_code']) ?></td>
                        <td><?= e($transfer['cost']) ?> золотых</td>
                        <?php if ($demo): ?>
                            <td><?= $transfer['mmr_before'] === null || $transfer['mmr_after'] === null
                                ? '—' : e($transfer['mmr_before'] . ' → ' . $transfer['mmr_after']) ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
