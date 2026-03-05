-- ============================================================
-- RamenCraft Database Schema + Seed Data
-- ============================================================
-- This file runs automatically when the MySQL container starts
-- for the first time. It creates all tables and inserts seed data.
-- ============================================================

-- Use the ramencraft database (created by Docker env var)
USE ramencraft;

-- ============================================================
-- TABLE DEFINITIONS
-- ============================================================

-- Users: player and admin accounts
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('player', 'admin') DEFAULT 'player',
    total_xp INT DEFAULT 0,
    current_rank ENUM('minarai', 'jouren', 'tsuu', 'shokunin', 'taisho') DEFAULT 'minarai',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories: the 5 ingredient categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    sort_order INT DEFAULT 0
);

-- Ingredients: all available ramen ingredients
CREATE TABLE ingredients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    name_jp VARCHAR(100),
    description TEXT,
    sprite_icon VARCHAR(255),
    sprite_bowl VARCHAR(255),
    calories_per_serving DECIMAL(8,2),
    protein_g DECIMAL(8,2),
    fat_g DECIMAL(8,2),
    carbs_g DECIMAL(8,2),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Pairings: ingredient combo scoring rules
CREATE TABLE pairings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingredient_1_id INT NOT NULL,
    ingredient_2_id INT NOT NULL,
    score_modifier INT NOT NULL,
    combo_name VARCHAR(100),
    description VARCHAR(255),
    FOREIGN KEY (ingredient_1_id) REFERENCES ingredients(id),
    FOREIGN KEY (ingredient_2_id) REFERENCES ingredients(id),
    UNIQUE KEY unique_pair (ingredient_1_id, ingredient_2_id)
);

-- Served bowls: game history (each time a player serves a bowl)
CREATE TABLE served_bowls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tastiness_score INT DEFAULT 0,
    nutrition_score INT DEFAULT 0,
    total_score INT DEFAULT 0,
    xp_earned INT DEFAULT 0,
    served_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Bowl ingredients: which ingredients were in each served bowl
CREATE TABLE bowl_ingredients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bowl_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    FOREIGN KEY (bowl_id) REFERENCES served_bowls(id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id)
);

-- Achievements: all possible achievements
CREATE TABLE achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(255),
    requirement_type VARCHAR(50),
    requirement_value INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User achievements: join table tracking which player unlocked which achievement
CREATE TABLE user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (achievement_id) REFERENCES achievements(id),
    UNIQUE KEY unique_user_achievement (user_id, achievement_id)
);

-- Favorites: saved bowl configurations
CREATE TABLE favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Favorite ingredients: which ingredients are in each saved bowl
CREATE TABLE favorite_ingredients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    favorite_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    FOREIGN KEY (favorite_id) REFERENCES favorites(id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id)
);

-- Nutrition cache: cached Edamam API responses to avoid hitting rate limits
CREATE TABLE nutrition_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingredient_hash VARCHAR(64) UNIQUE NOT NULL,
    response_json JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- SEED DATA
-- ============================================================

-- Categories (5 ingredient categories)
INSERT INTO categories (name, display_name, sort_order) VALUES
('broth',   'Broth',      1),
('noodles', 'Noodles',    2),
('oil',     'Flavor Oil', 3),
('protein', 'Protein',    4),
('topping', 'Toppings',   5);

-- Ingredients: Broths (category_id = 1)
INSERT INTO ingredients (category_id, name, name_jp, description, sprite_icon, sprite_bowl, calories_per_serving, protein_g, fat_g, carbs_g) VALUES
(1, 'Tonkotsu',    '豚骨',   'Rich, creamy pork bone broth',        '/sprites/broth-tonkotsu-icon.png',    '/sprites/broth-tonkotsu-bowl.png',    150, 8,  10, 3),
(1, 'Shoyu',       '醤油',   'Soy sauce based, clear brown',        '/sprites/broth-shoyu-icon.png',       '/sprites/broth-shoyu-bowl.png',       80,  6,  3,  5),
(1, 'Miso',        '味噌',   'Fermented soybean, opaque orange-brown', '/sprites/broth-miso-icon.png',     '/sprites/broth-miso-bowl.png',        120, 7,  5,  10),
(1, 'Shio',        '塩',     'Salt-based, light golden',            '/sprites/broth-shio-icon.png',        '/sprites/broth-shio-bowl.png',        60,  5,  2,  3),
(1, 'Tantan',      '担々',   'Sesame-based, spicy, red-orange',     '/sprites/broth-tantan-icon.png',      '/sprites/broth-tantan-bowl.png',      180, 7,  12, 8),
(1, 'Ebi',         '海老',   'Shrimp-based miso, pink-tinted',      '/sprites/broth-ebi-icon.png',         '/sprites/broth-ebi-bowl.png',         90,  10, 3,  4),
(1, 'Tori Paitan', '鶏白湯', 'Chicken, creamy yellow',              '/sprites/broth-tori-paitan-icon.png', '/sprites/broth-tori-paitan-bowl.png', 130, 9,  8,  2),
(1, 'Veggie',      '野菜',   'Plant-based, creamy with greenish tint', '/sprites/broth-veggie-icon.png',   '/sprites/broth-veggie-bowl.png',      70,  3,  4,  6);

-- Ingredients: Noodles (category_id = 2)
INSERT INTO ingredients (category_id, name, name_jp, description, sprite_icon, sprite_bowl, calories_per_serving, protein_g, fat_g, carbs_g) VALUES
(2, 'Thin Straight', '細麺',   'Hosomen — typical for tonkotsu',     '/sprites/noodle-thin-icon.png',  '/sprites/noodle-thin-bowl.png',  210, 7, 1, 42),
(2, 'Thick Straight', '太麺',  'Thick and chewy straight noodles',   '/sprites/noodle-thick-icon.png', '/sprites/noodle-thick-bowl.png', 240, 8, 2, 46),
(2, 'Thick Wavy',    '縮れ麺', 'Typical for miso ramen',            '/sprites/noodle-wavy-icon.png',  '/sprites/noodle-wavy-bowl.png',  230, 8, 2, 44);

-- Ingredients: Flavor Oils (category_id = 3)
INSERT INTO ingredients (category_id, name, name_jp, description, sprite_icon, sprite_bowl, calories_per_serving, protein_g, fat_g, carbs_g) VALUES
(3, 'Chili Oil',       '辣油',       'Layu — red, spicy chili oil',       '/sprites/oil-chili-icon.png',   '/sprites/oil-chili-bowl.png',   45, 0, 5, 0),
(3, 'Burnt Garlic Oil', 'マー油',    'Mayu — black, smoky garlic oil',    '/sprites/oil-mayu-icon.png',    '/sprites/oil-mayu-bowl.png',    40, 0, 4.5, 0),
(3, 'Garlic Oil',      'にんにく油', 'White, garlic-flavored oil',        '/sprites/oil-garlic-icon.png',  '/sprites/oil-garlic-bowl.png',  40, 0, 4.5, 0),
(3, 'Chicken Oil',     '鸡油',       'Chi yu — rich chicken fat',         '/sprites/oil-chicken-icon.png', '/sprites/oil-chicken-bowl.png', 50, 0, 5.5, 0),
(3, 'Back Fat',        '背脂',       'Sei abura — pork back fat',         '/sprites/oil-backfat-icon.png', '/sprites/oil-backfat-bowl.png', 60, 0, 7, 0);

-- Ingredients: Proteins (category_id = 4)
INSERT INTO ingredients (category_id, name, name_jp, description, sprite_icon, sprite_bowl, calories_per_serving, protein_g, fat_g, carbs_g) VALUES
(4, 'Pork Chashu',         'チャーシュー',       'Rolled pork belly slice',            '/sprites/protein-chashu-icon.png',      NULL, 200, 14, 16, 1),
(4, 'Chicken Chashu',      '鶏チャーシュー',     'Lighter colored chicken slice',      '/sprites/protein-chicken-icon.png',     NULL, 150, 18, 8, 0),
(4, 'Ajitama',             '味玉',               'Marinated soft-boiled egg, halved',  '/sprites/protein-ajitama-icon.png',     NULL, 70, 6, 5, 1),
(4, 'Seitan Katsu',        'セイタンカツ',       'Breaded, golden — plant-based',      '/sprites/protein-seitan-icon.png',      NULL, 180, 20, 8, 10),
(4, 'Karaage',             '唐揚げ',             'Japanese fried chicken pieces',      '/sprites/protein-karaage-icon.png',     NULL, 220, 16, 14, 8),
(4, 'Cauliflower Tempura', 'カリフラワー天ぷら', 'Plant-based tempura option',         '/sprites/protein-cauliflower-icon.png', NULL, 120, 4, 7, 12);

-- Ingredients: Toppings (category_id = 5)
INSERT INTO ingredients (category_id, name, name_jp, description, sprite_icon, sprite_bowl, calories_per_serving, protein_g, fat_g, carbs_g) VALUES
(5, 'Corn',        'コーン',       'Sweet corn kernels',             '/sprites/topping-corn-icon.png',    NULL, 30, 1, 0.5, 6),
(5, 'Bean Sprouts', 'もやし',      'Moyashi — fresh bean sprouts',   '/sprites/topping-sprouts-icon.png', NULL, 10, 1, 0, 2),
(5, 'Spinach',     'ほうれん草',   'Hōrenso — blanched spinach',     '/sprites/topping-spinach-icon.png', NULL, 15, 2, 0, 1),
(5, 'Nori',        '海苔',         'Dried seaweed sheet',            '/sprites/topping-nori-icon.png',    NULL, 5, 1, 0, 1),
(5, 'Menma',       'メンマ',       'Fermented bamboo shoots',        '/sprites/topping-menma-icon.png',   NULL, 15, 1, 0, 3),
(5, 'Negi',        'ねぎ',         'Sliced green onion',             '/sprites/topping-negi-icon.png',    NULL, 5, 0, 0, 1),
(5, 'Narutomaki',  'なると巻き',   'Fish cake with pink swirl',      '/sprites/topping-naruto-icon.png',  NULL, 35, 3, 1, 4);

-- Pairings: ingredient combo scoring rules
-- Great combos (positive modifiers)
INSERT INTO pairings (ingredient_1_id, ingredient_2_id, score_modifier, combo_name, description) VALUES
-- Classic Tonkotsu: Tonkotsu(1) + Pork Chashu(17) + Mayu(13) = +15
(1, 17, 10, 'Classic Tonkotsu',       'Tonkotsu + Pork Chashu — the foundation'),
(1, 13,  5, 'Classic Tonkotsu',       'Tonkotsu + Mayu — smoky perfection'),
-- Sapporo Special: Miso(3) + Thick Wavy(11) + Corn(23) = +10
(3, 11,  5, 'Sapporo Special',        'Miso + Thick Wavy noodles — authentic'),
(3, 23,  5, 'Sapporo Special',        'Miso + Corn — Sapporo classic'),
-- Old School Tokyo: Shoyu(2) + Thin Straight(9) + Nori(26) + Ajitama(19) = +12
(2, 9,   4, 'Old School Tokyo',       'Shoyu + Thin noodles — Tokyo style'),
(2, 26,  4, 'Old School Tokyo',       'Shoyu + Nori — classic pairing'),
(2, 19,  4, 'Old School Tokyo',       'Shoyu + Ajitama — OG combo'),
-- Spice Demon: Tantan(5) + Chicken Chashu(18) + Chili Oil(12) = +10
(5, 18,  5, 'Spice Demon',            'Tantan + Chicken — spicy comfort'),
(5, 12,  5, 'Spice Demon',            'Tantan + Chili Oil — fire on fire'),
-- Triple Chicken Threat: Tori Paitan(7) + Chicken Chashu(18) + Chicken Oil(15) = +10
(7, 18,  5, 'Triple Chicken Threat',  'Tori Paitan + Chicken Chashu — cluck cluck'),
(7, 15,  5, 'Triple Chicken Threat',  'Tori Paitan + Chicken Oil — triple chicken'),
-- Ocean's Three: Ebi(6) + Nori(26) + Narutomaki(29) = +8
(6, 26,  4, 'Ocean''s Three',         'Ebi + Nori — taste the sea'),
(6, 29,  4, 'Ocean''s Three',         'Ebi + Narutomaki — ocean friends'),
-- Green Machine: Veggie(8) + Seitan(20) + Spinach(25) = +10
(8, 20,  5, 'Green Machine',          'Veggie + Seitan — plant power'),
(8, 25,  5, 'Green Machine',          'Veggie + Spinach — green goodness'),
-- Egg is Life: any broth + ajitama = +5 (using most common broths)
(3, 19,  5, 'Egg is Life',            'Miso + Ajitama — egg makes everything better'),
(4, 19,  5, 'Egg is Life',            'Shio + Ajitama — simple perfection'),
-- Zen Garden: Shio(4) + Nori(26) + Menma(27) + Garlic Oil(14) = +5
(4, 26,  2, 'Zen Garden',             'Shio + Nori — peaceful flavor'),
(4, 27,  2, 'Zen Garden',             'Shio + Menma — earthy notes'),
(4, 14,  1, 'Zen Garden',             'Shio + Garlic Oil — subtle enhancement');

-- Cursed combos (negative modifiers)
INSERT INTO pairings (ingredient_1_id, ingredient_2_id, score_modifier, combo_name, description) VALUES
-- Surf & Lard: Ebi(6) + Back Fat(16) = -10
(6, 16, -10, 'Surf & Lard',           'Shrimp + Back fat — an abomination'),
-- The Hypocrite: Veggie(8) + Pork Chashu(17) = -8
(8, 17, -8,  'The Hypocrite',          'Veggie broth + Pork — pick a lane'),
-- Why Though: Tonkotsu(1) + Cauliflower Tempura(22) = -5
(1, 22, -5,  'Why Though',             'Tonkotsu + Cauliflower — confused bowl'),
-- Triple Oil Crisis: Shio(4) + Chili Oil(12) + Mayu(13) = -12
(12, 13, -6, 'Triple Oil Crisis',      'Chili + Mayu — too much oil'),
(12, 15, -6, 'Triple Oil Crisis',      'Chili + Chicken Oil — oil overload');

-- Achievements seed data
INSERT INTO achievements (name, description, requirement_type, requirement_value) VALUES
('First Serve',         'Everyone starts somewhere. Serve your first bowl.',                     'bowls_served', 1),
('Centurion',           'You''ve served 100 bowls. Touch grass?',                                'bowls_served', 100),
('Broth Explorer',      'Tried all 8 broths. A true scholar of soup.',                           'unique_broths', 8),
('Noodle Master',       'All 3 noodle types, conquered.',                                        'unique_noodles', 3),
('Oil Connoisseur',     'Every oil has graced your bowl. Slippery slope.',                       'unique_oils', 5),
('Perfect Bowl',        'Scored 180+. The ramen gods smile upon you.',                           'score_threshold', 180),
('Plant Power',         'A fully plant-based bowl. Mother Earth approves.',                      'specific_combo', NULL),
('Classic Tonkotsu',    'Tonkotsu + Chashu + Mayu. As the ancestors intended.',                  'specific_combo', NULL),
('Old School Tokyo',    'Shoyu + thin noodles + nori + ajitama. Respect the OGs.',               'specific_combo', NULL),
('Spice Demon',         'Tantan + chili oil. Your mouth is a warzone.',                          'specific_combo', NULL),
('Egg is Life',         'Used ajitama in 10 bowls. We get it, you like eggs.',                   'ingredient_count', 10),
('Triple Chicken Threat', 'Chicken broth + chicken chashu + chicken oil. Bawk bawk.',            'specific_combo', NULL),
('Oil Spill',           'You put ALL 5 oils in one bowl. Seek help.',                            'specific_combo', NULL),
('The Hypocrite',       'Veggie broth with pork chashu. Make up your mind.',                     'specific_combo', NULL),
('Surf & Lard',         'Shrimp broth + back fat. Why are you like this.',                       'specific_combo', NULL),
('Kitchen Sink',        'Every single topping in one bowl. Restraint is a virtue you don''t have.', 'specific_combo', NULL),
('Sad Soup',            'Broth with toppings but no noodles and no protein. Are you okay?',      'specific_combo', NULL),
('Masochist',           'Served 5 bowls that scored below 30. You enjoy suffering.',              'low_score_count', 5),
('Creature of Habit',   'Served the exact same bowl 10 times. Adventurous.',                     'identical_bowls', 10),
('Naked Noodles',       'Just broth and noodles. Nothing else. Minimalist king.',                 'specific_combo', NULL),
('The Arsonist',        'Tantan + layu + mayu. Some people just want to watch the world burn.',   'specific_combo', NULL);

-- Seed users: one admin and one test player
-- Password for both: "password123" (bcrypt hash)
INSERT INTO users (username, email, password_hash, role, total_xp, current_rank) VALUES
('admin',           'admin@ramencraft.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',  0,    'minarai'),
('RamenApprentice', 'player@ramencraft.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'player', 820,  'jouren');
