CREATE TABLE account_items (
    account_id BIGINT UNSIGNED NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (account_id, item_name),
    FOREIGN KEY (account_id) REFERENCES accounts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
