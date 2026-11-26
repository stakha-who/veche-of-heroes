UPDATE rankings
SET rank_points = 49
WHERE closed_at IS NULL AND rank_points >= 50;
