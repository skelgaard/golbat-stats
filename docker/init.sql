CREATE DATABASE IF NOT EXISTS golbat;
USE golbat;

CREATE TABLE IF NOT EXISTS pokemon_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pokemon_id INT NOT NULL,
    date DATE NOT NULL,
    total INT NOT NULL DEFAULT 0,
    UNIQUE KEY idx_pokemon_date (pokemon_id, date)
);

CREATE TABLE IF NOT EXISTS pokemon_shiny_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pokemon_id INT NOT NULL,
    date DATE NOT NULL,
    total INT NOT NULL DEFAULT 0,
    `count` INT NOT NULL DEFAULT 0,
    UNIQUE KEY idx_pokemon_date (pokemon_id, date)
);

CREATE TABLE IF NOT EXISTS pokemon_hundo_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pokemon_id INT NOT NULL,
    date DATE NOT NULL,
    `count` INT NOT NULL DEFAULT 0,
    fence VARCHAR(50) DEFAULT 'world',
    UNIQUE KEY idx_pokemon_date_fence (pokemon_id, date, fence)
);

CREATE TABLE IF NOT EXISTS pokemon_nundo_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pokemon_id INT NOT NULL,
    date DATE NOT NULL,
    `count` INT NOT NULL DEFAULT 0,
    fence VARCHAR(50) DEFAULT 'world',
    UNIQUE KEY idx_pokemon_date_fence (pokemon_id, date, fence)
);

-- Seed data for today
INSERT INTO pokemon_stats (pokemon_id, date, total) VALUES
(1, CURDATE(), 15234), (4, CURDATE(), 12890), (7, CURDATE(), 11456),
(25, CURDATE(), 28901), (133, CURDATE(), 19876), (129, CURDATE(), 8234),
(147, CURDATE(), 3210), (246, CURDATE(), 2890), (374, CURDATE(), 1567),
(443, CURDATE(), 987), (610, CURDATE(), 1234), (633, CURDATE(), 876),
(704, CURDATE(), 2345), (841, CURDATE(), 4567), (872, CURDATE(), 3456),
(1, CURDATE() - INTERVAL 1 DAY, 14500), (4, CURDATE() - INTERVAL 1 DAY, 11200),
(7, CURDATE() - INTERVAL 1 DAY, 10800), (25, CURDATE() - INTERVAL 1 DAY, 27600),
(133, CURDATE() - INTERVAL 1 DAY, 18900);

INSERT INTO pokemon_shiny_stats (pokemon_id, date, total, `count`) VALUES
(1, CURDATE(), 15234, 32), (4, CURDATE(), 12890, 25), (7, CURDATE(), 11456, 22),
(25, CURDATE(), 28901, 120), (133, CURDATE(), 19876, 45), (129, CURDATE(), 8234, 18),
(147, CURDATE(), 3210, 6), (246, CURDATE(), 2890, 5), (374, CURDATE(), 1567, 3),
(443, CURDATE(), 987, 2), (610, CURDATE(), 1234, 0), (633, CURDATE(), 876, 0),
(704, CURDATE(), 2345, 8), (841, CURDATE(), 4567, 10), (872, CURDATE(), 3456, 7),
(1, CURDATE() - INTERVAL 1 DAY, 14500, 28), (4, CURDATE() - INTERVAL 1 DAY, 11200, 20),
(7, CURDATE() - INTERVAL 1 DAY, 10800, 19), (25, CURDATE() - INTERVAL 1 DAY, 27600, 110),
(133, CURDATE() - INTERVAL 1 DAY, 18900, 40);

INSERT INTO pokemon_hundo_stats (pokemon_id, date, `count`, fence) VALUES
(1, CURDATE(), 5, 'world'), (4, CURDATE(), 3, 'world'), (7, CURDATE(), 4, 'world'),
(25, CURDATE(), 12, 'world'), (133, CURDATE(), 8, 'world'), (129, CURDATE(), 2, 'world'),
(147, CURDATE(), 1, 'world'), (246, CURDATE(), 1, 'world'), (374, CURDATE(), 0, 'world'),
(443, CURDATE(), 0, 'world'), (610, CURDATE(), 1, 'world'), (633, CURDATE(), 0, 'world'),
(704, CURDATE(), 1, 'world'), (841, CURDATE(), 2, 'world'), (872, CURDATE(), 1, 'world'),
(1, CURDATE() - INTERVAL 1 DAY, 4, 'world'), (4, CURDATE() - INTERVAL 1 DAY, 2, 'world'),
(7, CURDATE() - INTERVAL 1 DAY, 3, 'world'), (25, CURDATE() - INTERVAL 1 DAY, 10, 'world'),
(133, CURDATE() - INTERVAL 1 DAY, 7, 'world');

INSERT INTO pokemon_nundo_stats (pokemon_id, date, `count`, fence) VALUES
(1, CURDATE(), 3, 'world'), (4, CURDATE(), 2, 'world'), (7, CURDATE(), 2, 'world'),
(25, CURDATE(), 8, 'world'), (133, CURDATE(), 5, 'world'), (129, CURDATE(), 1, 'world'),
(147, CURDATE(), 0, 'world'), (246, CURDATE(), 1, 'world'), (374, CURDATE(), 0, 'world'),
(443, CURDATE(), 0, 'world'), (610, CURDATE(), 0, 'world'), (633, CURDATE(), 0, 'world'),
(704, CURDATE(), 1, 'world'), (841, CURDATE(), 1, 'world'), (872, CURDATE(), 1, 'world'),
(1, CURDATE() - INTERVAL 1 DAY, 2, 'world'), (4, CURDATE() - INTERVAL 1 DAY, 1, 'world'),
(7, CURDATE() - INTERVAL 1 DAY, 2, 'world'), (25, CURDATE() - INTERVAL 1 DAY, 6, 'world'),
(133, CURDATE() - INTERVAL 1 DAY, 4, 'world');
