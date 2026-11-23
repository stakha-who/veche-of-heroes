CREATE TABLE matches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT UNSIGNED NOT NULL,
    ranking_id BIGINT UNSIGNED NOT NULL,
    match_server_id BIGINT UNSIGNED NOT NULL,
    started_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    outcome ENUM('win', 'loss') NULL,
    mmr_after INT NULL,
    INDEX matches_active (account_id, ranking_id, completed_at),
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (ranking_id) REFERENCES rankings(id),
    FOREIGN KEY (match_server_id) REFERENCES match_servers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
