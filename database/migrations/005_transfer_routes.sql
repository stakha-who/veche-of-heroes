CREATE TABLE transfer_routes (
    source_region_id BIGINT UNSIGNED NOT NULL,
    target_region_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (source_region_id, target_region_id),
    FOREIGN KEY (source_region_id) REFERENCES regions(id),
    FOREIGN KEY (target_region_id) REFERENCES regions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
