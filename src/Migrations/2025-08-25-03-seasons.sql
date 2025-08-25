CREATE TABLE seasons (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  number    INT NOT NULL UNIQUE,              -- kolikátá sezóna (1,2,3…)
  name      VARCHAR(64) NOT NULL,             -- název sezóny
  start_at  DATETIME NOT NULL,
  end_at    DATETIME NULL,                    -- NULL = stále běží
  KEY idx_season_dates (start_at, end_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- (volitelné) navázat existující stats na sezóny:
ALTER TABLE player_faction_stats
  ADD CONSTRAINT fk_pfs_season
  FOREIGN KEY (season_id) REFERENCES seasons(id)
  ON UPDATE CASCADE ON DELETE SET NULL;


INSERT INTO seasons (number, name, start_at, end_at)
VALUES
 (1, 'Alpha',  '2025-01-01 00:00:00', '2025-03-31 23:59:59'),
 (2, 'Beta',   '2025-04-01 00:00:00', NULL);
 