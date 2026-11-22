CREATE TABLE transfers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT UNSIGNED NOT NULL,
    source_region_id BIGINT UNSIGNED NOT NULL,
    target_region_id BIGINT UNSIGNED NOT NULL,
    source_version INT UNSIGNED NOT NULL,
    operation_key CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    cost INT UNSIGNED NOT NULL,
    gold_after INT UNSIGNED NOT NULL,
    transferred_at DATETIME NOT NULL,
    UNIQUE (account_id, operation_key),
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (source_region_id) REFERENCES regions(id),
    FOREIGN KEY (target_region_id) REFERENCES regions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
