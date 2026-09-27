-- 1) Tabulka rolí
CREATE TABLE roles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(32) NOT NULL UNIQUE,   -- 'player', 'admin', ...
  name VARCHAR(64) NOT NULL           -- česky pro lidi, např. 'Hráč', 'Administrátor'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO roles (slug, name) VALUES
  ('player', 'Hráč'),
  ('admin',  'Administrátor');

-- 2) Přidat sloupec k uživatelům (zatím NULL)
ALTER TABLE users ADD role_id INT UNSIGNED NULL AFTER password;

-- 3) Naplnit všem default 'player'
UPDATE users
SET role_id = (SELECT id FROM roles WHERE slug = 'player')
WHERE role_id IS NULL;

-- 4) Zaháknout FK a zakázat NULL
ALTER TABLE users
  MODIFY role_id INT UNSIGNED NOT NULL,
  ADD CONSTRAINT fk_users_role
    FOREIGN KEY (role_id) REFERENCES roles(id)
    ON UPDATE CASCADE ON DELETE RESTRICT;
