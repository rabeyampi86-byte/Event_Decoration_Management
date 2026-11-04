-- DecoEase | Event Decoration Management System
-- Source: Event_Decoration_Management.docx, pp. 6-8
-- MySQL 8.0.16+ recommended for CHECK constraints
-- Run on a new database; no DROP statements included.
CREATE DATABASE IF NOT EXISTS `Event_Decoration_DB` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `Event_Decoration_DB`;

-- 01_users
CREATE TABLE users (
 user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 full_name VARCHAR(150) NOT NULL,
 email VARCHAR(191) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 phone VARCHAR(25),
 role ENUM('admin','customer') NOT NULL DEFAULT 'customer',
 status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 02_customers
CREATE TABLE customers (
 customer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL UNIQUE,
 address TEXT,
 preferences TEXT,
 CONSTRAINT fk_customers_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 03_staff
CREATE TABLE staff (
 staff_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NULL UNIQUE,
 specialization VARCHAR(150),
 availability_status ENUM('available','busy','unavailable') NOT NULL DEFAULT 'available',
 CONSTRAINT fk_staff_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 04_categories
CREATE TABLE categories (
 category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(150) NOT NULL UNIQUE,
 description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 05_packages
CREATE TABLE packages (
 package_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 category_id INT UNSIGNED NOT NULL,
 title VARCHAR(200) NOT NULL,
 base_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
 description TEXT,
 is_active BOOLEAN NOT NULL DEFAULT TRUE,
 CONSTRAINT chk_packages_price CHECK (base_price >= 0),
 CONSTRAINT fk_packages_category FOREIGN KEY (category_id) REFERENCES categories(category_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 06_inventory
CREATE TABLE inventory (
 inventory_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 item_name VARCHAR(200) NOT NULL,
 unit VARCHAR(40) NOT NULL,
 total_quantity INT UNSIGNED NOT NULL DEFAULT 0,
 available_quantity INT UNSIGNED NOT NULL DEFAULT 0,
 replacement_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
 CONSTRAINT chk_inventory_available CHECK (available_quantity <= total_quantity),
 CONSTRAINT chk_inventory_cost CHECK (replacement_cost >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 07_package_items
CREATE TABLE package_items (
 package_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 package_id INT UNSIGNED NOT NULL,
 inventory_id INT UNSIGNED NOT NULL,
 quantity_required INT UNSIGNED NOT NULL,
 CONSTRAINT chk_package_items_quantity CHECK (quantity_required > 0),
 CONSTRAINT fk_package_items_package FOREIGN KEY (package_id) REFERENCES packages(package_id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT fk_package_items_inventory FOREIGN KEY (inventory_id) REFERENCES inventory(inventory_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 08_venues
CREATE TABLE venues (
 venue_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(200) NOT NULL,
 address TEXT,
 capacity INT UNSIGNED,
 contact_phone VARCHAR(25)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 09_bookings
CREATE TABLE bookings (
 booking_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 customer_id INT UNSIGNED NOT NULL,
 package_id INT UNSIGNED NOT NULL,
 venue_id INT UNSIGNED NULL,
 event_date DATE NOT NULL,
 start_time TIME NOT NULL,
 end_time TIME NOT NULL,
 guest_count INT UNSIGNED NOT NULL,
 status ENUM('pending','quoted','confirmed','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
 quoted_total DECIMAL(12,2) NULL,
 CONSTRAINT chk_bookings_guest_count CHECK (guest_count > 0),
 CONSTRAINT chk_bookings_quoted_total CHECK (quoted_total IS NULL OR quoted_total >= 0),
 CONSTRAINT fk_bookings_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT fk_bookings_package FOREIGN KEY (package_id) REFERENCES packages(package_id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT fk_bookings_venue FOREIGN KEY (venue_id) REFERENCES venues(venue_id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10_booking_addons
CREATE TABLE booking_addons (
 booking_addon_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 booking_id INT UNSIGNED NOT NULL,
 title VARCHAR(200) NOT NULL,
 quantity INT UNSIGNED NOT NULL DEFAULT 1,
 unit_price DECIMAL(12,2) NOT NULL,
 CONSTRAINT chk_addons_quantity CHECK (quantity > 0),
 CONSTRAINT chk_addons_price CHECK (unit_price >= 0),
 CONSTRAINT fk_addons_booking FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11_payments
CREATE TABLE payments (
 payment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 booking_id INT UNSIGNED NOT NULL,
 amount DECIMAL(12,2) NOT NULL,
 method ENUM('cash','bkash','nagad') NOT NULL,
 transaction_reference VARCHAR(191) NULL UNIQUE,
 status ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
 paid_at DATETIME NULL,
 CONSTRAINT chk_payments_amount CHECK (amount > 0),
 CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12_invoices
CREATE TABLE invoices (
 invoice_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 booking_id INT UNSIGNED NOT NULL UNIQUE,
 subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
 discount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
 tax DECIMAL(12,2) NOT NULL DEFAULT 0.00,
 total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
 issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT chk_invoices_amounts CHECK (subtotal >= 0 AND discount >= 0 AND tax >= 0 AND total >= 0),
 CONSTRAINT fk_invoices_booking FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13_booking_staff
CREATE TABLE booking_staff (
 booking_id INT UNSIGNED NOT NULL,
 staff_id INT UNSIGNED NOT NULL,
 assignment_role VARCHAR(150) NOT NULL,
 PRIMARY KEY (booking_id, staff_id),
 CONSTRAINT fk_booking_staff_booking FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT fk_booking_staff_staff FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14_reviews
CREATE TABLE reviews (
 review_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 booking_id INT UNSIGNED NOT NULL UNIQUE,
 customer_id INT UNSIGNED NOT NULL,
 rating TINYINT UNSIGNED NOT NULL,
 comment TEXT,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
 CONSTRAINT fk_reviews_booking FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT fk_reviews_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
