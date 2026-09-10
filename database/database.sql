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
INSERT INTO `products` (`product_id`, `supplier_id`, `brand_id`, `category_id`, `product_name`, `model`, `description`, `price`, `discount`, `final_price`, `stock_quantity`, `minimum_stock`, `ram`, `storage`, `processor`, `display`, `camera`, `battery`, `operating_system`, `color`, `warranty`, `image`, `status`, `is_featured`, `is_trending`, `rating`) VALUES
(1, 1, 1, 1, 'Apple iPhone 18 Pro Max', 'A3290', 'Apple 2026 flagship with revolutionary TSMC 2nm A20 Pro Bionic silicon, Grade 5 Aerospace Titanium chassis, 48MP Fusion Triple Camera with 10x Tetraprism optical zoom, 6.9-inch 120Hz ProMotion Super Retina XDR OLED, and iOS 20.', 169900.00, 6, 159700.00, 35, 5, '16GB Unified', '512GB NVMe', 'Apple A20 Pro 2nm Hexa-Core', '6.9" ProMotion Super Retina XDR OLED (1-120Hz, 3000 nits)', '48MP Triple Fusion + 48MP Ultra-Wide + 48MP 10x Tetraprism Telephoto', '4850mAh Qi3 MagSafe 45W', 'iOS 20', 'Natural Titanium Glass', '1 Year Apple International Warranty', 'assets/images/products/iphone18_promax.svg', 'ACTIVE', 1, 1, 4.95),
(2, 1, 1, 4, 'Apple iPhone Duo (Foldable)', 'A3300-DUO', 'Revolutionary Apple foldable smartphone featuring dual seamless Ceramic Ultra-Thin Glass displays, Titanium Flex Hinge, A20 Pro chip, Spatial Video 8K recording, and dual MagSafe induction.', 199900.00, 5, 189900.00, 20, 3, '16GB Unified', '1TB NVMe', 'Apple A20 Pro 2nm Bionic', '8.1" Inner Foldable Ceramic OLED 120Hz + 6.3" Outer Cover Display', 'Dual 48MP Fusion Photonic Engine + 12MP TrueDepth FaceID', '5200mAh Dual-Cell MagSafe 50W', 'iOS 20 Fold Edition', 'Space Black Duo', '1 Year AppleCare+ Included', 'assets/images/products/iphone_duo.svg', 'ACTIVE', 1, 1, 4.98),
(3, 1, 1, 1, 'Apple iPhone 18 Pro', 'A3288', 'Compact pro powerhouse with A20 Pro silicon, 6.3-inch borderless ProMotion OLED, 48MP triple camera system with 5x optical zoom, and iOS 20.', 139900.00, 7, 130100.00, 40, 5, '12GB Unified', '256GB NVMe', 'Apple A20 Pro 2nm', '6.3" Super Retina XDR OLED 120Hz', '48MP Main + 48MP Ultra-Wide + 48MP 5x Telephoto', '4200mAh MagSafe 35W', 'iOS 20', 'Desert Titanium', '1 Year Manufacturer Warranty', 'assets/images/products/iphone18_pro.svg', 'ACTIVE', 0, 1, 4.88),
(4, 1, 1, 2, 'Apple iPhone 18', 'A3285', 'Modern standard iPhone featuring Dynamic Island 2, 120Hz ProMotion display, Apple A20 chip, Camera Control haptic button, and dual 48MP fusion lenses.', 84900.00, 10, 76410.00, 55, 8, '8GB Unified', '256GB NVMe', 'Apple A20 Bionic', '6.1" OLED 120Hz ProMotion', '48MP Fusion Main + 48MP Ultra-Wide', '3800mAh Fast Charge', 'iOS 20', 'Ultramarine Blue', '1 Year Manufacturer Warranty', 'assets/images/products/iphone18.svg', 'ACTIVE', 0, 1, 4.75),
(5, 1, 2, 1, 'Samsung Galaxy S26 Ultra 5G', 'SM-S948B/DS', 'Samsung ultimate 2026 flagship with Qualcomm Snapdragon 8 Gen 5 for Galaxy (3nm), 200MP Quad ISOCELL camera with 100x AI Space Zoom, integrated S-Pen, Anti-Reflective Armor Glass, and Galaxy AI 3.0.', 139999.00, 9, 127399.00, 50, 8, '16GB LPDDR5X', '512GB UFS 4.1', 'Qualcomm Snapdragon 8 Gen 5 (3nm)', '6.8" QHD+ Dynamic LTPO AMOLED 2X (1-144Hz, 3200 nits)', '200MP OIS + 50MP Periscope 10x + 50MP Tele 3x + 50MP Ultra-Wide', '5500mAh 65W Fast Charging', 'One UI 8.5 (Android 17)', 'Titanium Gray Armor', '1 Year Manufacturer Warranty', 'assets/images/products/s26_ultra.svg', 'ACTIVE', 1, 1, 4.92),
(6, 1, 2, 1, 'Samsung Galaxy S26+ 5G', 'SM-S946B/DS', 'High-performance flagship with 6.7-inch QHD+ Dynamic AMOLED 2X, Snapdragon 8 Gen 5, Armor Aluminum 3.0, 50MP triple camera, and 4900mAh battery.', 99999.00, 10, 89999.00, 45, 5, '12GB LPDDR5X', '256GB UFS 4.0', 'Qualcomm Snapdragon 8 Gen 5', '6.7" Dynamic AMOLED 2X 120Hz', '50MP OIS Dual Pixel + 50MP Ultra-Wide + 12MP Telephoto 3x', '4900mAh 45W Fast Charging', 'One UI 8.5 (Android 17)', 'Onyx Black', '1 Year Manufacturer Warranty', 'assets/images/products/s26_plus.svg', 'ACTIVE', 0, 0, 4.7),
(7, 1, 2, 1, 'Samsung Galaxy S26 5G', 'SM-S941B/DS', 'Ergonomic compact flagship phone with Snapdragon 8 Gen 5, 6.2-inch flat Dynamic AMOLED 2X display, IP68 water resistance, and full suite of Galaxy AI features.', 79999.00, 12, 70399.00, 60, 10, '12GB LPDDR5X', '256GB UFS 4.0', 'Qualcomm Snapdragon 8 Gen 5', '6.2" Dynamic AMOLED 2X 120Hz', '50MP Main OIS + 12MP Ultra-Wide + 10MP Telephoto 3x', '4200mAh 30W Fast Charging', 'One UI 8.5 (Android 17)', 'Cobalt Violet', '1 Year Manufacturer Warranty', 'assets/images/products/s26.svg', 'ACTIVE', 0, 1, 4.68),
(8, 1, 2, 4, 'Samsung Galaxy Z Fold 8 5G', 'SM-F966B/DS', 'Ultra-slim foldable phone with 7.8-inch QXGA+ Infinity Flex screen, zero-gap Titanium Armor hinge, Under-Display camera, and enhanced S-Pen multitasking.', 174999.00, 8, 160999.00, 25, 4, '16GB LPDDR5X', '512GB UFS 4.1', 'Qualcomm Snapdragon 8 Gen 5', '7.8" Foldable Dynamic AMOLED 2X 120Hz + 6.4" Outer Cover', '50MP OIS + 12MP Telephoto 3x + 12MP Ultra-Wide', '5000mAh 65W Fast Charging', 'One UI 8.5 on Android 17', 'Phantom Silver Flex', '1 Year Manufacturer Warranty', 'assets/images/products/z_fold8.svg', 'ACTIVE', 1, 0, 4.9),
(9, 1, 3, 6, 'Google Pixel 11 Pro 5G', 'G8V4C', 'Google AI photography powerhouse powered by Google Tensor G6 Titan M3 AI chip, 50MP Quad-PD camera with 30x Super Res Zoom, Pro Controls, and 7 years of OS updates.', 112999.00, 15, 96049.00, 35, 5, '16GB LPDDR5X', '256GB UFS 4.0', 'Google Tensor G6 AI Engine', '6.8" Super Actua OLED (1-144Hz, 3000 nits)', '50MP Main OIS + 48MP 5x Telephoto + 48MP Ultra-Wide Macro', '5300mAh 45W Fast Charging', 'Android 17 Stock Pixel Experience', 'Bay Coral & Obsidian', '1 Year Manufacturer Warranty', 'assets/images/products/pixel11_pro.svg', 'ACTIVE', 1, 1, 4.82),
(10, 1, 4, 1, 'OnePlus 15 5G', 'CPH2701', 'OnePlus flagship with 4th Gen Hasselblad Camera for Mobile, Snapdragon 8 Gen 5, 6.82-inch 2K 144Hz ProXDR screen, Dual Cryo-Velocity VC cooling, and massive 6000mAh Glacier battery.', 74999.00, 8, 68999.00, 65, 10, '16GB LPDDR5X', '512GB UFS 4.0', 'Qualcomm Snapdragon 8 Gen 5', '6.82" 2K 144Hz ProXDR LTPO AMOLED', '50MP Sony LYT-808 OIS + 64MP Periscope 3x + 48MP Ultra-Wide', '6000mAh 120W SuperVOOC', 'OxygenOS 17 (Android 17)', 'Emerald Flow Silk', '1 Year Manufacturer Warranty', 'assets/images/products/oneplus15.svg', 'ACTIVE', 1, 1, 4.85),
(11, 1, 5, 6, 'Xiaomi 16 Ultra 5G', '26030PN60G', 'Pinnacle of mobile optics co-engineered with Leica, featuring four 50MP sensors, 1-inch Sony LYT-1000 main sensor with stepless variable aperture, and dual periscope telephotos.', 104999.00, 11, 93449.00, 30, 5, '16GB LPDDR5X', '512GB UFS 4.1', 'Qualcomm Snapdragon 8 Gen 5', '6.73" WQHD+ 144Hz C9 AMOLED (3200 nits)', '50MP 1-inch Stepless OIS + 50MP 3.2x Tele + 50MP 5x Periscope + 50MP Ultra-Wide', '5800mAh 120W HyperCharge', 'Xiaomi HyperOS 3.0', 'Ceramic White Armor', '1 Year Manufacturer Warranty', 'assets/images/products/xiaomi16_ultra.svg', 'ACTIVE', 0, 1, 4.87),
(12, 1, 7, 6, 'Vivo X200 Pro 5G', 'V2501A', 'Professional camera flagship featuring ZEISS 200MP APO Telephoto camera, MediaTek Dimensity 9500 3nm platform, dedicated Vivo V4 imaging chip, and 6000mAh BlueVolt battery.', 94999.00, 10, 85499.00, 40, 6, '16GB LPDDR5X', '512GB UFS 4.0', 'MediaTek Dimensity 9500 (3nm) + V4 Chip', '6.78" 1.5K LTPO AMOLED 120Hz', '50MP Sony LYT-818 1/1.28" OIS + 200MP ZEISS APO Telephoto + 50MP Ultra-Wide', '6000mAh 100W FlashCharge', 'Funtouch OS 17 (Android 17)', 'Titanium Sunburst', '1 Year Manufacturer Warranty', 'assets/images/products/vivo_x200_pro.svg', 'ACTIVE', 0, 1, 4.89),
(13, 1, 9, 2, 'Nothing Phone (4) 5G', 'A075', 'Transparent iconic design with Matrix Glyph Interface v4, Snapdragon 8s Gen 5, dual 50MP flagship sensors, and clean bloatware-free Nothing OS 3.5.', 47999.00, 15, 40799.00, 55, 8, '12GB LPDDR5X', '256GB UFS 4.0', 'Qualcomm Snapdragon 8s Gen 5', '6.7" Flexible OLED 120Hz (2500 nits)', '50MP Sony IMX906 OIS + 50MP Samsung JN1 Ultra-Wide', '5200mAh 65W Fast Charging', 'Nothing OS 3.5 (Android 17)', 'Transparent Dark Glyph', '1 Year Manufacturer Warranty', 'assets/images/products/nothing4.svg', 'ACTIVE', 0, 1, 4.72),
(14, 1, 10, 5, 'iQOO 14 Pro 5G', 'I2501', 'Hardcore e-sports gaming phone with Snapdragon 8 Gen 5 and Supercomputing Q3 chip, 144Hz 2K Samsung E8 AMOLED, Monster Halo lighting, and 150W ultra-fast charging.', 62999.00, 14, 54179.00, 45, 8, '16GB LPDDR5X', '512GB UFS 4.1', 'Qualcomm Snapdragon 8 Gen 5 + Q3 Chip', '6.78" 2K 144Hz E8 AMOLED Display', '50MP VCS Bionic OIS + 50MP Periscope 3x + 50MP Ultra-Wide', '6000mAh 150W FlashCharge', 'OriginOS 17 (Android 17)', 'BMW M Legend White', '1 Year Manufacturer Warranty', 'assets/images/products/iqoo14_pro.svg', 'ACTIVE', 0, 0, 4.81),
(15, 1, 6, 5, 'Realme GT 8 Pro 5G', 'RMX4100', 'Performance flagship with Snapdragon 8 Gen 5, Eco² OLED display, 6500mAh Titan battery with 120W SuperVOOC charging, and Sony periscope telephoto lens.', 44999.00, 12, 39599.00, 70, 10, '16GB LPDDR5X', '512GB UFS 4.0', 'Qualcomm Snapdragon 8 Gen 5', '6.78" 1.5K 144Hz 8T LTPO Eco² OLED', '50MP Sony IMX906 OIS + 50MP Periscope 3x + 8MP Ultra-Wide', '6500mAh 120W SuperVOOC', 'Realme UI 7.0 (Android 17)', 'Mars Orange Vegan Leather', '1 Year Manufacturer Warranty', 'assets/images/products/realme_gt8_pro.svg', 'ACTIVE', 0, 1, 4.76),
(16, 1, 8, 1, 'Motorola Edge 70 Ultra 5G', 'XT2601-2', 'Luxury curved smartphone with Snapdragon 8 Gen 5, 165Hz pOLED display, Pantone-validated cameras with 3x periscope telephoto, real vegan leather back, and 125W TurboPower.', 69999.00, 16, 58799.00, 40, 6, '16GB LPDDR5X', '512GB UFS 4.0', 'Qualcomm Snapdragon 8 Gen 5', '6.7" Super HD 165Hz Curved pOLED', '50MP OIS + 64MP Periscope 3x + 50MP Ultra-Wide Macro', '5000mAh 125W TurboPower', 'Hello UI (Android 17)', 'Peach Fuzz Vegan Leather', '1 Year Manufacturer Warranty', 'assets/images/products/moto_edge70.svg', 'ACTIVE', 0, 0, 4.7),
(17, 1, 2, 2, 'Samsung Galaxy A57 5G', 'SM-A576B/DS', 'Best-selling premium mid-ranger with Exynos 1680 (4nm), 6.6-inch Super AMOLED 120Hz display with Gorilla Glass Armor, 50MP OIS camera, IP67 rating, and Knox Vault security.', 39999.00, 15, 33999.00, 85, 15, '8GB LPDDR5X', '256GB UFS 3.1', 'Samsung Exynos 1680 (4nm)', '6.6" FHD+ Super AMOLED 120Hz', '50MP Main OIS + 12MP Ultra-Wide + 5MP Macro', '5000mAh 45W Fast Charging', 'One UI 8.5 (Android 17)', 'Awesome Lilac', '1 Year Manufacturer Warranty', 'assets/images/products/galaxy_a57.svg', 'ACTIVE', 0, 1, 4.62),
(18, 1, 5, 3, 'Redmi Note 15 Pro+ 5G', '26021PN30I', 'Feature-packed budget champion with 200MP OIS Ultra-Clear camera, MediaTek Dimensity 7400-Ultra, IP68 water resistance, and 120W HyperCharge in a slim curved body.', 32999.00, 18, 27059.00, 90, 15, '12GB LPDDR5X', '256GB UFS 3.1', 'MediaTek Dimensity 7400-Ultra (4nm)', '6.67" 1.5K 120Hz Curved AMOLED', '200MP Samsung ISOCELL HP3 OIS + 8MP Ultra-Wide', '5500mAh 120W HyperCharge', 'Xiaomi HyperOS 3.0', 'Midnight Black', '1 Year Manufacturer Warranty', 'assets/images/products/redmi_note15.svg', 'ACTIVE', 0, 0, 4.6);

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
