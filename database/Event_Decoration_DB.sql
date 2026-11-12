-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 12, 2026 at 12:52 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `event_decoration_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `venue_id` int(10) UNSIGNED DEFAULT NULL,
  `event_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `guest_count` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','quoted','confirmed','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `quoted_total` decimal(12,2) DEFAULT NULL,
  `payment_method` varchar(20) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `event_location` text DEFAULT NULL,
  `special_request` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `customer_id`, `package_id`, `venue_id`, `event_date`, `start_time`, `end_time`, `guest_count`, `status`, `quoted_total`, `payment_method`, `transaction_id`, `event_location`, `special_request`) VALUES
(3, 1, 1, NULL, '2026-10-21', '10:00:00', '18:00:00', 200, 'confirmed', 15000.00, 'bkash', '34554', 'Sukrabad, Dhaka-1207', 'Please finish the decoration in time.'),
(4, 2, 3, NULL, '2026-11-30', '10:00:00', '18:00:00', 50, 'completed', 8000.00, 'bkash', '12560', 'Sukrabad, Dhaka-1207', '4 pound chocolate cake'),
(5, 3, 4, NULL, '2026-08-18', '10:00:00', '18:00:00', 150, 'completed', 12000.00, 'bkash', '25890', 'Gazipur', 'Use marigold flower.'),
(6, 4, 5, NULL, '2026-11-23', '10:00:00', '18:00:00', 50, 'in_progress', 10000.00, 'nagad', '15687', 'Mymenshing', ''),
(7, 2, 6, NULL, '2026-11-20', '10:00:00', '18:00:00', 100, 'confirmed', 20000.00, 'bkash', '58960', 'Dhaka', 'Use golden lights.');

-- --------------------------------------------------------

--
-- Table structure for table `booking_addons`
--

CREATE TABLE `booking_addons` (
  `booking_addon_id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_staff`
--

CREATE TABLE `booking_staff` (
  `booking_id` int(10) UNSIGNED NOT NULL,
  `staff_id` int(10) UNSIGNED NOT NULL,
  `assignment_role` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `address` text DEFAULT NULL,
  `preferences` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `user_id`, `address`, `preferences`) VALUES
(1, 5, NULL, NULL),
(2, 1, NULL, NULL),
(3, 6, NULL, NULL),
(4, 4, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `decoration_packages`
--

CREATE TABLE `decoration_packages` (
  `id` int(10) UNSIGNED NOT NULL,
  `package_name` varchar(150) NOT NULL,
  `event_type_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `decoration_packages`
--

INSERT INTO `decoration_packages` (`id`, `package_name`, `event_type_id`, `description`, `price`, `image`, `status`, `created_at`) VALUES
(1, 'Basic Wedding Package', 1, 'Simple and elegant wedding decoration', 25000.00, NULL, 'active', '2026-10-28 10:39:47'),
(2, 'Premium Wedding Package', 1, 'Premium wedding stage and venue decoration', 75000.00, NULL, 'active', '2026-10-28 10:39:47'),
(3, 'Birthday Celebration Package', 2, 'Colorful birthday party decoration', 15000.00, NULL, 'active', '2026-10-28 10:39:47'),
(4, 'Holud Special Package', 3, 'Traditional Gaye Holud decoration', 20000.00, NULL, 'active', '2026-10-28 10:39:47'),
(5, 'Akika Celebration Package', 4, 'Beautiful Akika event decoration', 12000.00, NULL, 'active', '2026-10-28 10:39:47'),
(6, 'Corporate Event Package', 5, 'Professional corporate event decoration', 20000.00, NULL, 'active', '2026-10-28 10:39:47');

-- --------------------------------------------------------

--
-- Table structure for table `event_tasks`
--

CREATE TABLE `event_tasks` (
  `task_id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `staff_id` int(10) UNSIGNED NOT NULL,
  `task_title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `progress_percent` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `event_types`
--

CREATE TABLE `event_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_types`
--

INSERT INTO `event_types` (`id`, `type_name`, `description`, `status`) VALUES
(1, 'Wedding', 'Wedding ceremony decoration', 'active'),
(2, 'Birthday', 'Birthday party decoration', 'active'),
(3, 'Holud', 'Gaye Holud ceremony decoration', 'active'),
(4, 'Akika', 'Akika celebration decoration', 'active'),
(5, 'Corporate Event', 'Corporate meeting and event decoration', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `inventory_id` int(10) UNSIGNED NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `unit` varchar(40) NOT NULL,
  `total_quantity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `available_quantity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `replacement_cost` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `invoice_id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `issued_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `package_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `base_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_items`
--

CREATE TABLE `package_items` (
  `package_item_id` int(10) UNSIGNED NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `inventory_id` int(10) UNSIGNED NOT NULL,
  `quantity_required` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` enum('cash','bkash','nagad') NOT NULL,
  `transaction_reference` varchar(191) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `amount`, `method`, `transaction_reference`, `status`, `paid_at`) VALUES
(1, 7, 20000.00, 'bkash', '58960', 'completed', '2026-11-12 12:03:14'),
(2, 3, 25000.00, 'bkash', '34554', 'completed', '2026-11-09 12:31:50'),
(3, 6, 12000.00, 'nagad', '15687', 'pending', '2026-11-10 12:34:11');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `specialization` varchar(150) DEFAULT NULL,
  `availability_status` enum('available','busy','unavailable') NOT NULL DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `role` enum('admin','customer','staff') NOT NULL DEFAULT 'customer',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `phone`, `role`, `status`) VALUES
(1, 'Mst Rabeya', 'rabeya@gmail.com', '$2y$10$/jWcE7IbrpZg4uBdtNprbu3MNxWxy7ATVzslsTwo.0SBIgEpM64EW', '01861093392', 'admin', 'active'),
(3, 'Admin', 'admin@gmail.com', '$2y$10$sMAch6EwAjgWhPRniWSmiuCiYR2eQtqYQcLywcqyHDRvlJfYAPcmG', '01236589428', 'admin', 'active'),
(4, 'Kazi Modina', 'modina@gmail.com', '$2y$10$GmjHFsVnGLqVuWOmBvE/b.ucvdNdSPdJbrdv2TDRpovHRkXmZi2UC', '01536589428', 'customer', 'active'),
(5, 'Rima Rani', 'rima@gmail.com', '$2y$10$0w6KiE.7c/89UY/MUi2hxeBWtxIMqFdKaWmtzeHB2.w3unWo4lTCa', '013256987452', 'customer', 'active'),
(6, 'Habiba Jannat', 'habiba@gmail.com', '$2y$10$cZixCU0Ih.yheikRsKDrMOh6.VjOKujCed9dPH2R2QRntpa.prOza', '0126549803', 'customer', 'active'),
(7, 'Mim Akter', 'mimak@gmail.com', '$2y$10$Abb6JCOqHyPO6/BkSCnN9.tvrnuMVTEMhbBKOH2m5qOhe6Q4ap.Rq', '0189562310', 'customer', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `venues`
--

CREATE TABLE `venues` (
  `venue_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `address` text DEFAULT NULL,
  `capacity` int(10) UNSIGNED DEFAULT NULL,
  `contact_phone` varchar(25) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `venues`
--

INSERT INTO `venues` (`venue_id`, `name`, `address`, `capacity`, `contact_phone`) VALUES
(1, 'Main Event Venue', 'Dhaka, Bangladesh', 1000, '01700000000');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `fk_bookings_customer` (`customer_id`),
  ADD KEY `fk_bookings_package` (`package_id`),
  ADD KEY `fk_bookings_venue` (`venue_id`);

--
-- Indexes for table `booking_addons`
--
ALTER TABLE `booking_addons`
  ADD PRIMARY KEY (`booking_addon_id`),
  ADD KEY `fk_addons_booking` (`booking_id`);

--
-- Indexes for table `booking_staff`
--
ALTER TABLE `booking_staff`
  ADD PRIMARY KEY (`booking_id`,`staff_id`),
  ADD KEY `fk_booking_staff_staff` (`staff_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `decoration_packages`
--
ALTER TABLE `decoration_packages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_type_id` (`event_type_id`);

--
-- Indexes for table `event_tasks`
--
ALTER TABLE `event_tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `fk_event_tasks_booking` (`booking_id`),
  ADD KEY `fk_event_tasks_staff` (`staff_id`);

--
-- Indexes for table `event_types`
--
ALTER TABLE `event_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`inventory_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`invoice_id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`package_id`),
  ADD KEY `fk_packages_category` (`category_id`);

--
-- Indexes for table `package_items`
--
ALTER TABLE `package_items`
  ADD PRIMARY KEY (`package_item_id`),
  ADD KEY `fk_package_items_package` (`package_id`),
  ADD KEY `fk_package_items_inventory` (`inventory_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `transaction_reference` (`transaction_reference`),
  ADD KEY `fk_payments_booking` (`booking_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`),
  ADD KEY `fk_reviews_customer` (`customer_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `venues`
--
ALTER TABLE `venues`
  ADD PRIMARY KEY (`venue_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `booking_addons`
--
ALTER TABLE `booking_addons`
  MODIFY `booking_addon_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `decoration_packages`
--
ALTER TABLE `decoration_packages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `event_tasks`
--
ALTER TABLE `event_tasks`
  MODIFY `task_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_types`
--
ALTER TABLE `event_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `inventory_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `invoice_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `package_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_items`
--
ALTER TABLE `package_items`
  MODIFY `package_item_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `venues`
--
ALTER TABLE `venues`
  MODIFY `venue_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_bookings_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bookings_decoration_package` FOREIGN KEY (`package_id`) REFERENCES `decoration_packages` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bookings_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`venue_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `booking_addons`
--
ALTER TABLE `booking_addons`
  ADD CONSTRAINT `fk_addons_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON UPDATE CASCADE;

--
-- Constraints for table `booking_staff`
--
ALTER TABLE `booking_staff`
  ADD CONSTRAINT `fk_booking_staff_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_booking_staff_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON UPDATE CASCADE;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `fk_customers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `event_tasks`
--
ALTER TABLE `event_tasks`
  ADD CONSTRAINT `fk_event_tasks_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_event_tasks_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_invoices_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON UPDATE CASCADE;

--
-- Constraints for table `packages`
--
ALTER TABLE `packages`
  ADD CONSTRAINT `fk_packages_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON UPDATE CASCADE;

--
-- Constraints for table `package_items`
--
ALTER TABLE `package_items`
  ADD CONSTRAINT `fk_package_items_inventory` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`inventory_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_package_items_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`) ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON UPDATE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reviews_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON UPDATE CASCADE;

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `fk_staff_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
