-- ===============================================
-- PC Configurator - Ініціалізація БД
-- Дата створення: 2025-12-02
-- ===============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Видалення існуючих таблиць (якщо є)
DROP TABLE IF EXISTS `build_items`;
DROP TABLE IF EXISTS `saved_builds`;
DROP TABLE IF EXISTS `components`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

-- ===============================================
-- Таблиця: users (Користувачі)
-- ===============================================
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `login` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL COMMENT 'Хеш пароля (password_hash)',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Користувачі системи';

-- ===============================================
-- Таблиця: categories (Категорії компонентів)
-- ===============================================
CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(50) NOT NULL UNIQUE COMMENT 'cpu, gpu, motherboard, ram, psu, case, storage',
  `name` VARCHAR(100) NOT NULL COMMENT 'Назва категорії українською',
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Категорії компонентів ПК';

-- ===============================================
-- Таблиця: components (Компоненти/Товари)
-- ===============================================
CREATE TABLE `components` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL COMMENT 'Назва товару',
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Ціна в грн',
  `image` VARCHAR(255) DEFAULT NULL COMMENT 'Шлях до зображення',
  
  -- Специфікації для сумісності
  `socket` VARCHAR(50) DEFAULT NULL COMMENT 'Сокет (для CPU та Motherboard)',
  `ram_type` VARCHAR(20) DEFAULT NULL COMMENT 'Тип пам\'яті: DDR4, DDR5 (для RAM та Motherboard)',
  `tdp` INT UNSIGNED DEFAULT NULL COMMENT 'Споживання енергії в Ватах (для CPU, GPU)',
  `psu_wattage` INT UNSIGNED DEFAULT NULL COMMENT 'Потужність БЖ у Ватах (тільки для PSU)',
  
  -- Додаткові характеристики
  `specs_json` JSON DEFAULT NULL COMMENT 'Додаткові характеристики у JSON форматі',
  
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  INDEX `idx_category` (`category_id`),
  INDEX `idx_socket` (`socket`),
  INDEX `idx_ram_type` (`ram_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Компоненти ПК';

-- ===============================================
-- Таблиця: saved_builds (Збережені збірки)
-- ===============================================
CREATE TABLE `saved_builds` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `build_name` VARCHAR(255) NOT NULL COMMENT 'Назва збірки',
  `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Загальна вартість збірки',
  `total_tdp` INT UNSIGNED DEFAULT NULL COMMENT 'Загальне споживання енергії',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Збережені конфігурації ПК';

-- ===============================================
-- Таблиця: build_items (Компоненти у збірці)
-- ===============================================
CREATE TABLE `build_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `build_id` INT UNSIGNED NOT NULL,
  `component_id` INT UNSIGNED NOT NULL,
  `quantity` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  
  FOREIGN KEY (`build_id`) REFERENCES `saved_builds`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`component_id`) REFERENCES `components`(`id`) ON DELETE CASCADE,
  INDEX `idx_build` (`build_id`),
  INDEX `idx_component` (`component_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Зв\'язок збірок та компонентів';

-- ===============================================
-- НАПОВНЕННЯ БАЗИ ДАНИХ
-- ===============================================

-- Вставка категорій
INSERT INTO `categories` (`slug`, `name`, `sort_order`) VALUES
('cpu', 'Процесор', 1),
('motherboard', 'Материнська плата', 2),
('ram', 'Оперативна пам\'ять', 3),
('gpu', 'Відеокарта', 4),
('psu', 'Блок живлення', 5),
('case', 'Корпус', 6),
('storage', 'Накопичувач', 7);

-- Вставка тестового користувача (пароль: test123)
INSERT INTO `users` (`login`, `password`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('testuser', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- ===============================================
-- Процесори (CPU) - AM5 та LGA1700
-- ===============================================
INSERT INTO `components` (`category_id`, `name`, `price`, `socket`, `tdp`, `specs_json`) VALUES
-- AMD AM5
(1, 'AMD Ryzen 5 7600X', 9500.00, 'AM5', 105, JSON_OBJECT('cores', 6, 'threads', 12, 'base_clock', '4.7 GHz', 'boost_clock', '5.3 GHz')),
(1, 'AMD Ryzen 7 7700X', 13500.00, 'AM5', 105, JSON_OBJECT('cores', 8, 'threads', 16, 'base_clock', '4.5 GHz', 'boost_clock', '5.4 GHz')),
(1, 'AMD Ryzen 9 7900X', 19000.00, 'AM5', 170, JSON_OBJECT('cores', 12, 'threads', 24, 'base_clock', '4.7 GHz', 'boost_clock', '5.4 GHz')),

-- Intel LGA1700
(1, 'Intel Core i5-13400F', 7200.00, 'LGA1700', 65, JSON_OBJECT('cores', 10, 'threads', 16, 'base_clock', '2.5 GHz', 'boost_clock', '4.6 GHz')),
(1, 'Intel Core i7-13700K', 16500.00, 'LGA1700', 125, JSON_OBJECT('cores', 16, 'threads', 24, 'base_clock', '3.4 GHz', 'boost_clock', '5.4 GHz')),
(1, 'Intel Core i9-13900K', 23000.00, 'LGA1700', 125, JSON_OBJECT('cores', 24, 'threads', 32, 'base_clock', '3.0 GHz', 'boost_clock', '5.8 GHz'));

-- ===============================================
-- Материнські плати (Motherboard)
-- ===============================================
INSERT INTO `components` (`category_id`, `name`, `price`, `socket`, `ram_type`, `specs_json`) VALUES
-- AM5 + DDR5
(2, 'ASUS TUF Gaming B650-PLUS', 7800.00, 'AM5', 'DDR5', JSON_OBJECT('form_factor', 'ATX', 'max_ram', '128GB', 'chipset', 'B650')),
(2, 'MSI MAG B650 TOMAHAWK', 8500.00, 'AM5', 'DDR5', JSON_OBJECT('form_factor', 'ATX', 'max_ram', '128GB', 'chipset', 'B650')),
(2, 'Gigabyte X670 AORUS ELITE AX', 11500.00, 'AM5', 'DDR5', JSON_OBJECT('form_factor', 'ATX', 'max_ram', '128GB', 'chipset', 'X670')),

-- LGA1700 + DDR4
(2, 'ASUS PRIME B660-PLUS D4', 5500.00, 'LGA1700', 'DDR4', JSON_OBJECT('form_factor', 'ATX', 'max_ram', '128GB', 'chipset', 'B660')),
(2, 'MSI PRO B660M-A DDR4', 4800.00, 'LGA1700', 'DDR4', JSON_OBJECT('form_factor', 'Micro-ATX', 'max_ram', '128GB', 'chipset', 'B660')),

-- LGA1700 + DDR5
(2, 'Gigabyte Z790 AORUS ELITE AX', 12500.00, 'LGA1700', 'DDR5', JSON_OBJECT('form_factor', 'ATX', 'max_ram', '128GB', 'chipset', 'Z790'));

-- ===============================================
-- Оперативна пам'ять (RAM)
-- ===============================================
INSERT INTO `components` (`category_id`, `name`, `price`, `ram_type`, `specs_json`) VALUES
-- DDR4
(3, 'Kingston Fury Beast 16GB (2x8GB) DDR4-3200', 1800.00, 'DDR4', JSON_OBJECT('capacity', '16GB', 'speed', '3200 MHz', 'kit', '2x8GB', 'cas_latency', 'CL16')),
(3, 'Corsair Vengeance LPX 32GB (2x16GB) DDR4-3600', 3200.00, 'DDR4', JSON_OBJECT('capacity', '32GB', 'speed', '3600 MHz', 'kit', '2x16GB', 'cas_latency', 'CL18')),
(3, 'G.Skill Ripjaws V 16GB (2x8GB) DDR4-3600', 1900.00, 'DDR4', JSON_OBJECT('capacity', '16GB', 'speed', '3600 MHz', 'kit', '2x8GB', 'cas_latency', 'CL16')),

-- DDR5
(3, 'Kingston Fury Beast 32GB (2x16GB) DDR5-5200', 4500.00, 'DDR5', JSON_OBJECT('capacity', '32GB', 'speed', '5200 MHz', 'kit', '2x16GB', 'cas_latency', 'CL36')),
(3, 'Corsair Vengeance 32GB (2x16GB) DDR5-6000', 5200.00, 'DDR5', JSON_OBJECT('capacity', '32GB', 'speed', '6000 MHz', 'kit', '2x16GB', 'cas_latency', 'CL36')),
(3, 'G.Skill Trident Z5 RGB 32GB (2x16GB) DDR5-6400', 6000.00, 'DDR5', JSON_OBJECT('capacity', '32GB', 'speed', '6400 MHz', 'kit', '2x16GB', 'cas_latency', 'CL32'));

-- ===============================================
-- Відеокарти (GPU)
-- ===============================================
INSERT INTO `components` (`category_id`, `name`, `price`, `tdp`, `specs_json`) VALUES
(4, 'NVIDIA GeForce RTX 4060', 13000.00, 115, JSON_OBJECT('vram', '8GB GDDR6', 'bus_width', '128-bit', 'recommended_psu', '550W')),
(4, 'AMD Radeon RX 7600', 11500.00, 165, JSON_OBJECT('vram', '8GB GDDR6', 'bus_width', '128-bit', 'recommended_psu', '550W')),
(4, 'NVIDIA GeForce RTX 4070', 23000.00, 200, JSON_OBJECT('vram', '12GB GDDR6X', 'bus_width', '192-bit', 'recommended_psu', '650W')),
(4, 'AMD Radeon RX 7800 XT', 21500.00, 263, JSON_OBJECT('vram', '16GB GDDR6', 'bus_width', '256-bit', 'recommended_psu', '700W')),
(4, 'NVIDIA GeForce RTX 4080', 48000.00, 320, JSON_OBJECT('vram', '16GB GDDR6X', 'bus_width', '256-bit', 'recommended_psu', '850W'));

-- ===============================================
-- Блоки живлення (PSU)
-- ===============================================
INSERT INTO `components` (`category_id`, `name`, `price`, `psu_wattage`, `specs_json`) VALUES
(5, 'Corsair CV550 550W', 2100.00, 550, JSON_OBJECT('efficiency', '80+ Bronze', 'modular', false)),
(5, 'Cooler Master MWE 650W', 2600.00, 650, JSON_OBJECT('efficiency', '80+ Bronze', 'modular', false)),
(5, 'be quiet! Pure Power 11 700W', 3500.00, 700, JSON_OBJECT('efficiency', '80+ Gold', 'modular', false)),
(5, 'Seasonic Focus GX-850 850W', 5200.00, 850, JSON_OBJECT('efficiency', '80+ Gold', 'modular', true)),
(5, 'EVGA SuperNOVA 1000 G5 1000W', 7500.00, 1000, JSON_OBJECT('efficiency', '80+ Gold', 'modular', true));

-- ===============================================
-- Корпуси (Case)
-- ===============================================
INSERT INTO `components` (`category_id`, `name`, `price`, `specs_json`) VALUES
(6, 'Aerocool Cylon Black', 1500.00, JSON_OBJECT('form_factor', 'ATX, Micro-ATX, Mini-ITX', 'color', 'Black', 'side_panel', 'Acrylic')),
(6, 'DeepCool MATREXX 55 MESH', 2200.00, JSON_OBJECT('form_factor', 'ATX, Micro-ATX, Mini-ITX', 'color', 'Black', 'side_panel', 'Tempered Glass')),
(6, 'NZXT H510 Flow', 3800.00, JSON_OBJECT('form_factor', 'ATX, Micro-ATX, Mini-ITX', 'color', 'Black/White', 'side_panel', 'Tempered Glass')),
(6, 'Fractal Design Meshify C', 4200.00, JSON_OBJECT('form_factor', 'ATX, Micro-ATX, Mini-ITX', 'color', 'Black', 'side_panel', 'Tempered Glass')),
(6, 'Corsair 4000D Airflow', 4500.00, JSON_OBJECT('form_factor', 'ATX, Micro-ATX, Mini-ITX', 'color', 'Black/White', 'side_panel', 'Tempered Glass'));

-- ===============================================
-- Накопичувачі (Storage)
-- ===============================================
INSERT INTO `components` (`category_id`, `name`, `price`, `specs_json`) VALUES
(7, 'Kingston NV2 500GB NVMe', 1400.00, JSON_OBJECT('type', 'SSD NVMe', 'capacity', '500GB', 'interface', 'M.2 PCIe 4.0', 'read_speed', '3500 MB/s')),
(7, 'Samsung 980 1TB NVMe', 2800.00, JSON_OBJECT('type', 'SSD NVMe', 'capacity', '1TB', 'interface', 'M.2 PCIe 3.0', 'read_speed', '3500 MB/s')),
(7, 'WD Blue SN570 1TB NVMe', 2600.00, JSON_OBJECT('type', 'SSD NVMe', 'capacity', '1TB', 'interface', 'M.2 PCIe 3.0', 'read_speed', '3500 MB/s')),
(7, 'Seagate Barracuda 2TB HDD', 1900.00, JSON_OBJECT('type', 'HDD', 'capacity', '2TB', 'interface', 'SATA 6Gb/s', 'rpm', '7200'));

-- ===============================================
-- ПРИКЛАД ЗБІРКИ (для тестування)
-- ===============================================

-- Збірка #1: Бюджетна збірка на Intel + DDR4
INSERT INTO `saved_builds` (`user_id`, `build_name`, `total_price`, `total_tdp`) VALUES
(1, 'Бюджетна збірка Intel', 42700.00, 345);

-- Компоненти збірки #1
INSERT INTO `build_items` (`build_id`, `component_id`, `quantity`) 
SELECT 1, id, 1 FROM `components` WHERE `name` = 'Intel Core i5-13400F'
UNION ALL
SELECT 1, id, 1 FROM `components` WHERE `name` = 'ASUS PRIME B660-PLUS D4'
UNION ALL
SELECT 1, id, 1 FROM `components` WHERE `name` = 'Kingston Fury Beast 16GB (2x8GB) DDR4-3200'
UNION ALL
SELECT 1, id, 1 FROM `components` WHERE `name` = 'NVIDIA GeForce RTX 4060'
UNION ALL
SELECT 1, id, 1 FROM `components` WHERE `name` = 'Corsair CV550 550W'
UNION ALL
SELECT 1, id, 1 FROM `components` WHERE `name` = 'DeepCool MATREXX 55 MESH'
UNION ALL
SELECT 1, id, 1 FROM `components` WHERE `name` = 'Kingston NV2 500GB NVMe';

-- ===============================================
-- ЗАВЕРШЕННЯ
-- ===============================================

SET FOREIGN_KEY_CHECKS = 1;

-- Перевірка створених таблиць
SHOW TABLES;












