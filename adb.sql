-- Buat database
CREATE DATABASE IF NOT EXISTS `ecommerce_db`;
USE `ecommerce_db`;

-- Buat tabel products
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contoh data dummy (opsional)
INSERT INTO `products` (`name`, `price`) VALUES
('Laptop ASUS', 8500000),
('Smartphone Samsung', 4500000),
('Headphone Wireless', 1200000),
('Mouse Gaming', 350000),
('Keyboard Mechanical', 650000),
('Monitor 24 inch', 1800000),
('Printer Epson', 2200000),
('External SSD 1TB', 1500000),
('Webcam HD', 500000),
('Speaker Bluetooth', 750000);