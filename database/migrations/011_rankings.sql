CREATE TABLE rankings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT UNSIGNED NOT NULL,
    season_id BIGINT UNSIGNED NOT NULL,
    region_id BIGINT UNSIGNED NOT NULL,
    provisional_rank VARCHAR(50) NULL,
    confirmed_rank VARCHAR(50) NULL,
    rank_points INT UNSIGNED NOT NULL DEFAULT 0,
    placement_games TINYINT UNSIGNED NOT NULL DEFAULT 0,
    closed_at DATETIME NULL,
    INDEX rankings_account_state (account_id, season_id, region_id, closed_at),
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (season_id) REFERENCES seasons(id),
    FOREIGN KEY (region_id) REFERENCES regions(id),
    CHECK (placement_games <= 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
