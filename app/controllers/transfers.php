<?php
declare(strict_types=1);

function transferPage(): void
{
    $account = ownedAccountFromRequest();
    if ($account === null) {
        return;
    }
    $targetId = readId($_GET['target'] ?? null);
    $regions = db()->query('SELECT id, code, name FROM regions ORDER BY id')->fetchAll();
    $quote = $targetId > 0 ? transferQuote(db(), $account, $targetId, new DateTimeImmutable()) : null;

    render('transfer', [
        'pageTitle' => 'Перенос аккаунта',
        'account' => $account,
        'regions' => $regions,
        'targetId' => $targetId,
        'quote' => $quote,
    ]);
}
