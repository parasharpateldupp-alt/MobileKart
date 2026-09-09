-- ============================================================
-- ONLINE MOBILE PURCHASING & DISTRIBUTING SYSTEM
-- Database Schema & Comprehensive Sample Data
-- Target: MySQL 5.7+ / 8.0+ / MariaDB 10.3+ (InnoDB, UTF-8)
-- DFD Data Stores:
--   D1: USER DB (users, suppliers)
--   D2: PRODUCT / INVENTORY DB (categories, brands, products, inventory_transactions)
--   D3: ORDERS DB (cart, cart_items, orders, order_items, stock_reservations)
--   D4: BILLING / FINANCE DB (payments, invoices, invoice_items)
--   D5: FULFILLMENT DB (fulfillments, shipment_events)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `online_mobile_distribution` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `online_mobile_distribution`;

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- D1: USER DB - Table: users
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('ADMIN', 'CUSTOMER', 'SUPPLIER') NOT NULL DEFAULT 'CUSTOMER',
  `status` ENUM('ACTIVE', 'INACTIVE', 'SUSPENDED') NOT NULL DEFAULT 'ACTIVE',
  `address` TEXT NULL,
  `city` VARCHAR(100) NULL,
  `state` VARCHAR(100) NULL,
  `pincode` VARCHAR(20) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` DATETIME NULL,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- D1: USER DB - Table: suppliers
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `supplier_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `company_name` VARCHAR(150) NOT NULL,
  `gst_number` VARCHAR(50) NOT NULL,
  `contact_person` VARCHAR(100) NOT NULL,
  `approval_status` ENUM('PENDING', 'APPROVED', 'REJECTED') DEFAULT 'APPROVED',
  `rating` DECIMAL(3,2) DEFAULT 4.80,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_suppliers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- D2: PRODUCT / INVENTORY DB - Table: categories
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `category_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `icon` VARCHAR(50) DEFAULT 'fa-mobile-alt',
  `status` ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- D2: PRODUCT / INVENTORY DB - Table: brands
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `brands`;
CREATE TABLE `brands` (
  `brand_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `logo_icon` VARCHAR(50) DEFAULT 'fa-mobile',
  `status` ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- D2: PRODUCT / INVENTORY DB - Table: products
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `product_id` INT AUTO_INCREMENT PRIMARY KEY,
  `supplier_id` INT NOT NULL,
  `brand_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  `product_name` VARCHAR(200) NOT NULL,
  `model` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `discount` INT DEFAULT 0 COMMENT 'Discount percentage (0-100)',
  `final_price` DECIMAL(10,2) NOT NULL,
  `stock_quantity` INT NOT NULL DEFAULT 0,
  `minimum_stock` INT NOT NULL DEFAULT 5,
  `ram` VARCHAR(50) NOT NULL,
  `storage` VARCHAR(50) NOT NULL,
  `processor` VARCHAR(100) NOT NULL,
  `display` VARCHAR(100) NOT NULL,
  `camera` VARCHAR(150) NOT NULL,
  `battery` VARCHAR(100) NOT NULL,
  `operating_system` VARCHAR(50) NOT NULL,
  `color` VARCHAR(50) NOT NULL,
  `warranty` VARCHAR(100) DEFAULT '1 Year Manufacturer Warranty',
  `image` VARCHAR(255) NOT NULL,
  `status` ENUM('ACTIVE', 'INACTIVE', 'OUT_OF_STOCK') DEFAULT 'ACTIVE',
  `is_featured` TINYINT(1) DEFAULT 0,
  `is_trending` TINYINT(1) DEFAULT 0,
  `rating` DECIMAL(3,2) DEFAULT 4.50,
  `reviews_count` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_products_brand` (`brand_id`),
  INDEX `idx_products_category` (`category_id`),
  INDEX `idx_products_status` (`status`),
  INDEX `idx_products_price` (`final_price`),
  CONSTRAINT `fk_products_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  CONSTRAINT `fk_products_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`brand_id`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- D2: PRODUCT / INVENTORY DB - Table: product_images
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `product_images`;
CREATE TABLE `product_images` (
  `image_id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) DEFAULT 0,
  `sort_order` INT DEFAULT 0,
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- D2: PRODUCT / INVENTORY DB - Table: inventory_transactions
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `inventory_transactions`;
CREATE TABLE `inventory_transactions` (
  `transaction_id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `transaction_type` ENUM('INITIAL', 'RESTOCK', 'PURCHASE', 'RESERVATION_HOLD', 'RESERVATION_RELEASE', 'DAMAGE_RETURN', 'ADJUSTMENT') NOT NULL,
  `quantity_change` INT NOT NULL,
  `quantity_after` INT NOT NULL,
  `reference_id` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `created_by` INT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_inv_product` (`product_id`),
  CONSTRAINT `fk_inv_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inv_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- D3: ORDERS DB - Table: cart
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `cart`;
CREATE TABLE `cart` (
  `cart_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- D3: ORDERS DB - Table: cart_items
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `cart_items`;
CREATE TABLE `cart_items` (
  `cart_item_id` INT AUTO_INCREMENT PRIMARY KEY,
  `cart_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_cart_product` (`cart_id`, `product_id`),
  CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`cart_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- D3: ORDERS DB - Table: orders
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `order_id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(50) NOT NULL UNIQUE,
  `customer_id` INT NOT NULL,
  `order_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `discount` DECIMAL(10,2) DEFAULT 0.00,
  `tax` DECIMAL(10,2) NOT NULL,
  `shipping_charge` DECIMAL(10,2) DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `payment_status` ENUM('PENDING', 'PAID', 'FAILED', 'REFUNDED') DEFAULT 'PENDING',
  `order_status` ENUM('PENDING_PAYMENT', 'ORDER_CONFIRMED', 'PROCESSING', 'PACKED', 'SHIPPED', 'IN_TRANSIT', 'OUT_FOR_DELIVERY', 'DELIVERED', 'CANCELLED') DEFAULT 'PENDING_PAYMENT',
  `shipping_name` VARCHAR(100) NOT NULL,
  `shipping_phone` VARCHAR(20) NOT NULL,
  `shipping_address` TEXT NOT NULL,
  `shipping_city` VARCHAR(100) NOT NULL,
  `shipping_state` VARCHAR(100) NOT NULL,
  `shipping_pincode` VARCHAR(20) NOT NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_orders_customer` (`customer_id`),
  INDEX `idx_orders_status` (`order_status`),
  CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- D3: ORDERS DB - Table: order_items
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `order_item_id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `supplier_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `fk_order_items_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- D3: ORDERS DB (P1.3.3) - Table: stock_reservations
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `stock_reservations`;
CREATE TABLE `stock_reservations` (
  `reservation_id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `reserved_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `expiry_time` DATETIME NOT NULL,
  `status` ENUM('ACTIVE', 'COMMITTED', 'RELEASED') DEFAULT 'ACTIVE',
  INDEX `idx_res_order` (`order_id`),
  INDEX `idx_res_product` (`product_id`),
  INDEX `idx_res_status` (`status`),
  CONSTRAINT `fk_res_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_res_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- D4: BILLING DB (P1.4) - Table: payments
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `payment_id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `customer_id` INT NOT NULL,
  `transaction_id` VARCHAR(100) NOT NULL UNIQUE,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('UPI', 'DEBIT_CARD', 'CREDIT_CARD', 'NET_BANKING') NOT NULL,
  `payment_status` ENUM('PENDING', 'SUCCESS', 'FAILED', 'CANCELLED') DEFAULT 'PENDING',
  `gateway_token` VARCHAR(255) NULL,
  `gateway_response` TEXT NULL,
  `payment_date` DATETIME NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pay_order` (`order_id`),
  CONSTRAINT `fk_pay_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  CONSTRAINT `fk_pay_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- D4: BILLING DB (P1.4.3) - Table: invoices
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `invoice_id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
  `order_id` INT NOT NULL UNIQUE,
  `payment_id` INT NOT NULL,
  `customer_id` INT NOT NULL,
  `invoice_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `discount` DECIMAL(10,2) DEFAULT 0.00,
  `tax_amount` DECIMAL(10,2) NOT NULL,
  `shipping_charge` DECIMAL(10,2) DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `billing_address` TEXT NOT NULL,
  `status` ENUM('GENERATED', 'PAID', 'CANCELLED') DEFAULT 'PAID',
  CONSTRAINT `fk_invoices_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  CONSTRAINT `fk_invoices_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`),
  CONSTRAINT `fk_invoices_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- D4: BILLING DB - Table: invoice_items
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `invoice_items`;
CREATE TABLE `invoice_items` (
  `invoice_item_id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` INT NOT NULL,
  `product_name` VARCHAR(200) NOT NULL,
  `model` VARCHAR(100) NOT NULL,
  `quantity` INT NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `total_price` DECIMAL(10,2) NOT NULL,
  CONSTRAINT `fk_invoice_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- D5: FULFILLMENT DB (P1.5) - Table: fulfillments
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `fulfillments`;
CREATE TABLE `fulfillments` (
  `fulfillment_id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL UNIQUE,
  `shipment_id` VARCHAR(50) NOT NULL UNIQUE,
  `tracking_id` VARCHAR(50) NOT NULL UNIQUE,
  `carrier` VARCHAR(100) NOT NULL DEFAULT 'Ekart Logistics',
  `shipment_date` DATETIME NULL,
  `estimated_delivery` DATETIME NULL,
  `delivered_date` DATETIME NULL,
  `shipment_status` ENUM('ORDER_CONFIRMED', 'PROCESSING', 'PACKED', 'SHIPPED', 'IN_TRANSIT', 'OUT_FOR_DELIVERY', 'DELIVERED', 'CANCELLED') DEFAULT 'ORDER_CONFIRMED',
  `delivery_address` TEXT NOT NULL,
  `manifest` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_ful_status` (`shipment_status`),
  CONSTRAINT `fk_fulfillments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- D5: FULFILLMENT DB - Table: shipment_events
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `shipment_events`;
CREATE TABLE `shipment_events` (
  `event_id` INT AUTO_INCREMENT PRIMARY KEY,
  `fulfillment_id` INT NOT NULL,
  `status` VARCHAR(50) NOT NULL,
  `location` VARCHAR(150) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `event_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ship_events_ful` FOREIGN KEY (`fulfillment_id`) REFERENCES `fulfillments` (`fulfillment_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: reviews
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `review_id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `customer_id` INT NOT NULL,
  `rating` INT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `title` VARCHAR(150) NOT NULL,
  `comment` TEXT NOT NULL,
  `status` ENUM('APPROVED', 'PENDING', 'REJECTED') DEFAULT 'APPROVED',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_reviews_product` (`product_id`),
  CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: promotions
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `promotions`;
CREATE TABLE `promotions` (
  `promo_id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `discount_percent` INT NOT NULL DEFAULT 0,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `minimum_order` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `max_discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `valid_from` DATE NOT NULL,
  `valid_to` DATE NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: notifications
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `notification_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: support_requests
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `support_requests`;
CREATE TABLE `support_requests` (
  `request_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED') DEFAULT 'OPEN',
  `priority` ENUM('LOW', 'MEDIUM', 'HIGH') DEFAULT 'MEDIUM',
  `admin_reply` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_support_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: activity_logs
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `log_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50) NULL,
  `entity_id` VARCHAR(50) NULL,
  `details` TEXT NULL,
  `ip_address` VARCHAR(50) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_logs_user` (`user_id`),
  INDEX `idx_logs_action` (`action`),
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: system_settings
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `setting_key` VARCHAR(50) PRIMARY KEY,
  `setting_value` TEXT NOT NULL,
  `description` VARCHAR(255) NULL,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SAMPLE DATA INSERTIONS
-- ============================================================

-- System Settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('site_name', 'Online Mobile Purchasing & Distributing System', 'Platform public branding name'),
('tax_rate_percent', '18', 'Standard GST on mobile phones in percent'),
('free_shipping_threshold', '5000', 'Minimum order total in INR for free express delivery'),
('standard_shipping_charge', '149', 'Default shipping charge when total is below threshold'),
('stock_reservation_minutes', '15', 'Minutes before temporary checkout stock reservation expires'),
('contact_email', 'support@mobiledistribution.com', 'Official customer support email'),
('contact_phone', '+91 99744 10030', 'Customer toll-free helpline number'),
('currency_symbol', '₹', 'Display currency symbol');

-- Admin Account: PATANJALI (PATU1522)
INSERT INTO `users` (`user_id`, `name`, `email`, `phone`, `password_hash`, `role`, `status`, `address`, `city`, `state`, `pincode`, `created_at`) VALUES
(1, 'PATANJALI', 'patanjali@patu.com', '9974410030', '$2y$10$6Dsi6c8ymJMJvmyQHOD9O./gge5ZKCS7feU8p80rQpfNgEJaQeKp.', 'ADMIN', 'ACTIVE', 'dandi daji faliya ta.dist. valsad 396385', 'Valsad', 'Gujarat', '396385', NOW());

-- Suppliers table
INSERT INTO `suppliers` (`supplier_id`, `user_id`, `company_name`, `gst_number`, `contact_person`, `approval_status`, `rating`) VALUES
(1, 1, 'PATANJALI Mobile Distribution', '29AAAAA0000A1Z5', 'PATANJALI', 'APPROVED', 4.95);

-- Categories
INSERT INTO `categories` (`category_id`, `name`, `slug`, `description`, `icon`) VALUES
(1, 'Flagship Mobiles', 'flagship-mobiles', 'High-performance premium smartphones with cutting-edge processors and displays', 'fa-crown'),
(2, '5G Smartphones', '5g-smartphones', 'Next-generation high speed connectivity smartphones', 'fa-wifi'),
(3, 'Camera Centric', 'camera-centric', 'Studio-quality sensors, telephoto periscope zoom, and optical stabilization', 'fa-camera'),
(4, 'Gaming Phones', 'gaming-phones', 'High refresh rates, vapor chamber cooling, and high capacity batteries', 'fa-gamepad'),
(5, 'Budget & Value', 'budget-value', 'Reliable everyday mobile phones packed with essential features', 'fa-wallet'),
(6, 'Foldable Phones', 'foldable-phones', 'Revolutionary folding screen devices offering dual tablet and phone utility', 'fa-tablet-alt');

-- Brands
INSERT INTO `brands` (`brand_id`, `name`, `slug`, `logo_icon`) VALUES
(1, 'Samsung', 'samsung', 'fa-mobile-alt'),
(2, 'Apple', 'apple', 'fa-apple'),
(3, 'OnePlus', 'oneplus', 'fa-plus-square'),
(4, 'Xiaomi', 'xiaomi', 'fa-bolt'),
(5, 'Nothing', 'nothing', 'fa-dot-circle'),
(6, 'Vivo', 'vivo', 'fa-play-circle'),
(7, 'Realme', 'realme', 'fa-star'),
(8, 'Motorola', 'motorola', 'fa-shield-alt'),
(9, 'Google', 'google', 'fa-google'),
(10, 'iQOO', 'iqoo', 'fa-tachometer-alt');

-- Products (16 realistic models with full specs and Indian pricing in INR)
INSERT INTO `products` (`product_id`, `supplier_id`, `brand_id`, `category_id`, `product_name`, `model`, `description`, `price`, `discount`, `final_price`, `stock_quantity`, `minimum_stock`, `ram`, `storage`, `processor`, `display`, `camera`, `battery`, `operating_system`, `color`, `warranty`, `image`, `status`, `is_featured`, `is_trending`, `rating`, `reviews_count`) VALUES
(1, 1, 1, 1, 'Samsung Galaxy S24 Ultra 5G', 'SM-S928B', 'The ultimate Galaxy flagship with Galaxy AI, titanium frame, built-in S-Pen, and 200MP quad camera setup.', 134999.00, 10, 121499.00, 18, 5, '12 GB', '256 GB', 'Snapdragon 8 Gen 3 for Galaxy', '6.8" Dynamic AMOLED 2X 120Hz QHD+', '200MP + 50MP + 12MP + 10MP Quad Camera', '5000 mAh (45W Fast Charging)', 'One UI 6.1 (Android 14)', 'Titanium Gray', '1 Year Brand Warranty', 'assets/images/products/s24_ultra.svg', 'ACTIVE', 1, 1, 4.90, 48),
(2, 1, 2, 1, 'Apple iPhone 15 Pro Max', 'A3106', 'Forged in titanium with the groundbreaking A17 Pro chip, customizable Action button, and 5x optical zoom.', 159900.00, 7, 148700.00, 12, 4, '8 GB', '256 GB', 'A17 Pro Hexa-core', '6.7" Super Retina XDR OLED 120Hz', '48MP Main + 12MP Ultra-Wide + 12MP 5x Telephoto', '4422 mAh (MagSafe Wireless)', 'iOS 17', 'Natural Titanium', '1 Year Apple Care Warranty', 'assets/images/products/iphone15_promax.svg', 'ACTIVE', 1, 1, 4.95, 62),
(3, 1, 3, 2, 'OnePlus 12 5G', 'CPH2573', 'Smooth Beyond Belief powered by Snapdragon 8 Gen 3, 4th Gen Hasselblad Camera, and 100W SUPERVOOC charging.', 69999.00, 8, 64399.00, 25, 6, '16 GB', '512 GB', 'Snapdragon 8 Gen 3', '6.82" 2K ProXDR Display 120Hz LTPO', '50MP LYT-808 + 64MP Periscope + 48MP Ultra-Wide', '5400 mAh (100W Flash Charge)', 'OxygenOS 14 (Android 14)', 'Flowy Emerald', '1 Year Brand Warranty', 'assets/images/products/oneplus12.svg', 'ACTIVE', 1, 1, 4.80, 35),
(4, 1, 4, 3, 'Xiaomi 14 Ultra 5G', '24030PN60G', 'Leica Optical Lens with 1-inch sensor, stepless variable aperture, and liquid display aesthetics.', 99999.00, 10, 89999.00, 14, 4, '16 GB', '512 GB', 'Snapdragon 8 Gen 3', '6.73" WQHD+ AMOLED 120Hz LTPO', '50MP 1-inch LYT-900 + 50MP + 50MP + 50MP Quad Leica', '5000 mAh (90W HyperCharge)', 'Xiaomi HyperOS (Android 14)', 'Titanium Black', '1 Year Brand Warranty', 'assets/images/products/xiaomi14_ultra.svg', 'ACTIVE', 0, 1, 4.75, 29),
(5, 1, 5, 2, 'Nothing Phone (2) 5G', 'A065', 'Iconic transparent Glyph Interface 2.0 with custom Nothing OS 2.5 and flagship grade dual 50MP sensors.', 44999.00, 18, 36899.00, 30, 8, '12 GB', '256 GB', 'Snapdragon 8+ Gen 1', '6.7" Flexible LTPO OLED 120Hz', '50MP Sony IMX890 OIS + 50MP Ultra-Wide', '4700 mAh (45W Fast Charging)', 'Nothing OS 2.5 (Android 14)', 'Dark Gray', '1 Year Brand Warranty', 'assets/images/products/nothing2.svg', 'ACTIVE', 1, 0, 4.70, 41),
(6, 1, 6, 3, 'Vivo X100 Pro 5G', 'V2309', 'Co-engineered with ZEISS featuring APO Floating Telephoto Camera, Dimensity 9300 chipset, and V3 imaging chip.', 89999.00, 12, 79199.00, 16, 5, '16 GB', '512 GB', 'MediaTek Dimensity 9300', '6.78" 8T LTPO AMOLED 120Hz', '50MP 1-inch ZEISS Main + 50MP Periscope + 50MP Wide', '5400 mAh (100W FlashCharge)', 'Funtouch OS 14 (Android 14)', 'Asteroid Black', '1 Year Brand Warranty', 'assets/images/products/vivo_x100.svg', 'ACTIVE', 1, 1, 4.85, 33),
(7, 1, 7, 5, 'Realme 12 Pro+ 5G', 'RMX3840', 'Luxury watch design crafted with Ollivier Saveo featuring 64MP Periscope Portrait Camera and vegan leather.', 34999.00, 15, 29749.00, 45, 10, '12 GB', '256 GB', 'Snapdragon 7s Gen 2', '6.7" 120Hz Curved Vision OLED', '64MP Periscope OIS + 50MP Sony IMX890 + 8MP Ultra-Wide', '5000 mAh (67W SUPERVOOC)', 'realme UI 5.0 (Android 14)', 'Submarine Blue', '1 Year Brand Warranty', 'assets/images/products/realme12_pro.svg', 'ACTIVE', 0, 1, 4.60, 52),
(8, 1, 8, 2, 'Motorola Edge 50 Pro 5G', 'XT2403-1', 'World’s 1st Pantone validated display and camera, silicone vegan leather finish, and 125W TurboPower.', 36999.00, 14, 31819.00, 22, 6, '12 GB', '256 GB', 'Snapdragon 7 Gen 3', '6.7" 1.5K 144Hz pOLED Curved Display', '50MP OIS f/1.4 + 13MP Macro/Wide + 10MP 3x Telephoto', '4500 mAh (125W TurboPower)', 'Hello UI (Android 14)', 'Luxe Lavender', '1 Year Brand Warranty', 'assets/images/products/moto_edge50.svg', 'ACTIVE', 1, 0, 4.65, 24),
(9, 1, 9, 3, 'Google Pixel 8 Pro 5G', 'GC3VE', 'Engineered by Google with Tensor G3 chip, best-in-class AI computational photography, and 7 years of OS updates.', 106999.00, 16, 89879.00, 15, 4, '12 GB', '128 GB', 'Google Tensor G3 + Titan M2', '6.7" Super Actua OLED 120Hz LTPO', '50MP Octa PD OIS + 48MP Quad PD Wide + 48MP Telephoto', '5050 mAh (30W Fast Charging)', 'Android 14 (Pure Pixel Experience)', 'Bay Blue', '1 Year Google Warranty', 'assets/images/products/pixel8_pro.svg', 'ACTIVE', 1, 1, 4.78, 38),
(10, 1, 10, 4, 'iQOO Neo 9 Pro 5G', 'I2217', 'Flagship dual chip beast powered by Snapdragon 8 Gen 2 and Supercomputing Chip Q1 for esports gaming.', 39999.00, 13, 34799.00, 35, 8, '12 GB', '256 GB', 'Snapdragon 8 Gen 2 + Supercomputing Chip Q1', '6.78" 1.5K 144Hz LTPO AMOLED', '50MP Sony IMX920 VCS OIS + 8MP Ultra-Wide', '5160 mAh (120W FlashCharge)', 'Funtouch OS 14 (Android 14)', 'Fiery Red Dual-Tone', '1 Year Brand Warranty', 'assets/images/products/iqoo_neo9.svg', 'ACTIVE', 1, 1, 4.82, 44),
(11, 1, 4, 5, 'Redmi Note 13 Pro 5G', '2312DRA50G', 'Ultra-clear 200MP camera with OIS, 1.5K curved AMOLED display, and 67W turbo fast charging.', 28999.00, 17, 24069.00, 50, 10, '8 GB', '256 GB', 'Snapdragon 7s Gen 2', '6.67" 1.5K AMOLED 120Hz Gorilla Glass Victus', '200MP HP3 OIS + 8MP Ultra-Wide + 2MP Macro', '5100 mAh (67W Turbo Charge)', 'MIUI 14 (Android 13/14 upgradeable)', 'Coral Purple', '1 Year Brand Warranty', 'assets/images/products/redmi_note13.svg', 'ACTIVE', 0, 1, 4.50, 59),
(12, 1, 1, 6, 'Samsung Galaxy Z Fold 5 5G', 'SM-F946B', 'Revolutionary productivity unfolded with massive 7.6" inner display, flex hinge, and multi-window multitasking.', 164999.00, 15, 140249.00, 8, 3, '12 GB', '512 GB', 'Snapdragon 8 Gen 2 for Galaxy', '7.6" Dynamic AMOLED 2X 120Hz + 6.2" Cover', '50MP Dual Pixel OIS + 12MP Wide + 10MP 3x Telephoto', '4400 mAh (25W Fast Charge)', 'One UI 6.0 (Android 14)', 'Icy Blue', '1 Year Samsung Care Warranty', 'assets/images/products/z_fold5.svg', 'ACTIVE', 1, 0, 4.70, 19),
(13, 1, 3, 5, 'OnePlus Nord CE4 5G', 'CPH2613', 'All the phone you need featuring Snapdragon 7 Gen 3, 100W SUPERVOOC, and 5500mAh largest OnePlus battery.', 26999.00, 11, 24029.00, 60, 12, '8 GB', '128 GB', 'Snapdragon 7 Gen 3', '6.7" FHD+ AMOLED 120Hz', '50MP Sony LYT-600 OIS + 8MP Ultra-Wide', '5500 mAh (100W SUPERVOOC)', 'OxygenOS 14', 'Celadon Marble', '1 Year Brand Warranty', 'assets/images/products/nord_ce4.svg', 'ACTIVE', 0, 1, 4.55, 31),
(14, 1, 7, 5, 'Realme Narzo 70 Pro 5G', 'RMX3868', 'First in segment Air Gestures, Glass Green finish, and Flagship Sony IMX890 OIS camera sensor.', 21999.00, 14, 18919.00, 40, 10, '8 GB', '128 GB', 'MediaTek Dimensity 7050 5G', '6.67" FHD+ AMOLED 120Hz Rainwater Smart Touch', '50MP Sony IMX890 OIS + 8MP Wide + 2MP Macro', '5000 mAh (67W SUPERVOOC)', 'realme UI 5.0 (Android 14)', 'Glass Green', '1 Year Brand Warranty', 'assets/images/products/narzo_70pro.svg', 'ACTIVE', 0, 0, 4.45, 27),
(15, 1, 1, 5, 'Samsung Galaxy A55 5G', 'SM-A556E', 'Awesome intelligence with metal frame, Gorilla Glass Victus+, Knox Vault security, and 4 OS upgrades.', 42999.00, 12, 37839.00, 3, 5, '8 GB', '128 GB', 'Exynos 1480 (4nm) with AMD Xclipse 530 GPU', '6.6" Super AMOLED 120Hz FHD+', '50MP OIS + 12MP Ultra-Wide + 5MP Macro', '5000 mAh (25W Charging)', 'One UI 6.1 (Android 14)', 'Awesome Iceblue', '1 Year Brand Warranty', 'assets/images/products/galaxy_a55.svg', 'ACTIVE', 0, 0, 4.40, 22),
(16, 1, 8, 5, 'Moto G84 5G', 'XT2347-2', 'Vibrant Pantone Viva Magenta vegan leather, 120Hz 10-bit pOLED display, and 50MP OIS camera under 20k.', 19999.00, 15, 16999.00, 2, 5, '12 GB', '256 GB', 'Snapdragon 695 5G', '6.55" 10-bit FHD+ pOLED 120Hz', '50MP Ultra Pixel OIS + 8MP Macro/Depth', '5000 mAh (33W TurboPower)', 'My UX (Android 13/14 upgradeable)', 'Viva Magenta (Pantone Color)', '1 Year Brand Warranty', 'assets/images/products/moto_g84.svg', 'ACTIVE', 0, 0, 4.35, 18);

-- Seed Initial Inventory Transactions
INSERT INTO `inventory_transactions` (`product_id`, `transaction_type`, `quantity_change`, `quantity_after`, `reference_id`, `notes`, `created_by`) VALUES
(1, 'INITIAL', 20, 20, 'PO-2026-001', 'Initial warehouse stock from Samsung India', 1),
(2, 'INITIAL', 15, 15, 'PO-2026-002', 'Initial inventory allocation', 1),
(3, 'INITIAL', 30, 30, 'PO-2026-003', 'Launch inventory batch', 1),
(15, 'ADJUSTMENT', -2, 3, 'AUDIT-2026-01', 'Quality check warehouse allocation - Low Stock Alert Triggered', 1),
(16, 'ADJUSTMENT', -3, 2, 'AUDIT-2026-02', 'Display showcase units - Low Stock Alert Triggered', 1);

-- Sample Promotions
INSERT INTO `promotions` (`code`, `discount_percent`, `discount_amount`, `minimum_order`, `max_discount`, `valid_from`, `valid_to`, `is_active`) VALUES
('WELCOME10', 10, 0.00, 10000.00, 2500.00, '2026-01-01', '2026-12-31', 1),
('FESTIVE500', 0, 500.00, 15000.00, 500.00, '2026-01-01', '2026-12-31', 1),
('FLAGSHIP2000', 0, 2000.00, 50000.00, 2000.00, '2026-01-01', '2026-12-31', 1);

-- Initial Activity Log
INSERT INTO `activity_logs` (`user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`, `created_at`) VALUES
(1, 'SYSTEM_INIT', 'DATABASE', '1', 'Platform database initialized with PATANJALI admin account', '127.0.0.1', NOW());
