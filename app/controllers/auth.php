<?php
declare(strict_types=1);

function loginPage(): void
{
    if (currentUserId() !== null) {
        redirect('/accounts');
    }
    render('login', ['pageTitle' => 'Вход']);
}

function loginSubmit(): void
{
    if (!validCsrf()) {
        render('message', ['pageTitle' => 'Форма устарела', 'message' => 'Открой форму входа заново.'], 403);
        return;
    }
    $username = trim(postValue('username'));
    $password = postValue('password');
    $query = db()->prepare('SELECT id, password_hash FROM users WHERE username = ?');
    $query->execute([$username]);
    $user = $query->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        render('login', ['pageTitle' => 'Вход', 'message' => 'Неверное имя пользователя или пароль.', 'username' => $username], 422);
        return;
    }
    session_regenerate_id(true);
    $_SESSION = ['user_id' => (int) $user['id']];
    redirect('/accounts');
}

function logoutSubmit(): void
{
    if (!validCsrf()) {
        render('message', ['pageTitle' => 'Форма устарела', 'message' => 'Обнови страницу и повтори выход.'], 403);
        return;
    }
    $_SESSION = [];
    session_destroy();
    redirect('/login');
}
