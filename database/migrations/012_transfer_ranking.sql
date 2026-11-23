ALTER TABLE transfers
    ADD COLUMN season_id BIGINT UNSIGNED NULL,
    ADD COLUMN source_rank VARCHAR(50) NULL,
    ADD COLUMN target_ranking_id BIGINT UNSIGNED NULL,
    ADD FOREIGN KEY (season_id) REFERENCES seasons(id),
    ADD FOREIGN KEY (target_ranking_id) REFERENCES rankings(id);
