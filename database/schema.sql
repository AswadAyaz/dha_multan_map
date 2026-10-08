-- ==========================================================
-- DHA Multan Interactive Property Map Database Schema
-- Compatible with MySQL 5.7+ / MySQL 8.0+ / MariaDB
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `property_images`;
DROP TABLE IF EXISTS `properties`;
DROP TABLE IF EXISTS `blocks`;
DROP TABLE IF EXISTS `phases`;
DROP TABLE IF EXISTS `admins`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Admins Table
CREATE TABLE `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Phases Table
CREATE TABLE `phases` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Blocks / Sectors Table
CREATE TABLE `blocks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `phase_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `sector_type` VARCHAR(50) DEFAULT 'Residential',
  `center_lat` DECIMAL(10, 8) NULL,
  `center_lng` DECIMAL(11, 8) NULL,
  `boundary_geojson` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`phase_id`) REFERENCES `phases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Properties Table
CREATE TABLE `properties` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `plot_number` VARCHAR(50) NOT NULL,
  `phase_id` INT NOT NULL,
  `block_id` INT NOT NULL,
  `street` VARCHAR(100) NULL,
  `property_type` VARCHAR(50) NOT NULL DEFAULT 'Residential Plot',
  `plot_size` VARCHAR(50) NOT NULL,
  `price` DECIMAL(15, 2) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'Available',
  `description` TEXT,
  `latitude` DECIMAL(10, 8) NOT NULL,
  `longitude` DECIMAL(11, 8) NOT NULL,
  `polygon_coordinates` TEXT NULL,
  `featured_image` VARCHAR(255) NULL,
  `owner_dealer_name` VARCHAR(150) DEFAULT 'Authorized DHA Multan Realtor',
  `phone` VARCHAR(50) NOT NULL DEFAULT '+92 300 1234567',
  `whatsapp` VARCHAR(50) NOT NULL DEFAULT '923001234567',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_status` (`status`),
  INDEX `idx_plot_number` (`plot_number`),
  INDEX `idx_property_type` (`property_type`),
  INDEX `idx_price` (`price`),
  FOREIGN KEY (`phase_id`) REFERENCES `phases` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`block_id`) REFERENCES `blocks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Property Images Table
CREATE TABLE `property_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `property_id` INT NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `is_cover` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- SEED DATA FOR DHA MULTAN
-- Default Admin Password: admin123 (BCrypt Hash)
-- ==========================================================

INSERT INTO `admins` (`id`, `name`, `email`, `password_hash`) VALUES
(1, 'DHA Multan Admin', 'admin@dhamultanmap.pk', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRpu.5UXzoAmZORQndHSZmE7otgmC');

-- DHA Multan Phase 1
INSERT INTO `phases` (`id`, `name`, `description`) VALUES
(1, 'Phase 1', 'Main flagship phase of DHA Multan comprising Sectors A to V, Central Commercial, Rumanza Golf Community, and The Arena.');

-- DHA Multan Sectors / Blocks
INSERT INTO `blocks` (`id`, `phase_id`, `name`, `sector_type`, `center_lat`, `center_lng`) VALUES
(1, 1, 'Sector A', 'Residential 1 Kanal', 30.298000, 71.551000),
(2, 1, 'Sector B1', 'Residential 1 Kanal', 30.304000, 71.554000),
(3, 1, 'Sector B2', 'Residential 1 Kanal', 30.307000, 71.556000),
(4, 1, 'Sector C', 'Residential 1 Kanal', 30.309000, 71.558000),
(5, 1, 'Sector D', 'Residential 1 Kanal', 30.302000, 71.562000),
(6, 1, 'Sector E', 'Residential 1 Kanal', 30.295000, 71.566000),
(7, 1, 'Sector F (Rumanza)', 'Golf View 1 & 2 Kanal', 30.288000, 71.572000),
(8, 1, 'Sector G', 'Residential 1 Kanal', 30.292000, 71.559000),
(9, 1, 'Sector H', 'Residential 1 Kanal', 30.286000, 71.562000),
(10, 1, 'Sector I', 'Residential 1 Kanal', 30.281000, 71.567000),
(11, 1, 'Sector K', 'Residential 10 Marla', 30.300000, 71.571000),
(12, 1, 'Sector L', 'Residential 1 Kanal', 30.303000, 71.577000),
(13, 1, 'Sector M', 'Residential 10 Marla', 30.307000, 71.567000),
(14, 1, 'Sector N', 'Residential 2 Kanal', 30.312000, 71.575000),
(15, 1, 'Sector P', 'Residential 5 Marla', 30.315000, 71.562000),
(16, 1, 'Sector Q', 'Residential 10 Marla', 30.318000, 71.554000),
(17, 1, 'Sector R', 'Residential 10 Marla', 30.312000, 71.548000),
(18, 1, 'Sector S', 'Residential 10 Marla', 30.316000, 71.542000),
(19, 1, 'Sector T', 'Residential 5 Marla', 30.320000, 71.542000),
(20, 1, 'Sector U', 'Residential 10 Marla', 30.306000, 71.544000),
(21, 1, 'Sector V', 'Residential 5 Marla', 30.300000, 71.540000),
(22, 1, 'Central Commercial', 'Commercial 4 & 8 Marla', 30.301500, 71.557000);

-- Realistic Properties with Polygon Boundaries (Method A) and Point Markers (Method B)
INSERT INTO `properties` (`id`, `title`, `plot_number`, `phase_id`, `block_id`, `street`, `property_type`, `plot_size`, `price`, `status`, `description`, `latitude`, `longitude`, `polygon_coordinates`, `featured_image`, `owner_dealer_name`, `phone`, `whatsapp`) VALUES
(1, 'Prime Corner 1 Kanal Plot on 80ft Road', 'Plot 102', 1, 1, 'Street 4', 'Residential Plot', '1 Kanal', 24500000.00, 'Available', 'Direct deal corner plot in Sector A, DHA Multan. Walking distance to Sector A mosque and commercial hub. Possession ready, all dues cleared.', 30.298200, 71.551200, '[[30.29845, 71.55095], [30.29845, 71.55145], [30.29795, 71.55145], [30.29795, 71.55095]]', 'uploads/properties/plot-102.svg', 'Multan Estate Advisors', '+92 301 8654321', '923018654321'),

(2, 'Luxury 1 Kanal Facing Park Residential Plot', 'Plot 215', 1, 1, 'Main Boulevard', 'Residential Plot', '1 Kanal', 27000000.00, 'Reserved', 'Scenic park-facing residential plot in Sector A. Very near to Bab-e-Khyber main entrance gate. Ideal for immediate luxury home construction.', 30.297400, 71.552500, '[[30.29765, 71.55225], [30.29765, 71.55275], [30.29715, 71.55275], [30.29715, 71.55225]]', 'uploads/properties/plot-215.svg', 'Al-Razaq Properties DHA', '+92 302 7788990', '923027788990'),

(3, '10 Marla Hot Location Plot in Sector M', 'Plot 45', 1, 13, 'Street 12', 'Residential Plot', '10 Marla', 11500000.00, 'Available', 'Excellent investment opportunity in Sector M, DHA Multan. Rapid development underway with underground utilities installed.', 30.306800, 71.567200, '[[30.30700, 71.56700], [30.30700, 71.56740], [30.30660, 71.56740], [30.30660, 71.56700]]', 'uploads/properties/plot-45.svg', 'DHA Multan Direct Realtor', '+92 300 9876543', '923009876543'),

(4, '4 Marla Commercial Plot in Central Commercial', 'Comm 18', 1, 22, 'Civic Center Boulevard', 'Commercial Plot', '4 Marla', 38000000.00, 'Available', 'Top tier 4 Marla commercial plot in DHA Multan Downtown Central Commercial. High ROI potential for corporate banks, plazas, and retail stores.', 30.301800, 71.557200, '[[30.30200, 71.55700], [30.30200, 71.55740], [30.30160, 71.55740], [30.30160, 71.55700]]', 'uploads/properties/comm-18.svg', 'Premier Commercial Estates', '+92 321 4455667', '923214455667'),

(5, '5 Marla Residential Plot in Sector P', 'Plot 88', 1, 15, 'Street 8', 'Residential Plot', '5 Marla', 6200000.00, 'Sold', 'Affordable residential plot in Sector P. Fully developed sector with water, electricity and carpeted roads.', 30.315200, 71.562300, '[[30.31535, 71.56215], [30.31535, 71.56245], [30.31505, 71.56245], [30.31505, 71.56215]]', 'uploads/properties/plot-88.svg', 'Star Marketing DHA', '+92 333 5566778', '923335566778'),

(6, 'Rumanza Golf Facing 2 Kanal Signature Villa Plot', 'Plot 08', 1, 7, 'Fairway Drive', 'Residential Plot', '2 Kanal', 65000000.00, 'Available', 'Prestigious 2 Kanal plot on Sir Nick Faldo Signature 18-hole Rumanza Golf Course. Unobstructed lush fairway greens view, premier gated security.', 30.288200, 71.572400, '[[30.28850, 71.57210], [30.28850, 71.57270], [30.28790, 71.57270], [30.28790, 71.57210]]', 'uploads/properties/plot-08.svg', 'Rumanza Living Consultants', '+92 300 8899112', '923008899112'),

(7, 'Designer 1 Kanal Modern Spanish House', 'House 34', 1, 2, 'Street 9', 'House', '1 Kanal', 62000000.00, 'Available', 'Newly constructed 1 Kanal modern luxury house in Sector B1. 5 master bedrooms with attached baths, Italian kitchen, servant quarter, and solar backup system.', 30.304200, 71.554200, NULL, 'uploads/properties/house-34.svg', 'Iconic Multan Builders', '+92 301 9988776', '923019988776'),

(8, '10 Marla Corner Plot in Sector R', 'Plot 120', 1, 17, 'Street 15', 'Residential Plot', '10 Marla', 10800000.00, 'Hold', 'Corner 10 Marla plot currently under token verification. Good depth and elevation in Sector R.', 30.312200, 71.548200, NULL, 'uploads/properties/plot-120.svg', 'Al-Madina Real Estate', '+92 304 3322110', '923043322110'),

(9, '1 Kanal Golden Investment Plot in Sector C', 'Plot 77', 1, 4, 'Street 3', 'Residential Plot', '1 Kanal', 22000000.00, 'Available', 'Fast-moving residential plot in Sector C, DHA Multan. Very close to the education city and health complex.', 30.309200, 71.558300, '[[30.30940, 71.55810], [30.30940, 71.55850], [30.30900, 71.55850], [30.30900, 71.55810]]', 'uploads/properties/plot-77.svg', 'DHA Multan Property Exchange', '+92 300 6677889', '923006677889'),

(10, '5 Marla Developed Residential Plot in Sector V', 'Plot 19', 1, 21, 'Street 5', 'Residential Plot', '5 Marla', 5800000.00, 'Available', 'Pocket-friendly 5 Marla plot in Sector V. Ideal for small family home or investment with expected quick appreciation.', 30.300200, 71.540300, NULL, 'uploads/properties/plot-19.svg', 'United Associates', '+92 305 1122334', '923051122334');

-- Property Gallery Images
INSERT INTO `property_images` (`property_id`, `image_path`, `is_cover`) VALUES
(1, 'uploads/properties/plot-102.svg', 1),
(1, 'uploads/properties/plot-102-site.svg', 0),
(2, 'uploads/properties/plot-215.svg', 1),
(3, 'uploads/properties/plot-45.svg', 1),
(4, 'uploads/properties/comm-18.svg', 1),
(5, 'uploads/properties/plot-88.svg', 1),
(6, 'uploads/properties/plot-08.svg', 1),
(7, 'uploads/properties/house-34.svg', 1),
(7, 'uploads/properties/house-34-interior.svg', 0),
(8, 'uploads/properties/plot-120.svg', 1),
(9, 'uploads/properties/plot-77.svg', 1),
(10, 'uploads/properties/plot-19.svg', 1);
