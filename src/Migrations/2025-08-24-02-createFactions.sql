-- 1) Tabulka factions
CREATE TABLE factions (
  id INT AUTO_INCREMENT NOT NULL,
  slug VARCHAR(32) NOT NULL,                 -- stabilní identifikátor
  name VARCHAR(64) NOT NULL,
  color CHAR(7) NOT NULL,                    -- hex barva, např. #E53935
  description TEXT DEFAULT NULL,             -- delší popis
  emblem VARCHAR(255) DEFAULT NULL,          -- URL/rel. cesta na malý emblém
  banner VARCHAR(255) DEFAULT NULL,          -- URL/rel. cesta na větší obrázek
  perk VARCHAR(255) DEFAULT NULL,            -- krátký text „pasivky“
  is_selectable TINYINT(1) NOT NULL DEFAULT 1, -- může se volit v UI?
  players_count INT NOT NULL DEFAULT 0,      -- agregace (volitelně)
  faction_points INT NOT NULL DEFAULT 0,     -- body frakce (agregace)
  UNIQUE INDEX UNIQ_factions_slug (slug),
  INDEX IDX_factions_selectable (is_selectable),
  PRIMARY KEY(id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_czech_ci;

-- 2) Vazba do profiles (pokud už tam máš starý sloupec faction jako string, můžeš ho zahodit)
ALTER TABLE profiles
  ADD CONSTRAINT FK_profiles_faction
    FOREIGN KEY (faction_id) REFERENCES factions (id) ON DELETE SET NULL;

-- (volitelně) pokud existuje starý stringový sloupec:
-- ALTER TABLE profiles DROP COLUMN faction;

-- 3) Seed základních frakcí
INSERT INTO factions (slug, name, color, description, emblem, banner, perk, is_selectable)
VALUES
('ignis',  'Ignis',  '#E53935', 'Oheň a síla.',        '/assets/factions/ignis.png',  NULL, 'Bonus k útoku po výhře.', 1),
('vitae',  'Vitae',  '#43A047', 'Léčení a růst.',      '/assets/factions/vitae.png',  NULL, 'Malé léčení po kole.',    1),
('noctis', 'Noctis', '#6A1B9A', 'Temnota a kontrola.', '/assets/factions/noctis.png', NULL, 'Šance zablokovat efekt.', 1);
