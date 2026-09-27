-- Definice odznaků/úspěchů
CREATE TABLE achievement (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  code         VARCHAR(100) NOT NULL,          -- stálý identifikátor (např. "OPEN_10_PACKS")
  name         VARCHAR(150) NOT NULL,
  description  TEXT NULL,
  points       INT NOT NULL DEFAULT 0,         -- volitelné: bodová hodnota odměny
  icon         VARCHAR(255) NULL,              -- volitelné: cesta/klíč ikony
  type         VARCHAR(255) NULL,              
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_achievement_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Úspěchy hráčů
CREATE TABLE player_achievement (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  player_id      INT NOT NULL,
  achievement_id INT NOT NULL,
  achieved_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_pa_player      FOREIGN KEY (player_id)      REFERENCES player (id)       ON DELETE CASCADE,
  CONSTRAINT fk_pa_achievement FOREIGN KEY (achievement_id) REFERENCES achievement (id) ON DELETE CASCADE,

  UNIQUE KEY uq_player_achievement (player_id, achievement_id),
  KEY idx_pa_player (player_id),
  KEY idx_pa_achievement (achievement_id),
  KEY idx_pa_achieved_at (achieved_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Úspěchy frakcí
CREATE TABLE faction_achievement (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  faction_id     INT NOT NULL,
  achievement_id INT NOT NULL,
  achieved_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_fa_faction      FOREIGN KEY (faction_id)    REFERENCES faction (id)      ON DELETE CASCADE,
  CONSTRAINT fk_fa_achievement  FOREIGN KEY (achievement_id)REFERENCES achievement (id) ON DELETE CASCADE,

  UNIQUE KEY uq_faction_achievement (faction_id, achievement_id),
  KEY idx_fa_faction (faction_id),
  KEY idx_fa_achievement (achievement_id),
  KEY idx_fa_achieved_at (achieved_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO achievement (code, name, description, points)
VALUES
  ('OPEN_1_PACK',   'První balíček',     'Otevři první balíček', 10),
  ('OPEN_10_PACKS', 'Balíčkář',          'Otevři 10 balíčků',    50),
  ('WIN_1_MATCH',   'První výhra',       'Vyhraj zápas',         20),
  ('WIN_10_MATCH',  'Jedeš bomby',       'Vyhraj 10 zápasů',     80),
  ('DONATE_DUST',   'Podpora frakce',    'Přispěj Dustem',       15);
