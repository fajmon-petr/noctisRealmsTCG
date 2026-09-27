CREATE TABLE faction_season_stats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  faction_id INT NOT NULL,
  season_id INT NOT NULL,

  points_total INT NOT NULL DEFAULT 0,
  final_rank INT NULL,

  common INT NOT NULL DEFAULT 0,
  uncommon INT NOT NULL DEFAULT 0,
  rare INT NOT NULL DEFAULT 0,
  epic INT NOT NULL DEFAULT 0,
  legendary INT NOT NULL DEFAULT 0,

  last_update_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY idx_faction_season (faction_id, season_id),
  CONSTRAINT fk_fss_faction FOREIGN KEY (faction_id) REFERENCES factions(id) ON DELETE CASCADE,
  CONSTRAINT fk_fss_season  FOREIGN KEY (season_id)  REFERENCES seasons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
