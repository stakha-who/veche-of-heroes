<h1><?= e($pageTitle) ?></h1>
<p class="notice" role="status"><?= e($message) ?></p>
<a href="<?= currentUserId() === null ? '/login' : '/accounts' ?>">Вернуться в кабинет</a>
