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

-- ------------------------------------------------------------
-- D2: PRODUCT / INVENTORY DB - Table: product_categories (Many-to-Many)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `product_categories`;
CREATE TABLE `product_categories` (
  `product_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  PRIMARY KEY (`product_id`, `category_id`),
  CONSTRAINT `fk_pc_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- D2: PRODUCT / INVENTORY DB - Table: product_variants
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `product_variants`;
CREATE TABLE `product_variants` (
  `variant_id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `sku` VARCHAR(100) NOT NULL UNIQUE,
  `ram` VARCHAR(50) NOT NULL,
  `storage` VARCHAR(50) NOT NULL,
  `color` VARCHAR(100) NOT NULL,
  `color_hex` VARCHAR(20) DEFAULT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `discount` INT DEFAULT 0,
  `final_price` DECIMAL(10,2) NOT NULL,
  `stock_quantity` INT NOT NULL DEFAULT 15,
  `is_default` TINYINT(1) DEFAULT 0,
  CONSTRAINT `fk_pv_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `product_images`;
CREATE TABLE `product_images` (
  `image_id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `color_name` VARCHAR(100) DEFAULT NULL,
  `color_hex` VARCHAR(20) DEFAULT NULL,
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

-- Authorized Brand Distribution Supplier Users
INSERT INTO `users` (`user_id`, `name`, `email`, `phone`, `password_hash`, `role`, `status`, `address`, `city`, `state`, `pincode`, `created_at`) VALUES
(2, 'Apple India Authorized', 'apple.dist@mobilekart.com', '9900000001', '$2y$10$6Dsi6c8ymJMJvmyQHOD9O./gge5ZKCS7feU8p80rQpfNgEJaQeKp.', 'SUPPLIER', 'ACTIVE', 'BKC Bandra East', 'Mumbai', 'Maharashtra', '400051', NOW()),
(3, 'Samsung Electronics India', 'samsung.dist@mobilekart.com', '9900000002', '$2y$10$6Dsi6c8ymJMJvmyQHOD9O./gge5ZKCS7feU8p80rQpfNgEJaQeKp.', 'SUPPLIER', 'ACTIVE', 'Two Horizon Center Golf Course Rd', 'Gurugram', 'Haryana', '122002', NOW()),
(4, 'Google Hardware India', 'google.dist@mobilekart.com', '9900000003', '$2y$10$6Dsi6c8ymJMJvmyQHOD9O./gge5ZKCS7feU8p80rQpfNgEJaQeKp.', 'SUPPLIER', 'ACTIVE', 'Old Madras Rd', 'Bengaluru', 'Karnataka', '560016', NOW()),
(5, 'OnePlus Direct India', 'oneplus.dist@mobilekart.com', '9900000004', '$2y$10$6Dsi6c8ymJMJvmyQHOD9O./gge5ZKCS7feU8p80rQpfNgEJaQeKp.', 'SUPPLIER', 'ACTIVE', 'Brigade Rd', 'Bengaluru', 'Karnataka', '560001', NOW()),
(6, 'Xiaomi Technology India', 'xiaomi.dist@mobilekart.com', '9900000005', '$2y$10$6Dsi6c8ymJMJvmyQHOD9O./gge5ZKCS7feU8p80rQpfNgEJaQeKp.', 'SUPPLIER', 'ACTIVE', 'Outer Ring Rd', 'Bengaluru', 'Karnataka', '560103', NOW()),
(7, 'Vivo Mobile India', 'vivo.dist@mobilekart.com', '9900000006', '$2y$10$6Dsi6c8ymJMJvmyQHOD9O./gge5ZKCS7feU8p80rQpfNgEJaQeKp.', 'SUPPLIER', 'ACTIVE', 'World Trade Center', 'Noida', 'Uttar Pradesh', '201301', NOW()),
(8, 'Realme Mobile India', 'realme.dist@mobilekart.com', '9900000007', '$2y$10$6Dsi6c8ymJMJvmyQHOD9O./gge5ZKCS7feU8p80rQpfNgEJaQeKp.', 'SUPPLIER', 'ACTIVE', 'Cyber City', 'Gurugram', 'Haryana', '122002', NOW()),
(9, 'Motorola Mobility India', 'motorola.dist@mobilekart.com', '9900000008', '$2y$10$6Dsi6c8ymJMJvmyQHOD9O./gge5ZKCS7feU8p80rQpfNgEJaQeKp.', 'SUPPLIER', 'ACTIVE', 'DLF Cyber City', 'Gurugram', 'Haryana', '122002', NOW()),
(10, 'Nothing Technology India', 'nothing.dist@mobilekart.com', '9900000009', '$2y$10$6Dsi6c8ymJMJvmyQHOD9O./gge5ZKCS7feU8p80rQpfNgEJaQeKp.', 'SUPPLIER', 'ACTIVE', 'Connaught Place', 'New Delhi', 'Delhi', '110001', NOW()),
(11, 'iQOO Performance India', 'iqoo.dist@mobilekart.com', '9900000010', '$2y$10$6Dsi6c8ymJMJvmyQHOD9O./gge5ZKCS7feU8p80rQpfNgEJaQeKp.', 'SUPPLIER', 'ACTIVE', 'Sector 126', 'Noida', 'Uttar Pradesh', '201304', NOW()),
(12, 'Redmi India Official', 'redmi.dist@mobilekart.com', '9900000011', '$2y$10$6Dsi6c8ymJMJvmyQHOD9O./gge5ZKCS7feU8p80rQpfNgEJaQeKp.', 'SUPPLIER', 'ACTIVE', 'Outer Ring Rd Devarabisanahalli', 'Bengaluru', 'Karnataka', '560103', NOW());


-- Suppliers table
INSERT INTO `suppliers` (`supplier_id`, `user_id`, `company_name`, `gst_number`, `contact_person`, `approval_status`, `rating`) VALUES
(1, 2, 'Apple India Authorized Distribution Ltd.', '07AAAAA0001A1Z1', 'Apple Supply Operations', 'APPROVED', 4.95),
(2, 3, 'Samsung Electronics India Official Distribution', '27AAAAA0002A1Z2', 'Samsung Logistics Network', 'APPROVED', 4.9),
(3, 4, 'Google Hardware Distribution India', '29AAAAA0003A1Z3', 'Google Devices Team', 'APPROVED', 4.88),
(4, 5, 'OnePlus Direct Distribution India', '33AAAAA0004A1Z4', 'OnePlus Supply Chain', 'APPROVED', 4.85),
(5, 6, 'Xiaomi Technology India Distribution', '29AAAAA0005A1Z5', 'Xiaomi Wholesale Operations', 'APPROVED', 4.82),
(6, 7, 'Vivo Mobile India Official Distribution', '09AAAAA0006A1Z6', 'Vivo Commercial Logistics', 'APPROVED', 4.8),
(7, 8, 'Realme Mobile Distribution India', '09AAAAA0007A1Z7', 'Realme Sales Hub', 'APPROVED', 4.78),
(8, 9, 'Motorola Mobility India Distribution', '27AAAAA0008A1Z8', 'Motorola Enterprise Logistics', 'APPROVED', 4.75),
(9, 10, 'Nothing Technology Authorized Distribution', '07AAAAA0009A1Z9', 'Nothing Devices India', 'APPROVED', 4.8),
(10, 11, 'iQOO Performance Smartphone Distribution India', '09AAAAA0010A1Z0', 'iQOO India Distribution', 'APPROVED', 4.85),
(11, 12, 'Redmi India Official Authorized Distribution', '29AAAAA0011A1Z1', 'Redmi Distribution Network', 'APPROVED', 4.8);

-- Categories
INSERT INTO `categories` (`category_id`, `name`, `slug`, `description`, `icon`) VALUES
(1, 'Flagship Mobiles', 'flagship-mobiles', 'Top-tier cutting edge flagships with latest silicon and premium chassis', 'fa-crown'),
(2, '5G Smartphones', '5g-smartphones', 'Next-generation high speed 5G enabled smartphones', 'fa-bolt'),
(3, 'Camera Centric', 'camera-centric', 'Smartphones with pro grade studio cameras, large sensors, and periscope zoom', 'fa-camera'),
(4, 'Gaming Phones', 'gaming-phones', 'High refresh rate displays, advanced cooling, and extreme gaming performance', 'fa-gamepad'),
(5, 'Budget & Value', 'budget-value', 'Feature-packed reliable smartphones offering unmatched value for money', 'fa-tags'),
(6, 'Foldable Phones', 'foldable-phones', 'Futuristic flexible display smartphones with innovative hinge engineering', 'fa-book-open');

-- Brands
INSERT INTO `brands` (`brand_id`, `name`, `slug`, `logo_icon`) VALUES
(1, 'Samsung', 'samsung', 'fa-mobile-screen'),
(2, 'Apple', 'apple', 'fa-apple'),
(3, 'OnePlus', 'oneplus', 'fa-mobile-screen'),
(4, 'Xiaomi', 'xiaomi', 'fa-mobile-screen'),
(5, 'Nothing', 'nothing', 'fa-mobile-screen'),
(6, 'Vivo', 'vivo', 'fa-mobile-screen'),
(7, 'Realme', 'realme', 'fa-mobile-screen'),
(8, 'Motorola', 'motorola', 'fa-mobile-screen'),
(9, 'Google', 'google', 'fa-google'),
(10, 'iQOO', 'iqoo', 'fa-mobile-screen'),
(11, 'Redmi', 'redmi', 'fa-mobile-screen');

-- Products (16 realistic models with full specs and Indian pricing in INR)
INSERT INTO `products` (`product_id`, `supplier_id`, `brand_id`, `category_id`, `product_name`, `model`, `description`, `price`, `discount`, `final_price`, `stock_quantity`, `minimum_stock`, `ram`, `storage`, `processor`, `display`, `camera`, `battery`, `operating_system`, `color`, `warranty`, `image`, `status`, `is_featured`, `is_trending`, `rating`, `reviews_count`) VALUES
(1, 1, 2, 1, 'Apple iPhone 18 Pro Max', 'A3290', 'Apple 2026 flagship with revolutionary TSMC 2nm A20 Pro Bionic silicon, Grade 5 Aerospace Titanium chassis, 48MP Fusion Triple Camera with 10x Tetraprism optical zoom, 6.9-inch 120Hz ProMotion Super Retina XDR OLED, and iOS 20.', 169900.00, 6, 159706.00, 35, 5, '16GB Unified', '512GB NVMe', 'Apple A20 Pro 2nm Hexa-Core', '6.9" ProMotion Super Retina XDR OLED (1-120Hz, 3000 nits)', '48MP Triple Fusion + 48MP Ultra-Wide + 48MP 10x Tetraprism Telephoto', '4850mAh Qi3 MagSafe 45W', 'iOS 20', 'Natural Titanium Glass', '1 Year Apple International Warranty', 'assets/images/products/iphone18_promax_natural.png', 'ACTIVE', 1, 1, 0.00, 0),
(2, 1, 2, 6, 'Apple iPhone Duo (Foldable)', 'A3300-DUO', 'Revolutionary Apple foldable smartphone featuring dual seamless Ceramic Ultra-Thin Glass displays, Titanium Flex Hinge, A20 Pro chip, Spatial Video 8K recording, and dual MagSafe induction.', 199900.00, 5, 189905.00, 20, 3, '16GB Unified', '1TB NVMe', 'Apple A20 Pro 2nm Bionic', '8.1" Inner Foldable Ceramic OLED 120Hz + 6.3" Outer Cover Display', 'Dual 48MP Fusion Photonic Engine + 12MP TrueDepth FaceID', '5200mAh Dual-Cell MagSafe 50W', 'iOS 20 Fold Edition', 'Titanium Silver Flex', '1 Year AppleCare+ Included', 'assets/images/products/iphone_duo_open.jpg', 'ACTIVE', 1, 1, 0.00, 0),
(3, 1, 2, 1, 'Apple iPhone 18 Pro', 'A3288', 'Compact pro powerhouse with A20 Pro silicon, 6.3-inch borderless ProMotion OLED, 48MP triple camera system with 5x optical zoom, and iOS 20.', 139900.00, 7, 130107.00, 40, 5, '12GB Unified', '256GB NVMe', 'Apple A20 Pro 2nm', '6.3" Super Retina XDR OLED 120Hz', '48MP Main + 48MP Ultra-Wide + 48MP 5x Telephoto', '4200mAh MagSafe 35W', 'iOS 20', 'Desert Titanium', '1 Year Apple International Warranty', 'assets/images/products/iphone18_pro_desert.png', 'ACTIVE', 1, 0, 0.00, 0),
(4, 1, 2, 1, 'Apple iPhone 18', 'A3280', 'Dynamic Island, A20 Bionic chip, 48MP dual fusion camera with 2x sensor zoom, vibrant aerospace aluminum and color-infused back glass.', 84900.00, 10, 76410.00, 65, 10, '8GB Unified', '128GB NVMe', 'Apple A20 Bionic 3nm', '6.1" Super Retina XDR OLED 120Hz', '48MP Main Fusion + 12MP Ultra-Wide', '3800mAh Fast Charging 30W', 'iOS 20', 'Ultramarine Blue', '1 Year Apple Warranty', 'assets/images/products/iphone18_ultramarine.png', 'ACTIVE', 0, 1, 0.00, 0),
(5, 2, 1, 1, 'Samsung Galaxy S26 Ultra 5G', 'SM-S948B/DS', 'The ultimate Samsung AI powerhouse featuring Snapdragon 8 Gen 5 for Galaxy, 200MP Quad Zoom Camera with ISOCELL HP3+ sensor, embedded S-Pen, and Titanium Armor frame.', 139999.00, 9, 127399.00, 45, 8, '16GB LPDDR5X', '512GB UFS 4.1', 'Snapdragon 8 Gen 5 Ultra (3nm TSMC)', '6.8" Dynamic AMOLED 2X QHD+ (1-120Hz, Anti-Reflective 3200 nits)', '200MP Main + 50MP 5x Periscope + 50MP 3x Telephoto + 50MP Ultra-Wide', '5500mAh 65W Super Fast Charge 2.0', 'One UI 8 (Android 16)', 'Titanium Gray Armor', '1 Year Samsung India On-Site Warranty', 'assets/images/products/s26_ultra_gray.png', 'ACTIVE', 1, 1, 0.00, 0),
(6, 2, 1, 1, 'Samsung Galaxy S26+ 5G', 'SM-S946B/DS', 'Balanced large screen flagship with 6.7" QHD+ Dynamic AMOLED 2X, Snapdragon 8 Gen 5, Armor Aluminum frame, and ProVisual AI engine.', 99999.00, 10, 89999.00, 35, 6, '12GB LPDDR5X', '256GB UFS 4.1', 'Snapdragon 8 Gen 5 for Galaxy', '6.7" Dynamic AMOLED 2X 120Hz', '50MP Dual-Pixel OIS + 12MP Ultra-Wide + 10MP 3x Telephoto', '4900mAh 45W Fast Charging', 'One UI 8 (Android 16)', 'Onyx Black', '1 Year Samsung India Warranty', 'assets/images/products/s26_plus_black.jpg', 'ACTIVE', 0, 1, 0.00, 0),
(7, 2, 1, 1, 'Samsung Galaxy S26 5G', 'SM-S941B/DS', 'Compact premium flagship with 6.2-inch 120Hz Dynamic AMOLED 2X, flagship Snapdragon 8 Gen 5, and next-gen Galaxy AI productivity.', 79999.00, 12, 70399.00, 50, 8, '12GB LPDDR5X', '256GB UFS 4.1', 'Snapdragon 8 Gen 5 for Galaxy', '6.2" Dynamic AMOLED 2X FHD+ 120Hz', '50MP Main OIS + 12MP Ultra-Wide + 10MP 3x Telephoto', '4000mAh 25W Fast Charging', 'One UI 8 (Android 16)', 'Amber Yellow', '1 Year Samsung India Warranty', 'assets/images/products/s26_base_amber.jpg', 'ACTIVE', 0, 0, 0.00, 0),
(8, 2, 1, 6, 'Samsung Galaxy Z Fold 8 5G', 'SM-F966B/DS', 'Ultra-refined foldable tablet-phone with zero-gap titanium hinge, IP48 water/dust resistance, 7.6-inch Dynamic AMOLED 2X main display with S-Pen support, and Snapdragon 8 Gen 5.', 174999.00, 8, 160999.00, 22, 4, '16GB LPDDR5X', '512GB UFS 4.1', 'Snapdragon 8 Gen 5 Ultra 3nm', '7.6" Inner Dynamic AMOLED 2X (120Hz) + 6.3" Cover AMOLED (120Hz)', '50MP Dual Pixel OIS + 12MP Ultra-Wide + 10MP 3x Periscope', '4600mAh 45W Dual Cell Fast Charging', 'One UI 8 Fold Edition (Android 16)', 'Phantom Silver Flex', '1 Year Samsung Premier Care + 1 Screen Replacement', 'assets/images/products/z_fold8_silver.jpg', 'ACTIVE', 1, 1, 0.00, 0),
(9, 3, 9, 3, 'Google Pixel 11 Pro 5G', 'GC-P11P', 'The standard of computational photography with Google Tensor G6 custom TPU, 50MP Sony LYT-900 1-inch main sensor, Gemini 2.0 on-device multimodal AI, and 7 years of Android OS updates.', 112999.00, 15, 96049.00, 30, 5, '16GB LPDDR5X', '256GB UFS 4.1', 'Google Tensor G6 2nm with Titan M3 Security', '6.7" Super Actua OLED (1-120Hz, 3100 nits peak)', '50MP 1-inch Octa-PD + 48MP Quad PD Ultra-Wide + 48MP 5x Optical Periscope', '5100mAh Fast Charging 45W', 'Stock Android 16', 'Bay Coral Blue', '1 Year Google India Warranty', 'assets/images/products/pixel11_pro_obsidian.jpg', 'ACTIVE', 1, 1, 0.00, 0),
(10, 4, 3, 1, 'OnePlus 15 5G', 'CPH2621', 'Fast and Smooth 2026 flagship with Snapdragon 8 Gen 5, 5th Gen Hasselblad Mobile Camera system with HyperTone Engine, 100W SUPERVOOC flash charge, and OxygenOS 16.', 74999.00, 8, 68999.00, 45, 6, '16GB LPDDR5X', '512GB UFS 4.1', 'Snapdragon 8 Gen 5 Octa-Core 3nm', '6.82" 2K ProXDR LTPO AMOLED (1-120Hz, Dolby Vision)', '50MP Sony LYT-808 OIS + 64MP 3x Periscope Telephoto + 48MP Ultra-Wide', '5600mAh Silicon-Carbon Dual-Cell 100W SUPERVOOC', 'OxygenOS 16 (Android 16)', 'Emerald Flow Silk', '1 Year OnePlus Comprehensive Warranty', 'assets/images/products/oneplus15_silver.jpg', 'ACTIVE', 1, 1, 0.00, 0),
(11, 5, 4, 3, 'Xiaomi 16 Ultra 5G', '26048PN5CG', 'Leica Quad Camera imaging beast with 1-inch variable aperture main sensor, dual periscope zoom lenses, ceramic body armor, and Snapdragon 8 Gen 5.', 104999.00, 11, 93449.00, 25, 4, '16GB LPDDR5X', '512GB UFS 4.1', 'Snapdragon 8 Gen 5 Flagship', '6.73" WQHD+ AMOLED 120Hz LTPO (3000 nits)', 'Leica Quad: 50MP 1-inch Variable Aperture + 50MP 3.2x Tele + 50MP 5x Periscope + 50MP Ultra-Wide', '5300mAh 90W HyperCharge + 80W Wireless', 'Xiaomi HyperOS 3 (Android 16)', 'Ceramic White Armor', '1 Year Xiaomi India Priority Warranty', 'assets/images/products/xiaomi16_white.jpg', 'ACTIVE', 1, 0, 0.00, 0),
(12, 6, 6, 3, 'Vivo X200 Pro 5G', 'V2413A', 'Zeiss co-engineered portrait flagship with 200MP Zeiss APO Telephoto camera, MediaTek Dimensity 9500 3nm silicon, Vivo V4 imaging chip, and Armor Glass protection.', 94999.00, 10, 85499.00, 30, 5, '16GB LPDDR5X', '512GB UFS 4.1', 'MediaTek Dimensity 9500 3nm Flagship', '6.78" 1.5K 8T LTPO AMOLED 120Hz (3000 nits)', '50MP Sony LYT-818 1/1.28" + 200MP Zeiss APO Telephoto + 50MP Ultra-Wide', '6000mAh BlueVolt 90W FlashCharge', 'Funtouch OS 16 (Android 16)', 'Ocean Blue Sunburst', '1 Year Vivo India Warranty', 'assets/images/products/vivo_x200_blue.png', 'ACTIVE', 0, 1, 0.00, 0),
(13, 9, 5, 5, 'Nothing Phone (4) 5G', 'AIN088', 'Transparent aesthetic smartphone with next-generation interactive Glyph Matrix LED interface, Snapdragon 7+ Gen 4, clean bloatware-free Nothing OS 3.5, and dual 50MP Sony cameras.', 47999.00, 15, 40799.00, 60, 10, '12GB LPDDR5X', '256GB UFS 4.0', 'Snapdragon 7+ Gen 4 Octa-Core', '6.7" Flexible OLED 120Hz Adaptive (1600 nits)', '50MP Sony Main OIS + 50MP Ultra-Wide Macro', '5000mAh 65W Fast Charging + 15W Wireless', 'Nothing OS 3.5 (Android 16)', 'Transparent Dark Glyph', '1 Year Nothing Warranty', 'assets/images/products/nothing4_dark.png', 'ACTIVE', 0, 1, 0.00, 0),
(14, 10, 10, 4, 'iQOO 14 Pro 5G', 'I2401', 'Pure gaming and benchmark dominator with Snapdragon 8 Gen 5, Q3 dedicated supercomputing gaming chip, 2K 144Hz Samsung E8 AMOLED, and 120W FlashCharge.', 62999.00, 14, 54179.00, 40, 8, '16GB LPDDR5X', '512GB UFS 4.1', 'Snapdragon 8 Gen 5 + Q3 Supercomputing Gaming Chip', '6.78" 2K 144Hz E8 AMOLED with 3000Hz Instant Touch', '50MP VCS Bionic OIS + 50MP Ultra-Wide + 64MP 3x Periscope', '5400mAh 120W Ultra FlashCharge', 'Funtouch OS 16 (Android 16)', 'BMW M Legend White', '1 Year iQOO India Warranty', 'assets/images/products/iqoo15_legend.png', 'ACTIVE', 0, 1, 0.00, 0),
(15, 7, 7, 4, 'Realme GT 8 Pro 5G', 'RMX4001', 'Speed-inspired performance champion with Snapdragon 8 Gen 5, Iceberg 10,000mm2 VC vapor cooling chamber, 144Hz AMOLED, and vegan leather luxury styling.', 44999.00, 12, 39599.00, 55, 8, '16GB LPDDR5X', '512GB UFS 4.1', 'Snapdragon 8 Gen 5 Octa-Core', '6.78" 1.5K 144Hz 8T LTPO AMOLED (4000 nits)', '50MP Sony IMX890 OIS + 50MP Periscope Telephoto + 8MP Ultra-Wide', '5800mAh 120W Ultra-Charge', 'Realme UI 7 (Android 16)', 'Mars Blue Vegan', '1 Year Realme India Warranty', 'assets/images/products/realme_gt8_blue.jpg', 'ACTIVE', 0, 0, 0.00, 0),
(16, 8, 8, 1, 'Motorola Edge 70 Ultra 5G', 'XT2501-1', 'Pantone validated design with Snapdragon 8 Gen 5, 165Hz borderless quad-curved pOLED display, 50MP triple optical camera with AI Action Shot, and 125W TurboPower.', 69999.00, 16, 58799.00, 35, 5, '16GB LPDDR5X', '512GB UFS 4.1', 'Snapdragon 8 Gen 5 3nm TSMC', '6.7" 1.5K 165Hz Quad-Curved pOLED HDR10+', '50MP Main OIS + 50MP Ultra-Wide Macro + 64MP 3x Periscope', '5000mAh 125W TurboPower + 50W Wireless', 'Hello UI (Android 16)', 'Peach Fuzz Vegan Leather', '1 Year Motorola India Warranty', 'assets/images/products/moto_edge70_ultra.jpg', 'ACTIVE', 0, 0, 0.00, 0),
(17, 2, 1, 5, 'Samsung Galaxy A57 5G', 'SM-A576B/DS', 'Premium mid-range standard with metal chassis, IP67 water resistance, Exynos 1580 processor, 50MP OIS main camera with 4K selfie video, and 5 years of security updates.', 39999.00, 15, 33999.00, 80, 12, '8GB LPDDR5X', '256GB UFS 3.1', 'Samsung Exynos 1580 4nm Octa-Core', '6.6" Super AMOLED+ 120Hz FHD+ (1500 nits)', '50MP Main OIS + 12MP Ultra-Wide + 5MP Macro', '5000mAh 45W Fast Charging', 'One UI 8 (Android 16)', 'Awesome Lilac', '1 Year Samsung India Warranty', 'assets/images/products/galaxy_a57_lilac.jpg', 'ACTIVE', 0, 1, 0.00, 0),
(18, 11, 11, 5, 'Redmi Note 15 Pro+ 5G', '26090RN7CG', 'The undisputed value camera leader featuring 200MP OIS camera with 4x in-sensor lossless zoom, MediaTek Dimensity 8400-Ultra, curved 1.5K AMOLED, and 120W HyperCharge.', 32999.00, 18, 27059.00, 90, 15, '12GB LPDDR5X', '256GB UFS 3.1', 'MediaTek Dimensity 8400-Ultra 4nm', '6.67" 1.5K Curved AMOLED 120Hz HDR10+ (1800 nits)', '200MP Samsung ISOCELL HP3 OIS + 8MP Ultra-Wide + 2MP Macro', '5100mAh 120W HyperCharge', 'Xiaomi HyperOS 3 (Android 16)', 'Aurora Purple', '1 Year Redmi India Warranty', 'assets/images/products/redmi_note15_purple.jpg', 'ACTIVE', 0, 1, 0.00, 0);


-- Seed Product Categories Many-to-Many
INSERT INTO `product_categories` (`product_id`, `category_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(2, 6),
(2, 1),
(2, 2),
(3, 1),
(3, 2),
(3, 3),
(4, 1),
(4, 2),
(5, 1),
(5, 2),
(5, 3),
(6, 1),
(6, 2),
(7, 1),
(7, 2),
(8, 6),
(8, 1),
(8, 2),
(9, 3),
(9, 1),
(9, 2),
(10, 1),
(10, 2),
(10, 4),
(11, 3),
(11, 1),
(11, 2),
(12, 3),
(12, 1),
(12, 2),
(13, 5),
(13, 2),
(14, 4),
(14, 1),
(14, 2),
(15, 4),
(15, 1),
(15, 2),
(16, 1),
(16, 2),
(17, 5),
(17, 2),
(18, 5),
(18, 3),
(18, 2);

-- Seed Product Variants
INSERT INTO `product_variants` (`product_id`, `sku`, `ram`, `storage`, `color`, `color_hex`, `image_url`, `price`, `discount`, `final_price`, `stock_quantity`, `is_default`) VALUES
(1, 'IP18PM-512-NAT', '16GB Unified', '512GB NVMe', 'Natural Titanium Glass', '#9c968f', 'assets/images/products/iphone18_promax_natural.png', 169900.00, 6, 159706.00, 35, 1),
(1, 'IP18PM-256-DES', '16GB Unified', '256GB NVMe', 'Desert Titanium', '#c4a58b', 'assets/images/products/iphone18_promax_desert.png', 154900.00, 6, 145606.00, 25, 0),
(1, 'IP18PM-1TB-WHT', '16GB Unified', '1TB NVMe', 'White Titanium', '#e3e4e5', 'assets/images/products/iphone18_promax_white.png', 189900.00, 5, 180405.00, 15, 0),
(1, 'IP18PM-512-BLK', '16GB Unified', '512GB NVMe', 'Black Titanium', '#343538', 'assets/images/products/iphone18_promax_black.png', 169900.00, 6, 159706.00, 30, 0),
(2, 'IPDUO-1TB-SLV', '16GB Unified', '1TB NVMe', 'Titanium Silver Flex', '#c0c0c0', 'assets/images/products/iphone_duo_open.jpg', 199900.00, 5, 189905.00, 20, 1),
(2, 'IPDUO-512-BLK', '16GB Unified', '512GB NVMe', 'Space Black Duo', '#1c1c1e', 'assets/images/products/iphone_duo_black.jpg', 184900.00, 5, 175655.00, 15, 0),
(3, 'IP18P-256-DES', '12GB Unified', '256GB NVMe', 'Desert Titanium', '#c4a58b', 'assets/images/products/iphone18_pro_desert.png', 139900.00, 7, 130107.00, 40, 1),
(3, 'IP18P-512-NAT', '12GB Unified', '512GB NVMe', 'Natural Titanium', '#9c968f', 'assets/images/products/iphone18_pro_natural.png', 154900.00, 6, 145606.00, 25, 0),
(3, 'IP18P-256-BLK', '12GB Unified', '256GB NVMe', 'Black Titanium', '#343538', 'assets/images/products/iphone18_pro_black.png', 139900.00, 7, 130107.00, 30, 0),
(4, 'IP18-128-BLU', '8GB Unified', '128GB NVMe', 'Ultramarine Blue', '#39538c', 'assets/images/products/iphone18_ultramarine.png', 84900.00, 10, 76410.00, 65, 1),
(4, 'IP18-256-TEA', '8GB Unified', '256GB NVMe', 'Teal Green', '#619597', 'assets/images/products/iphone18_teal.png', 94900.00, 9, 86359.00, 45, 0),
(4, 'IP18-128-PNK', '8GB Unified', '128GB NVMe', 'Pink Blossom', '#d68d9b', 'assets/images/products/iphone18_pink.png', 84900.00, 10, 76410.00, 35, 0),
(4, 'IP18-256-BLK', '8GB Unified', '256GB NVMe', 'Midnight Black', '#2c2d30', 'assets/images/products/iphone18_black.png', 94900.00, 9, 86359.00, 40, 0),
(5, 'S26U-512-GRY', '16GB LPDDR5X', '512GB UFS 4.1', 'Titanium Gray Armor', '#73726e', 'assets/images/products/s26_ultra_gray.png', 139999.00, 9, 127399.00, 45, 1),
(5, 'S26U-256-BLK', '12GB LPDDR5X', '256GB UFS 4.1', 'Titanium Black', '#2b2b2b', 'assets/images/products/s26_ultra_black.jpg', 129999.00, 10, 116999.00, 35, 0),
(5, 'S26U-1TB-VIO', '16GB LPDDR5X', '1TB UFS 4.1', 'Titanium Violet', '#443a57', 'assets/images/products/s26_ultra_violet.jpg', 159999.00, 8, 147199.00, 20, 0),
(5, 'S26U-512-YEL', '16GB LPDDR5X', '512GB UFS 4.1', 'Titanium Yellow', '#e4d9b9', 'assets/images/products/s26_ultra_yellow.png', 139999.00, 9, 127399.00, 25, 0),
(6, 'S26P-256-BLK', '12GB LPDDR5X', '256GB UFS 4.1', 'Onyx Black', '#222222', 'assets/images/products/s26_plus_black.jpg', 99999.00, 10, 89999.00, 35, 1),
(6, 'S26P-512-COB', '12GB LPDDR5X', '512GB UFS 4.1', 'Cobalt Violet', '#433f5c', 'assets/images/products/s26_cobalt.jpg', 109999.00, 9, 100099.00, 25, 0),
(7, 'S26-256-AMB', '12GB LPDDR5X', '256GB UFS 4.1', 'Amber Yellow', '#f1d899', 'assets/images/products/s26_base_amber.jpg', 79999.00, 12, 70399.00, 50, 1),
(7, 'S26-128-COB', '8GB LPDDR5X', '128GB UFS 4.1', 'Cobalt Violet', '#433f5c', 'assets/images/products/s26_cobalt.jpg', 74999.00, 11, 66749.00, 30, 0),
(8, 'ZFD8-512-SLV', '16GB LPDDR5X', '512GB UFS 4.1', 'Phantom Silver Flex', '#d4d5d9', 'assets/images/products/z_fold8_silver.jpg', 174999.00, 8, 160999.00, 22, 1),
(8, 'ZFD8-1TB-BLK', '16GB LPDDR5X', '1TB UFS 4.1', 'Jet Black Flex', '#1a1a1a', 'assets/images/products/z_fold8_black.jpg', 194999.00, 7, 181349.00, 15, 0),
(9, 'PX11P-256-BAY', '16GB LPDDR5X', '256GB UFS 4.1', 'Bay Coral Blue', '#789dc7', 'assets/images/products/pixel11_pro_obsidian.jpg', 112999.00, 15, 96049.00, 30, 1),
(9, 'PX11P-512-OBS', '16GB LPDDR5X', '512GB UFS 4.1', 'Obsidian Black', '#2c2d30', 'assets/images/products/pixel11_pro_obsidian.jpg', 124999.00, 14, 107499.00, 20, 0),
(9, 'PX11P-256-POR', '16GB LPDDR5X', '256GB UFS 4.1', 'Porcelain White', '#eae6df', 'assets/images/products/pixel11_pro_front.jpg', 112999.00, 15, 96049.00, 25, 0),
(10, 'OP15-512-SLV', '16GB LPDDR5X', '512GB UFS 4.1', 'Emerald Flow Silk', '#c4d1cd', 'assets/images/products/oneplus15_silver.jpg', 74999.00, 8, 68999.00, 45, 1),
(10, 'OP15-256-BLK', '12GB LPDDR5X', '256GB UFS 4.1', 'Silky Black', '#1e1e1e', 'assets/images/products/oneplus15_black.jpg', 69999.00, 9, 63699.00, 35, 0),
(11, 'XM16U-512-WHT', '16GB LPDDR5X', '512GB UFS 4.1', 'Ceramic White Armor', '#f5f5f5', 'assets/images/products/xiaomi16_white.jpg', 104999.00, 11, 93449.00, 25, 1),
(11, 'XM16U-1TB-BLK', '16GB LPDDR5X', '1TB UFS 4.1', 'Titanium Black', '#222222', 'assets/images/products/xiaomi16_black.jpg', 119999.00, 10, 107999.00, 18, 0),
(12, 'VX200P-512-BLU', '16GB LPDDR5X', '512GB UFS 4.1', 'Ocean Blue Sunburst', '#306699', 'assets/images/products/vivo_x200_blue.png', 94999.00, 10, 85499.00, 30, 1),
(12, 'VX200P-256-PNK', '12GB LPDDR5X', '256GB UFS 4.1', 'Sunset Pink', '#e09fba', 'assets/images/products/vivo_x200_pink.png', 86999.00, 10, 78299.00, 20, 0),
(13, 'NOTH4-256-DRK', '12GB LPDDR5X', '256GB UFS 4.0', 'Transparent Dark Glyph', '#262626', 'assets/images/products/nothing4_dark.png', 47999.00, 15, 40799.00, 60, 1),
(13, 'NOTH4-512-WHT', '16GB LPDDR5X', '512GB UFS 4.0', 'White Glyph Edition', '#eaeaea', 'assets/images/products/nothing4_white.png', 54999.00, 13, 47849.00, 35, 0),
(14, 'IQ14P-512-LEG', '16GB LPDDR5X', '512GB UFS 4.1', 'BMW M Legend White', '#efefef', 'assets/images/products/iqoo15_legend.png', 62999.00, 14, 54179.00, 40, 1),
(14, 'IQ14P-256-BLK', '12GB LPDDR5X', '256GB UFS 4.1', 'Track Black', '#202020', 'assets/images/products/iqoo14_alpha.jpg', 56999.00, 14, 49019.00, 30, 0),
(15, 'RGT8P-512-BLU', '16GB LPDDR5X', '512GB UFS 4.1', 'Mars Blue Vegan', '#285880', 'assets/images/products/realme_gt8_blue.jpg', 44999.00, 12, 39599.00, 55, 1),
(15, 'RGT8P-256-WHT', '12GB LPDDR5X', '256GB UFS 4.1', 'White Speed Edition', '#f0f0f0', 'assets/images/products/realme_gt8_white.jpg', 39999.00, 13, 34799.00, 40, 0),
(16, 'ME70U-512-PCH', '16GB LPDDR5X', '512GB UFS 4.1', 'Peach Fuzz Vegan Leather', '#f6c4a6', 'assets/images/products/moto_edge70_ultra.jpg', 69999.00, 16, 58799.00, 35, 1),
(16, 'ME70U-256-BLK', '12GB LPDDR5X', '256GB UFS 4.1', 'Cosmic Black', '#202022', 'assets/images/products/moto_edge70_front.jpg', 62999.00, 15, 53549.00, 25, 0),
(17, 'A57-256-LIL', '8GB LPDDR5X', '256GB UFS 3.1', 'Awesome Lilac', '#c8b9db', 'assets/images/products/galaxy_a57_lilac.jpg', 39999.00, 15, 33999.00, 80, 1),
(17, 'A57-128-BLU', '8GB LPDDR5X', '128GB UFS 3.1', 'Awesome Ice Blue', '#adc5db', 'assets/images/products/galaxy_a57_blue.jpg', 34999.00, 14, 30099.00, 50, 0),
(18, 'RN15P-256-PUR', '12GB LPDDR5X', '256GB UFS 3.1', 'Aurora Purple', '#786088', 'assets/images/products/redmi_note15_purple.jpg', 32999.00, 18, 27059.00, 90, 1),
(18, 'RN15P-128-BLK', '8GB LPDDR5X', '128GB UFS 3.1', 'Midnight Black', '#1c1c1c', 'assets/images/products/redmi_note15_black.jpg', 28999.00, 17, 24069.00, 60, 0);

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

-- Seed Product Images & Color Variants
LOCK TABLES `product_images` WRITE;
INSERT INTO `product_images` (`product_id`, `image_url`, `color_name`, `color_hex`, `is_primary`, `sort_order`) VALUES
(1, 'assets/images/products/iphone18_promax_natural.png', 'Natural Titanium Glass', '#9c968f', 1, 1),
(1, 'assets/images/products/iphone18_promax_desert.png', 'Desert Titanium', '#c4a58b', 0, 2),
(1, 'assets/images/products/iphone18_promax_white.png', 'White Titanium', '#e3e4e5', 0, 3),
(1, 'assets/images/products/iphone18_promax_black.png', 'Black Titanium', '#343538', 0, 4),
(2, 'assets/images/products/iphone_duo_open.jpg', 'Titanium Silver Flex', '#c0c0c0', 1, 1),
(2, 'assets/images/products/iphone_duo_black.jpg', 'Space Black Duo', '#1c1c1e', 0, 2),
(3, 'assets/images/products/iphone18_pro_desert.png', 'Desert Titanium', '#c4a58b', 1, 1),
(3, 'assets/images/products/iphone18_pro_natural.png', 'Natural Titanium', '#9c968f', 0, 2),
(3, 'assets/images/products/iphone18_pro_white.png', 'White Titanium', '#e3e4e5', 0, 3),
(3, 'assets/images/products/iphone18_pro_black.png', 'Black Titanium', '#343538', 0, 4),
(4, 'assets/images/products/iphone18_ultramarine.png', 'Ultramarine Blue', '#39538c', 1, 1),
(4, 'assets/images/products/iphone18_teal.png', 'Teal Green', '#619597', 0, 2),
(4, 'assets/images/products/iphone18_pink.png', 'Pink Blossom', '#d68d9b', 0, 3),
(4, 'assets/images/products/iphone18_white.png', 'Starlight White', '#f4f4f4', 0, 4),
(4, 'assets/images/products/iphone18_black.png', 'Midnight Black', '#2c2d30', 0, 5),
(5, 'assets/images/products/s26_ultra_gray.png', 'Titanium Gray Armor', '#73726e', 1, 1),
(5, 'assets/images/products/s26_ultra_black.jpg', 'Titanium Black', '#2b2b2b', 0, 2),
(5, 'assets/images/products/s26_ultra_violet.jpg', 'Titanium Violet', '#443a57', 0, 3),
(5, 'assets/images/products/s26_ultra_yellow.png', 'Titanium Yellow', '#e4d9b9', 0, 4),
(6, 'assets/images/products/s26_plus_black.jpg', 'Onyx Black', '#222222', 1, 1),
(6, 'assets/images/products/s26_cobalt.jpg', 'Cobalt Violet', '#433f5c', 0, 2),
(7, 'assets/images/products/s26_base_amber.jpg', 'Amber Yellow', '#f1d899', 1, 1),
(7, 'assets/images/products/s26_cobalt.jpg', 'Cobalt Violet', '#433f5c', 0, 2),
(8, 'assets/images/products/z_fold8_silver.jpg', 'Phantom Silver Flex', '#d4d5d9', 1, 1),
(8, 'assets/images/products/z_fold8_black.jpg', 'Jet Black Flex', '#1a1a1a', 0, 2),
(9, 'assets/images/products/pixel11_pro_obsidian.jpg', 'Bay Coral Blue', '#789dc7', 1, 1),
(9, 'assets/images/products/pixel11_pro_obsidian.jpg', 'Obsidian Black', '#2c2d30', 0, 2),
(9, 'assets/images/products/pixel11_pro_front.jpg', 'Porcelain White', '#eae6df', 0, 3),
(9, 'assets/images/products/pixel11_pro_mint.svg', 'Mint Green', '#c4e3d4', 0, 4),
(10, 'assets/images/products/oneplus15_silver.jpg', 'Emerald Flow Silk', '#c4d1cd', 1, 1),
(10, 'assets/images/products/oneplus15_black.jpg', 'Silky Black', '#1e1e1e', 0, 2),
(11, 'assets/images/products/xiaomi16_white.jpg', 'Ceramic White Armor', '#f5f5f5', 1, 1),
(11, 'assets/images/products/xiaomi16_black.jpg', 'Titanium Black', '#222222', 0, 2),
(12, 'assets/images/products/vivo_x200_blue.png', 'Ocean Blue Sunburst', '#306699', 1, 1),
(12, 'assets/images/products/vivo_x200_pink.png', 'Sunset Pink', '#e09fba', 0, 2),
(13, 'assets/images/products/nothing4_dark.png', 'Transparent Dark Glyph', '#262626', 1, 1),
(13, 'assets/images/products/nothing4_white.png', 'White Glyph Edition', '#eaeaea', 0, 2),
(14, 'assets/images/products/iqoo15_legend.png', 'BMW M Legend White', '#efefef', 1, 1),
(14, 'assets/images/products/iqoo14_alpha.jpg', 'Track Black', '#202020', 0, 2),
(15, 'assets/images/products/realme_gt8_blue.jpg', 'Mars Blue Vegan', '#285880', 1, 1),
(15, 'assets/images/products/realme_gt8_white.jpg', 'White Speed Edition', '#f0f0f0', 0, 2),
(16, 'assets/images/products/moto_edge70_ultra.jpg', 'Peach Fuzz Vegan Leather', '#f6c4a6', 1, 1),
(16, 'assets/images/products/moto_edge70_front.jpg', 'Cosmic Black', '#202022', 0, 2),
(17, 'assets/images/products/galaxy_a57_lilac.jpg', 'Awesome Lilac', '#c8b9db', 1, 1),
(17, 'assets/images/products/galaxy_a57_blue.jpg', 'Awesome Ice Blue', '#adc5db', 0, 2),
(18, 'assets/images/products/redmi_note15_purple.jpg', 'Aurora Purple', '#786088', 1, 1),
(18, 'assets/images/products/redmi_note15_black.jpg', 'Midnight Black', '#1c1c1c', 0, 2);
UNLOCK TABLES;
