CREATE TABLE IF NOT EXISTS item_catalog (
    name VARCHAR(100) NOT NULL PRIMARY KEY
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO item_catalog (name) VALUES
    ('Обережный пояс'),
    ('Ратный клинок'),
    ('Сосновое копьё'),
    ('Берестяной оберег'),
    ('Серебряная гривна'),
    ('Липовый щит'),
    ('Медные наручи'),
    ('Костяной гребень'),
    ('Кованая пряжка'),
    ('Рябиновый посох'),
    ('Вышитый плащ'),
    ('Путевой фонарь')
ON DUPLICATE KEY UPDATE name = item_catalog.name;
