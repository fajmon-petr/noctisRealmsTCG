CREATE TABLE player_faction_stats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  profile_id INT NOT NULL,
  faction_id INT NOT NULL,
  season_id INT NULL,

  points_total INT NOT NULL DEFAULT 0,
  common INT NOT NULL DEFAULT 0,
  uncommon INT NOT NULL DEFAULT 0,
  rare INT NOT NULL DEFAULT 0,
  epic INT NOT NULL DEFAULT 0,
  legendary INT NOT NULL DEFAULT 0,

  last_update_at DATETIME NOT NULL
    DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uniq_profile_faction_season (profile_id, faction_id, season_id),
  KEY idx_leaderboard (faction_id, points_total),

  CONSTRAINT fk_pfs_profile FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
  CONSTRAINT fk_pfs_faction FOREIGN KEY (faction_id) REFERENCES factions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
