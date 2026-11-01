-- WebMarket POS Database Schema & Seed Data

CREATE DATABASE IF NOT EXISTS `yayasa41_ti_kelompok5` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `yayasa41_ti_kelompok5`;

-- 1. Table users
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'kasir') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Table categories
CREATE TABLE IF NOT EXISTS `categories` (
    `category_id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_name` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Table products
CREATE TABLE IF NOT EXISTS `products` (
    `products_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `stock` INT NOT NULL,
    `category_id` INT,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Table transactions
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `transaction_code` VARCHAR(50) NOT NULL,
    `buyer_name` VARCHAR(100) NOT NULL,
    `products_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `total` DECIMAL(10,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default Users (Password: admin / kasir)
INSERT INTO `users` (`username`, `password`, `name`, `role`) VALUES 
('admin', '21232f297a57a5a743894a0e4a801fc3', 'Administrator Toko', 'admin'),
('kasir', 'c7911af3adbd12a035b289556d964c67', 'Kasir Utama', 'kasir')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- Default Categories
INSERT INTO `categories` (`category_id`, `category_name`) VALUES 
(1, 'Makanan & Minuman'),
(2, 'Kebutuhan Pokok'),
(3, 'Perawatan & Kebersihan'),
(4, 'Elektronik & Aksesoris')
ON DUPLICATE KEY UPDATE `category_id`=`category_id`;

-- Default Products
INSERT INTO `products` (`products_id`, `name`, `price`, `stock`, `category_id`) VALUES 
(1, 'Minyak Goreng 2L', 34000.00, 25, 2),
(2, 'Beras Premium 5kg', 72000.00, 18, 2),
(3, 'Kopi Arabika 250g', 45000.00, 30, 1),
(4, 'Susu UHT Full Cream 1L', 18500.00, 40, 1),
(5, 'Sabun Cuci Piring 750ml', 14000.00, 15, 3),
(6, 'Shampoo Anti Ketombe 170ml', 26000.00, 22, 3),
(7, 'Kabel Data Type-C Fast Charging', 35000.00, 12, 4)
ON DUPLICATE KEY UPDATE `products_id`=`products_id`;
