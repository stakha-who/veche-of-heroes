ALTER TABLE accounts
    ADD COLUMN last_transfer_at DATETIME NULL,
    ADD COLUMN transfer_version INT UNSIGNED NOT NULL DEFAULT 0;
