<h1>Вход в кабинет</h1>
<?php if (isset($message)): ?>
    <p class="notice notice--error" role="alert"><?= e($message) ?></p>
<?php endif; ?>
<form action="/login" method="post">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <label>Имя пользователя
        <input name="username" required maxlength="64" autocomplete="username" value="<?= e($username ?? '') ?>">
    </label>
    <label>Пароль
        <input name="password" type="password" required autocomplete="current-password">
    </label>
    <button type="submit">Войти</button>
</form>
