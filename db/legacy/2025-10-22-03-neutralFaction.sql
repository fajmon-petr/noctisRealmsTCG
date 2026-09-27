INSERT INTO factions (slug, name, color, description, emblem, banner, perk, is_selectable, players_count, faction_points)
VALUES (
  'neutral',
  'Neutral',
  '#999999',
  'Neutrální síla – spojení všech elementů.',
  '/assets/factions/neutral.png',
  NULL,
  'Žádný bonus, čistá rovnováha.',
  0,
  0,
  0
);

INSERT INTO cards (faction_id, name, rarity, image_path) VALUES
((SELECT id FROM factions WHERE slug = 'ignis'),  'Fire Adept',       'C', '/assets/cards/ignis-common-1.png'),
((SELECT id FROM factions WHERE slug = 'vitae'),  'Life Healer',      'U', '/assets/cards/vitae-uncommon-1.png'),
((SELECT id FROM factions WHERE slug = 'noctis'), 'Shadow Assassin',  'R', '/assets/cards/noctis-rare-1.png'),
((SELECT id FROM factions WHERE slug = 'neutral'),'Balanced Spirit',  'C', '/assets/cards/neutral-common-1.png');
