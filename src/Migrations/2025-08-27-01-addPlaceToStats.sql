ALTER TABLE player_season_stats
  ADD COLUMN final_rank INT NULL AFTER points_total;

-- Pomůže jak live ranku, tak snapshotu
CREATE INDEX idx_pfs_season_points ON player_season_stats (season_id, points_total DESC);