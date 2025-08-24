CREATE TABLE profiles (
  id INT AUTO_INCREMENT NOT NULL,
  user_id INT NOT NULL,
  nickname VARCHAR(64) DEFAULT NULL,
  faction VARCHAR(20) DEFAULT NULL,
  cards INT NOT NULL DEFAULT 0,
  opened_packs INT NOT NULL DEFAULT 0,
  achievements INT NOT NULL DEFAULT 0,
  INDEX IDX_profiles_user (user_id),
  UNIQUE INDEX UNIQ_profiles_user (user_id),
  PRIMARY KEY(id),
  CONSTRAINT FK_profiles_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;
