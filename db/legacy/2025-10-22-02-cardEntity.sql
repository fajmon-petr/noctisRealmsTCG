CREATE TABLE cards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  faction_id INT NULL,
  name VARCHAR(120) NOT NULL,
  rarity CHAR(1) NOT NULL,          
  image_path VARCHAR(255) NOT NULL,  

  CONSTRAINT fk_card_faction FOREIGN KEY (faction_id) REFERENCES factions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
