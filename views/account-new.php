<h1>Создать аккаунт</h1>
<?php if ($message !== ''): ?>
    <p class="notice notice--error" role="alert"><?= e($message) ?></p>
<?php endif; ?>
<form action="/account/new" method="post">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <label>Имя игрока
        <input name="player_name" required minlength="2" maxlength="30" value="<?= e($playerName) ?>">
    </label>
    <label>Пол персонажа
        <select name="gender">
            <option value="">Не указан</option>
            <option value="female"<?= $selectedGender === 'female' ? ' selected' : '' ?>>Женский</option>
            <option value="male"<?= $selectedGender === 'male' ? ' selected' : '' ?>>Мужской</option>
        </select>
    </label>
    <label>Начальный регион
        <select name="region_id" required>
            <option value="">Выбери регион</option>
            <?php foreach ($regions as $region): ?>
                <option value="<?= e($region['id']) ?>" <?= (int) $region['id'] === $selectedRegion ? 'selected' : '' ?>>
                    <?= e($region['name']) ?> (<?= e($region['code']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit">Создать аккаунт</button>
</form>
