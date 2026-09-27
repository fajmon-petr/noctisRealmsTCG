RENAME TABLE
  cards TO card,
  factions TO faction,
  faction_season_stats TO faction_season_stat,
  player_season_stats TO player_season_stat,
  profiles TO player,
  roles TO role,
  seasons TO season,
  users TO user;
  
ALTER TABLE player_season_stat
CHANGE profile_id player_id INT NOT NULL;

ALTER TABLE player_season_stat
  DROP INDEX uniq_profile_faction_season,
  ADD UNIQUE KEY uniq_player_faction_season (player_id, faction_id, season_id);
