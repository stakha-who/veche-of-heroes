UPDATE rankings r
JOIN accounts a ON a.id = r.account_id AND a.current_region_id = r.region_id
JOIN seasons s ON s.id = r.season_id
JOIN (
    SELECT ranking_id, outcome
    FROM (
        SELECT ranking_id, outcome,
            ROW_NUMBER() OVER (PARTITION BY ranking_id ORDER BY completed_at, id) AS match_number
        FROM matches
        WHERE completed_at IS NOT NULL AND outcome IN ('win', 'loss')
    ) ordered_matches
    WHERE match_number = 3
) third_match ON third_match.ranking_id = r.id
SET r.confirmed_rank = CASE
    WHEN r.provisional_rank = 'Алмаз IV' AND third_match.outcome = 'loss' THEN 'Изумруд I'
    ELSE COALESCE(r.provisional_rank, 'Серебро I')
END,
r.placement_games = 3
WHERE r.confirmed_rank IS NULL
    AND r.closed_at IS NULL
    AND r.placement_games >= 3
    AND s.starts_at <= UTC_TIMESTAMP()
    AND s.ends_at > UTC_TIMESTAMP();
