-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 22, 2026 at 01:20 PM
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
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `banner_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` text DEFAULT NULL,
  `image_id` int(10) UNSIGNED DEFAULT NULL,
  `button_text` varchar(100) DEFAULT NULL,
  `button_link` varchar(500) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `package_id` int(10) UNSIGNED DEFAULT NULL,
  `venue_id` int(10) UNSIGNED DEFAULT NULL,
  `package_name_snapshot` varchar(200) NOT NULL,
  `package_price_snapshot` decimal(12,2) NOT NULL DEFAULT 0.00,
  `package_description_snapshot` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `guest_count` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','quoted','confirmed','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `quoted_total` decimal(12,2) DEFAULT NULL,
  `event_location` text DEFAULT NULL,
  `special_request` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `customer_id`, `package_id`, `venue_id`, `package_name_snapshot`, `package_price_snapshot`, `package_description_snapshot`, `event_date`, `start_time`, `end_time`, `guest_count`, `status`, `quoted_total`, `event_location`, `special_request`, `created_at`, `updated_at`) VALUES
(1, 4, 1, NULL, 'Premium Wedding Package', 35000.00, 'Premium wedding stage, floral decoration, lighting and entrance setup.', '2026-10-30', '10:00:00', '18:00:00', 250, 'completed', 35000.00, 'Dhanmondi, Dhaka', 'Completed sample wedding booking.', '2026-11-18 06:33:08', '2026-11-18 06:33:08'),
(2, 5, 2, NULL, 'Engagement Special Package', 18000.00, 'Stage decoration, flower arrangement and entrance decoration.', '2026-11-25', '09:00:00', '17:00:00', 120, 'confirmed', 18000.00, 'Uttara, Dhaka', 'Confirmed sample engagement booking.', '2026-11-18 06:33:08', '2026-11-18 06:33:08'),
(3, 6, 3, NULL, 'Birthday Celebration Package', 10000.00, 'Birthday backdrop, balloon decoration and lighting setup.', '2026-12-05', '11:00:00', '19:00:00', 80, 'pending', NULL, 'Mirpur, Dhaka', 'Pending sample birthday booking.', '2026-11-18 06:33:08', '2026-11-18 06:33:08'),
(4, 4, 1, NULL, 'Premium Wedding Package', 150000.00, 'Premium wedding stage, floral decoration, lighting and entrance setup.', '2026-11-25', '11:46:00', '13:48:00', 50, 'confirmed', 150000.00, 'Dhaka', '', '2026-11-22 05:48:03', '2026-11-22 05:48:45');

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
-- Table structure for table `booking_selections`
--

CREATE TABLE `booking_selections` (
  `selection_id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `selection_type` varchar(100) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `item_description` text DEFAULT NULL,
  `quantity` int(10) UNSIGNED DEFAULT 1,
  `unit_price` decimal(12,2) DEFAULT 0.00,
  `total_price` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_staff`
--

CREATE TABLE `booking_staff` (
  `booking_staff_id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `assignment_role` varchar(150) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `message_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(191) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read','replied') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`message_id`, `name`, `email`, `phone`, `subject`, `message`, `status`, `created_at`) VALUES
(1, 'Kazi Arifur Rahman', 'kaziarifurrahman31@gmail.com', '01765319630', 'Employees number', 'My name is Kazi Arifur Rahman. Please save my contact information.', 'read', '2026-11-20 02:11:42');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `address` text DEFAULT NULL,
  `preferences` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `user_id`, `address`, `preferences`, `created_at`) VALUES
(4, 7, 'Dhanmondi, Dhaka', 'Wedding decoration', '2026-11-18 06:33:08'),
(5, 8, 'Uttara, Dhaka', 'Engagement decoration', '2026-11-18 06:33:08'),
(6, 9, 'Mirpur, Dhaka', 'Birthday decoration', '2026-11-18 06:33:08');

-- --------------------------------------------------------

--
-- Table structure for table `decoration_areas`
--

CREATE TABLE `decoration_areas` (
  `area_id` int(10) UNSIGNED NOT NULL,
  `area_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `image_id` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_profiles`
--

CREATE TABLE `employee_profiles` (
  `employee_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `designation` varchar(120) DEFAULT NULL,
  `specialization` varchar(150) DEFAULT NULL,
  `profile_image` varchar(500) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `salary` decimal(12,2) DEFAULT NULL,
  `availability_status` enum('available','busy','unavailable') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_profiles`
--

INSERT INTO `employee_profiles` (`employee_id`, `user_id`, `designation`, `specialization`, `profile_image`, `bio`, `joining_date`, `salary`, `availability_status`, `created_at`) VALUES
(3, 10, 'Owner & Founder', 'Event Management & Business Administration', 'uploads/employees/e4aba99955528b34d5115773fdc59ed6.jpg', 'Mst. Rabeya is the founder and owner of DecorEase, responsible for business management, strategic planning, and ensuring quality event decoration services.', '2025-01-10', 50000.00, 'available', '2026-11-18 06:33:08'),
(4, 11, 'Event Coordinator', 'Event Management', 'uploads/employees/b85e06cc67609acf25575a371a2f6a77.jpg', 'Mehedi Hasan is an organized and dedicated Event Coordinator responsible for planning, scheduling, and coordinating event activities. Focuses on managing event timelines, communicating with clients and team members, and ensuring that every event runs smoothly and efficiently.', '2025-03-15', 20000.00, 'available', '2026-11-18 06:33:08'),
(5, 12, 'Event Manager', 'Event Planning & Managemen', 'uploads/employees/a009386caf3713233fa4a0e45447c4f4.jpg', 'Kazi Arifur Rahman is a member of the DecorEase team, contributing to event planning and management. He is involved in coordinating event-related activities and supporting the delivery of organized celebration experiences.', '2025-01-10', 30000.00, 'unavailable', '2026-11-18 09:16:28'),
(6, 13, 'Senior Decoration Designer', 'Wedding & Event Styling', 'uploads/employees/21aad1fe1e6fa70c3a1bb2bb41430843.jpg', 'Nusrat Jahan is a creative Senior Decoration Designer specializing in wedding and event styling. She responsible for developing decoration concepts, selecting color combinations, planning venue layouts, and creating elegant event environments that reflect each client\'s preferences.', '2025-01-10', 25000.00, 'available', '2026-11-22 03:53:10'),
(7, 14, 'Photography & Videography Specialist', 'Event Photography & Videography', 'uploads/employees/5c10625772e7c0495348eaa92e8201d2.jpg', 'Tanvir Ahmed is a dedicated Photography and Videography Specialist focused on capturing the special moments of every celebration. He is responsible for event coverage, creative photography, video recording, and delivering high-quality visual memories for clients.', NULL, 22000.00, 'available', '2026-11-22 03:56:13'),
(8, 15, 'Venue & Logistics Coordinator', 'Venue Setup & Event Logistics', 'uploads/employees/af425b554bf0c11461f0230e45c1db79.jpg', 'Saimul Islam is a detail-oriented Venue and Logistics Coordinator responsible for managing venue arrangements, coordinating decoration materials, and organizing event setup requirements. He works closely with the event and decoration teams to ensure smooth preparation and timely execution of every event.', '2025-11-05', 20000.00, 'available', '2026-11-22 03:59:46'),
(9, 16, 'Videographer', 'Corporate Event Videography', NULL, 'Experienced in recording corporate programs, birthday celebrations and social events.', '2025-08-06', 18000.00, 'available', '2026-11-22 06:41:24'),
(10, 17, 'Videographer', 'Wedding Cinematography', NULL, 'Professional event videographer specializing in wedding films and cinematic event coverage.', '2025-06-11', 20000.00, 'available', '2026-11-22 06:43:00'),
(11, 18, 'Photographer', 'Portrait and Event Photography', NULL, 'Skilled photographer with experience in birthday, corporate and traditional event photography.', '2026-02-12', 16000.00, 'available', '2026-11-22 06:44:04'),
(12, 19, 'Photographer', 'Wedding Photography', NULL, 'Experienced event photographer specializing in wedding, engagement and family event photography.', '2026-02-12', 16000.00, 'available', '2026-11-22 06:45:46'),
(13, 20, 'Video Editor', 'Wedding Film Editing', NULL, 'Creative video editor specializing in cinematic wedding highlights, event films and professional video editing.', '2026-02-12', 22000.00, 'available', '2026-11-22 06:47:50'),
(14, 21, 'Lead Decorator', 'Wedding Stage Decoration', NULL, 'Experienced lead decorator specializing in wedding stages, venue styling and event decoration management.', '2026-02-12', 25000.00, 'available', '2026-11-22 06:49:11'),
(15, 22, 'Lead Decorator', 'Corporate and Birthday Decoration', NULL, 'Skilled decorator experienced in planning and managing modern event decoration setups.', '2026-02-12', 22000.00, 'available', '2026-11-22 06:50:40'),
(16, 23, 'Flower Decorator', 'Floral Stage Decoration', NULL, 'Experienced in creating beautiful floral arrangements for wedding stages and special occasions.', '2026-02-11', 18000.00, 'available', '2026-11-22 06:52:18'),
(17, 24, 'Flower Decorator', 'Floral Stage Decoration', NULL, 'Skilled in floral table arrangements, entrance decoration and decorative flower styling.', '2026-02-11', 14500.00, 'available', '2026-11-22 06:53:28'),
(18, 25, 'Setup Assistant', '', NULL, '', '2026-02-11', 12500.00, 'available', '2026-11-22 07:00:21'),
(19, 26, 'Setup Assistant', '', NULL, '', NULL, 12000.00, 'available', '2026-11-22 07:01:02'),
(20, 27, 'Head Chef', 'Bengali and Wedding Cuisine', NULL, 'Experienced chef specializing in Bengali cuisine, wedding menus and large event catering.', '2026-07-09', 28000.00, 'available', '2026-11-22 07:02:24'),
(21, 28, 'Assistant Chef', 'Bengali Cuisine', NULL, 'Assists the head chef in food preparation, catering operations and kitchen management.', '2026-07-09', 16000.00, 'available', '2026-11-22 07:04:11'),
(22, 29, 'Assistant Chef', 'Catering Preparation', NULL, 'Assists the head chef in food preparation, catering operations and kitchen management.', '2026-07-09', 15000.00, 'available', '2026-11-22 07:05:08'),
(23, 30, 'Serving Team', '', NULL, '', '2026-02-05', 10000.00, 'available', '2026-11-22 07:06:10'),
(24, 31, 'Serving Team', '', NULL, '', '2025-08-13', 100000.00, 'available', '2026-11-22 07:06:58'),
(25, 32, 'Serving Team', '', NULL, '', '2026-04-15', 10000.00, 'available', '2026-11-22 07:07:42'),
(26, 33, 'Serving Team', '', NULL, '', NULL, 9000.00, 'available', '2026-11-22 07:08:32'),
(27, 34, 'Videographer', 'Corporate Event Videography', NULL, '', '2025-12-10', 18000.00, 'available', '2026-11-22 07:10:59'),
(28, 35, 'Head Chef', 'Bengali and Wedding Cuisine', NULL, '', '2025-12-10', 25000.00, 'available', '2026-11-22 07:12:00'),
(29, 36, 'Video Editor', '', NULL, '', '2025-02-05', 22000.00, 'available', '2026-11-22 07:18:22');

-- --------------------------------------------------------

--
-- Table structure for table `event_categories`
--

CREATE TABLE `event_categories` (
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `image_id` int(10) UNSIGNED DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_categories`
--

INSERT INTO `event_categories` (`category_id`, `name`, `slug`, `description`, `image_id`, `sort_order`, `status`, `created_at`) VALUES
(1, 'Wedding', 'wedding', 'Wedding ceremony decoration', NULL, 1, 'active', '2026-11-18 05:03:45'),
(2, 'Birthday', 'birthday', 'Birthday party decoration', NULL, 3, 'active', '2026-11-18 05:03:45'),
(3, 'Holud', 'holud', 'Gaye Holud ceremony decoration', NULL, 3, 'active', '2026-11-18 05:03:45'),
(4, 'Akika', 'akika', 'Akika celebration decoration', NULL, 4, 'active', '2026-11-18 05:03:45'),
(5, 'Corporate Event', 'corporate-event', 'Corporate event decoration', NULL, 5, 'active', '2026-11-18 05:03:45'),
(6, 'Engagement', 'engagement', 'Engagement ceremony decoration', NULL, 2, 'active', '2026-11-18 05:03:45'),
(7, 'Anniversary', 'anniversary', 'Anniversary celebration decoration', NULL, 7, 'active', '2026-11-18 05:03:45'),
(8, 'Custom Event', 'custom-event', 'Customized event decoration', NULL, 8, 'active', '2026-11-18 05:03:45');

-- --------------------------------------------------------

--
-- Table structure for table `event_tasks`
--

CREATE TABLE `event_tasks` (
  `task_id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `task_title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `progress_percent` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `facilities`
--

CREATE TABLE `facilities` (
  `facility_id` int(10) UNSIGNED NOT NULL,
  `facility_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `image_id` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `flowers`
--

CREATE TABLE `flowers` (
  `flower_id` int(10) UNSIGNED NOT NULL,
  `flower_name` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `image_id` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flowers`
--

INSERT INTO `flowers` (`flower_id`, `flower_name`, `description`, `image_id`, `is_active`) VALUES
(1, 'Rose', '', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `flower_designs`
--

CREATE TABLE `flower_designs` (
  `design_id` int(10) UNSIGNED NOT NULL,
  `design_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `image_id` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `food_menu_items`
--

CREATE TABLE `food_menu_items` (
  `food_id` int(10) UNSIGNED NOT NULL,
  `food_name` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `image_id` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gallery_albums`
--

CREATE TABLE `gallery_albums` (
  `album_id` int(10) UNSIGNED NOT NULL,
  `album_name` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `cover_image_id` int(10) UNSIGNED DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gallery_albums`
--

INSERT INTO `gallery_albums` (`album_id`, `album_name`, `description`, `category_id`, `cover_image_id`, `sort_order`, `status`, `created_at`) VALUES
(1, 'Wedding', NULL, 1, 5, 5, 'active', '2026-11-22 04:08:53'),
(2, 'Birthday', NULL, 2, 9, 5, 'active', '2026-11-22 04:47:49');

-- --------------------------------------------------------

--
-- Table structure for table `gallery_images`
--

CREATE TABLE `gallery_images` (
  `gallery_image_id` int(10) UNSIGNED NOT NULL,
  `album_id` int(10) UNSIGNED NOT NULL,
  `image_id` int(10) UNSIGNED NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_visible` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gallery_images`
--

INSERT INTO `gallery_images` (`gallery_image_id`, `album_id`, `image_id`, `caption`, `sort_order`, `is_visible`) VALUES
(1, 1, 6, 'Beautiful Wedding Gate', 1, 1),
(2, 1, 4, NULL, 1, 1),
(3, 1, 7, NULL, 1, 1),
(4, 1, 8, NULL, 1, 1),
(5, 2, 9, NULL, 1, 1),
(6, 2, 10, NULL, 1, 1),
(7, 2, 11, NULL, 0, 1),
(8, 1, 12, NULL, 1, 1);

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
  `issued_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`invoice_id`, `booking_id`, `subtotal`, `discount`, `tax`, `total`, `issued_at`) VALUES
(1, 1, 35000.00, 0.00, 1725.00, 36725.00, '2026-11-18 11:02:00');

-- --------------------------------------------------------

--
-- Table structure for table `lighting_equipment`
--

CREATE TABLE `lighting_equipment` (
  `lighting_id` int(10) UNSIGNED NOT NULL,
  `equipment_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `image_id` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lighting_equipment`
--

INSERT INTO `lighting_equipment` (`lighting_id`, `equipment_name`, `description`, `image_id`, `is_active`) VALUES
(1, 'LED Par Light', '', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `media_id` int(10) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `uploaded_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `media`
--

INSERT INTO `media` (`media_id`, `file_name`, `file_path`, `file_type`, `alt_text`, `uploaded_by`, `created_at`) VALUES
(1, 'cr1.png', 'uploads/packages/package_e5ef67c6f5b35ea8e2398374.png', 'image/png', 'Product Launche Package Image', 1, '2026-11-18 10:12:39'),
(2, 'b.jpg', 'uploads/packages/package_5f620a258d0fa5fbf62418fa.jpg', 'image/jpeg', 'Birthday Celebration Package Package Image', 1, '2026-11-18 10:16:00'),
(3, 'en3.jpg', 'uploads/packages/package_bd4b2700cddeb819d8d19b0f.jpg', 'image/jpeg', 'Engagement Special Package Package Image', 1, '2026-11-19 05:33:51'),
(4, 'wed1.webp', 'uploads/media/a9f4465fdcf8628026493b21126c76f0.webp', 'image/webp', 'Premium Wedding Package Package Image', 1, '2026-11-19 05:34:48'),
(5, 'Wedding gate.png', 'uploads/media/2a2ceabd747b89b89e1c3192c0d7a5ed.png', 'image/png', 'Beautiful wedding gate.', NULL, '2026-11-20 05:01:44'),
(6, 'wd.jpg', 'uploads/packages/package_cdce3e91ee56694347df004c.jpg', 'image/jpeg', 'Premium Wedding Package Package Image', 1, '2026-11-22 04:01:51'),
(7, 'wed2.png', 'uploads/media/7dab88c2302c1d5d585860b934fb0463.png', 'image/png', NULL, NULL, '2026-11-22 04:46:56'),
(8, 'wed1.png', 'uploads/media/0b4f0a35527910dfff0bbab164188359.png', 'image/png', NULL, NULL, '2026-11-22 04:47:14'),
(9, 'b.jpg', 'uploads/media/7d094e73f2d13f8e76aec1de06e8324a.jpg', 'image/jpeg', NULL, NULL, '2026-11-22 04:48:28'),
(10, 'b.png', 'uploads/media/07b556f08c7d2c04c28b1f9f2c4f5c3c.png', 'image/png', NULL, NULL, '2026-11-22 04:49:01'),
(11, 'h3.jpg', 'uploads/media/256b0175639392c64522535e2147eafc.jpg', 'image/jpeg', NULL, NULL, '2026-11-22 04:49:55'),
(12, 'wed7.png', 'uploads/media/e96c3c90202fb8558567f7275703b343.png', 'image/png', NULL, NULL, '2026-11-22 04:50:38'),
(13, 'h1.png', 'uploads/packages/package_fb2eb69d22002766c7b8ae6d.png', 'image/png', 'Hould Event Package Image', 1, '2026-11-22 05:02:38');

-- --------------------------------------------------------

--
-- Table structure for table `navigation_items`
--

CREATE TABLE `navigation_items` (
  `nav_id` int(10) UNSIGNED NOT NULL,
  `label` varchar(100) NOT NULL,
  `url` varchar(500) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_visible` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `navigation_items`
--

INSERT INTO `navigation_items` (`nav_id`, `label`, `url`, `sort_order`, `is_visible`) VALUES
(1, 'Home', 'index.php', 1, 1),
(2, 'About Us', 'about.php', 2, 1),
(3, 'Services', 'services.php', 3, 1),
(4, 'Gallery', 'gallery.php', 4, 1),
(5, 'Contact', 'contact.php', 5, 1),
(6, 'Login', 'login.php', 6, 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text DEFAULT NULL,
  `notification_type` varchar(80) DEFAULT NULL,
  `related_id` int(10) UNSIGNED DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `title`, `message`, `notification_type`, `related_id`, `is_read`, `created_at`) VALUES
(1, 1, 'New Booking Received', 'A new booking has been submitted.', 'booking', NULL, 0, '2026-11-20 15:13:55'),
(2, 1, 'New Customer Registered', 'A new customer has registered.', 'registration', NULL, 0, '2026-11-20 15:13:55'),
(3, 1, 'Payment Received', 'A new payment has been received.', 'payment', NULL, 0, '2026-11-20 15:13:55'),
(4, 1, 'New Contact Message', 'You have received a new contact message.', 'message', NULL, 0, '2026-11-20 15:13:55'),
(5, 1, 'New Review Submitted', 'A customer has submitted a review.', 'review', NULL, 1, '2026-11-20 15:13:55');

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `package_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `package_name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `short_description` text DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `base_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `guest_capacity` int(10) UNSIGNED DEFAULT 0,
  `image_id` int(10) UNSIGNED DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive','draft') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`package_id`, `category_id`, `package_name`, `slug`, `short_description`, `description`, `base_price`, `guest_capacity`, `image_id`, `is_featured`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Premium Wedding Package', 'premium-wedding-package', 'Elegant premium wedding decoration', 'Premium wedding stage, floral decoration, lighting and entrance setup.', 150000.00, 400, 6, 1, 'active', '2026-11-18 06:33:08', '2026-11-22 04:01:51'),
(2, 6, 'Engagement Special Package', 'engagement-special-package', 'Beautiful engagement ceremony decoration', 'Stage decoration, flower arrangement and entrance decoration.', 18000.00, 150, 3, 1, 'active', '2026-11-18 06:33:08', '2026-11-19 05:33:51'),
(3, 2, 'Birthday Celebration Package', 'birthday-celebration-package', 'Join us for a joyful and memorable birthday celebration filled with laughter, happiness, and unforgettable moments and make your birthday colorful and enjoyable with us.', '# A Day to Remember: Celebrating Life, Love & Happiness\r\n\r\n**Event Description:**\r\n\r\nJoin us for a joyful and memorable birthday celebration filled with laughter, happiness, and unforgettable moments. Whether it\'s a milestone birthday or a special annual celebration, we create a wonderful atmosphere where family, friends, and loved ones come together to make the day truly special.\r\n\r\nOur birthday celebration is thoughtfully designed to bring your vision to life with beautiful decorations, creative themes, delicious food, exciting entertainment, and personalized arrangements. From elegant birthday setups to fun-filled parties, every detail is carefully planned to ensure a delightful experience for the birthday celebrant and all the guests.\r\n\r\n**What to Expect:**\r\n\r\n- **Welcome & Reception:** A warm welcome for guests with beautiful entrance decorations and a photo-friendly reception area.\r\n- **Themed Birthday Decoration:** Customized stage decoration, balloons, flowers, lighting, and creative backdrops based on the chosen theme.\r\n- **Cake Cutting Ceremony:** A special birthday cake presentation followed by a memorable cake-cutting moment.\r\n- **Entertainment & Activities:** Exciting music, fun games, interactive activities, and entertainment for guests of all ages.\r\n- **Food & Refreshments:** Delicious food, snacks, desserts, and refreshments to make the celebration even more enjoyable.\r\n- **Photography & Memories:** Professional photography arrangements to capture every special moment of the celebration.\r\n\r\n**Event Details:**\r\n\r\n- **Date:** [Day, Month Date, Year]\r\n- **Time:** [Start Time] – [End Time]\r\n- **Venue:** [Venue Name, Full Address]\r\n- **Birthday Celebrant:** [Name of Birthday Person]\r\n- **Age / Milestone:** [Birthday Age]\r\n- **Theme:** [Selected Birthday Theme]\r\n- **Dress Code:** [Dress Code / Theme-Based Outfit]\r\n\r\nMake this birthday truly unforgettable with a celebration filled with joy, love, and beautiful memories. Let us take care of every detail while you enjoy the special moments with your loved ones.\r\n\r\nWe look forward to celebrating this wonderful occasion with you and making it a day to remember!', 15000.00, 100, 2, 1, 'active', '2026-11-18 06:33:08', '2026-11-18 10:19:43'),
(4, 5, 'Product Launche', 'Product Launche', 'Join us for an exclusive unveiling of our latest innovation. Experience the future industries firsthand as we introduce the product.', '# Unveiling the Future: The Official Launch of [Product Name]\r\n\r\n**Event Description:**\r\n\r\nJoin us for an exclusive corporate gathering as **[Company Name]** proudly introduces our latest innovation—**[Product Name]**. Designed to redefine industry standards and empower businesses and users, this launch marks a significant milestone in our journey toward excellence.\r\n\r\nDuring this event, attendees will get a firsthand look at how **[Product Name]** solves key challenges and creates new opportunities in the **[Industry/Sector Name]**. Our leadership and product development teams will walk you through its core features, real-world applications, and the vision behind its creation.\r\n\r\n**What to Expect:**\r\n\r\n- **Keynote Address:** Insights into industry trends and our strategic vision from [Name/Title of Keynote Speaker, e.g., our CEO].\r\n- **Live Product Demonstration:** An interactive showcase highlighting the capabilities and benefits of [Product Name].\r\n- **Q&A Session:** An open floor to engage directly with our product engineers and leadership team.\r\n- **Networking Reception:** Connect with industry peers, partners, and innovators over refreshments.\r\n\r\n**Event Details:**\r\n\r\n- **Date:** [Day, Month Date, Year]\r\n- **Time:** [Start Time] – [End Time]\r\n- **Venue:** [Venue Name, Full Address / Virtual Event Link]\r\n- **Dress Code:** Business Formal / Business Casual\r\n\r\nBe part of this exciting new chapter with us. Spaces are limited, so please confirm your attendance by **[RSVP Date]** via [Registration Link/Email].\r\n\r\nWe look forward to welcoming you and shaping the future together!', 70000.00, 200, 1, 1, 'active', '2026-11-18 10:12:39', '2026-11-18 10:12:39'),
(5, 3, 'Hould Event', 'holud-event', '', 'Make your Holud event more beautiful with us.', 25000.00, 150, 13, 1, 'active', '2026-11-22 05:02:38', '2026-11-22 05:02:38');

-- --------------------------------------------------------

--
-- Table structure for table `package_decoration_areas`
--

CREATE TABLE `package_decoration_areas` (
  `package_area_id` int(10) UNSIGNED NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `area_id` int(10) UNSIGNED NOT NULL,
  `design_description` text DEFAULT NULL,
  `image_id` int(10) UNSIGNED DEFAULT NULL,
  `additional_price` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_details`
--

CREATE TABLE `package_details` (
  `detail_id` int(10) UNSIGNED NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `detail_title` varchar(200) NOT NULL,
  `detail_description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_details`
--

INSERT INTO `package_details` (`detail_id`, `package_id`, `detail_title`, `detail_description`, `sort_order`) VALUES
(1, 3, 'A Day to Remember: A Joyful Birthday Celebration', 'A Day to Remember: Celebrating Life, Love & Happiness\r\n\r\nEvent Description:\r\n\r\nJoin us for a joyful and memorable birthday celebration filled with laughter, happiness, and unforgettable moments. Whether it\'s a milestone birthday or a special annual celebration, we create a wonderful atmosphere where family, friends, and loved ones come together to make the day truly special.\r\n\r\nOur birthday celebration is thoughtfully designed to bring your vision to life with beautiful decorations, creative themes, delicious food, exciting entertainment, and personalized arrangements. From elegant birthday setups to fun-filled parties, every detail is carefully planned to ensure a delightful experience for the birthday celebrant and all the guests.\r\n\r\nWhat to Expect:\r\n\r\nWelcome & Reception: A warm welcome for guests with beautiful entrance decorations and a photo-friendly reception area.\r\n\r\nThemed Birthday Decoration: Customized stage decoration, balloons, flowers, lighting, and creative backdrops based on the chosen theme.\r\n\r\nCake Cutting Ceremony: A special birthday cake presentation followed by a memorable cake-cutting moment.\r\n\r\nEntertainment & Activities: Exciting music, fun games, interactive activities, and entertainment for guests of all ages.\r\n\r\nFood & Refreshments: Delicious food, snacks, desserts, and refreshments to make the celebration even more enjoyable.\r\n\r\nPhotography & Memories: Professional photography arrangements to capture every special moment of the celebration.\r\n\r\nEvent Details:\r\n\r\nDate: [Day, Month Date, Year]\r\n\r\nTime: [Start Time] – [End Time]\r\n\r\nVenue: [Venue Name, Full Address]\r\n\r\nBirthday Celebrant: [Name of Birthday Person]\r\n\r\nAge / Milestone: [Birthday Age]\r\n\r\nTheme: [Selected Birthday Theme]\r\n\r\nDress Code: [Dress Code / Theme-Based Outfit]\r\n\r\nMake this birthday truly unforgettable with a celebration filled with joy, love, and beautiful memories. Let us take care of every detail while you enjoy the special moments with your loved ones.\r\n\r\nWe look forward to celebrating this wonderful occasion with you and making it a day to remember!', 1);

-- --------------------------------------------------------

--
-- Table structure for table `package_facilities`
--

CREATE TABLE `package_facilities` (
  `package_facility_id` int(10) UNSIGNED NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `facility_id` int(10) UNSIGNED NOT NULL,
  `additional_price` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_flower_options`
--

CREATE TABLE `package_flower_options` (
  `option_id` int(10) UNSIGNED NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `flower_id` int(10) UNSIGNED NOT NULL,
  `design_id` int(10) UNSIGNED DEFAULT NULL,
  `quantity_description` varchar(150) DEFAULT NULL,
  `additional_price` decimal(12,2) DEFAULT 0.00,
  `is_selectable` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_food_menus`
--

CREATE TABLE `package_food_menus` (
  `package_food_id` int(10) UNSIGNED NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `food_id` int(10) UNSIGNED NOT NULL,
  `quantity_description` varchar(150) DEFAULT NULL,
  `additional_price` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_inventory`
--

CREATE TABLE `package_inventory` (
  `package_item_id` int(10) UNSIGNED NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `inventory_id` int(10) UNSIGNED NOT NULL,
  `quantity_required` int(10) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_lighting`
--

CREATE TABLE `package_lighting` (
  `package_lighting_id` int(10) UNSIGNED NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `lighting_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED DEFAULT 1,
  `purpose` varchar(255) DEFAULT NULL,
  `additional_price` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_team_roles`
--

CREATE TABLE `package_team_roles` (
  `package_team_id` int(10) UNSIGNED NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_team_roles`
--

INSERT INTO `package_team_roles` (`package_team_id`, `package_id`, `role_id`, `quantity`, `description`) VALUES
(1, 3, 4, 1, 'He will manage the whole decoration for the event.'),
(2, 3, 6, 5, 'They will help and finish the decoration.'),
(3, 3, 1, 3, 'First photographer only takes bride photo, second one will takes only grome photo and the third one will takes the whole events photos including family photos.'),
(4, 3, 2, 1, 'He will capture the moment of the event.'),
(5, 3, 3, 1, 'He will edit the final video based on videographer\'s captured seen.');

-- --------------------------------------------------------

--
-- Table structure for table `package_vendors`
--

CREATE TABLE `package_vendors` (
  `package_vendor_id` int(10) UNSIGNED NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `vendor_id` int(10) UNSIGNED NOT NULL,
  `service_description` text DEFAULT NULL,
  `partnership_status` enum('confirmed','to_be_confirmed') DEFAULT 'to_be_confirmed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `page_id` int(10) UNSIGNED NOT NULL,
  `page_title` varchar(200) NOT NULL,
  `page_slug` varchar(150) NOT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `status` enum('published','draft') DEFAULT 'published',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`page_id`, `page_title`, `page_slug`, `meta_title`, `meta_description`, `status`, `updated_at`) VALUES
(1, 'Home', 'home', NULL, NULL, 'published', '2026-11-18 05:03:45'),
(2, 'About Us', 'about', NULL, NULL, 'published', '2026-11-18 05:03:45'),
(3, 'Services', 'services', NULL, NULL, 'published', '2026-11-18 05:03:45'),
(4, 'Gallery', 'gallery', NULL, NULL, 'published', '2026-11-18 05:03:45'),
(5, 'Contact', 'contact', NULL, NULL, 'published', '2026-11-18 05:03:45');

-- --------------------------------------------------------

--
-- Table structure for table `page_sections`
--

CREATE TABLE `page_sections` (
  `section_id` int(10) UNSIGNED NOT NULL,
  `page_id` int(10) UNSIGNED NOT NULL,
  `section_key` varchar(120) NOT NULL,
  `section_title` varchar(255) DEFAULT NULL,
  `heading` varchar(255) DEFAULT NULL,
  `subheading` varchar(255) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `image_id` int(10) UNSIGNED DEFAULT NULL,
  `button_text` varchar(100) DEFAULT NULL,
  `button_link` varchar(500) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_visible` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `page_sections`
--

INSERT INTO `page_sections` (`section_id`, `page_id`, `section_key`, `section_title`, `heading`, `subheading`, `content`, `image_id`, `button_text`, `button_link`, `sort_order`, `is_visible`, `created_at`, `updated_at`) VALUES
(1, 2, 'about_intro', 'About Introduction', 'Creating Moments, Designing Memories', 'ABOUT DECOR EASE', 'DecorEase is a professional event decoration company dedicated to bringing creativity and elegance to every celebration. We specialize in designing beautiful and personalized event environments for weddings, birthdays, engagements, anniversaries, and corporate occasions. Our team focuses on every detail to create visually stunning setups that reflect the unique style and preferences of our clients.', NULL, NULL, NULL, 1, 1, '2026-11-22 03:35:14', '2026-11-22 03:37:45'),
(2, 2, 'mission', 'Our Mission', 'Our Mission', '', 'To make exceptional event decoration accessible through creative concepts, thoughtful design, and attention to detail. We aim to understand each client\'s expectations and deliver decoration solutions that turn ordinary venues into extraordinary celebration spaces.', NULL, NULL, NULL, 2, 1, '2026-11-22 03:35:14', '2026-11-22 03:35:32'),
(3, 2, 'vision', 'Our Vision', 'Our Vision', '', 'To establish DecorEase as a leading and trusted event decoration brand, recognized for originality, professionalism, and excellence in design. We aspire to set new standards in event styling by continuously exploring fresh ideas and creating distinctive celebration experiences.', NULL, NULL, NULL, 3, 1, '2026-11-22 03:35:14', '2026-11-22 03:35:32');

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
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `amount`, `method`, `transaction_reference`, `status`, `paid_at`, `created_at`) VALUES
(1, 1, 35000.00, 'bkash', 'DEMO_001', 'completed', '2026-11-18 12:33:00', '2026-11-18 06:33:08'),
(2, 2, 18000.00, 'nagad', 'DEMO-PAY-002', 'pending', NULL, '2026-11-18 06:33:08'),
(3, 3, 5000.00, 'cash', 'DEMO-PAY-003', 'pending', NULL, '2026-11-18 06:33:08'),
(4, 4, 150000.00, 'bkash', 'DB1250', 'completed', NULL, '2026-11-22 05:48:03');

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
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `booking_id`, `customer_id`, `rating`, `comment`, `status`, `created_at`) VALUES
(1, 1, 4, 5, 'The decoration of the wedding was gorgeous and I love the red and white rose combination on the stage. Moreover, the photographers were nice and the staff were also well-behaved. I specifically liked their food menu, specially the Kacchi. You guys can try their services too.', 'approved', '2026-11-19 05:31:51');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `setting_id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(150) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(100) DEFAULT 'general',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_group`, `updated_at`) VALUES
(1, 'company_name', 'DecorEase', 'general', '2026-11-18 05:03:45'),
(2, 'company_established_date', '', 'general', '2026-11-18 05:03:45'),
(3, 'company_experience_override', '', 'general', '2026-11-18 05:03:45'),
(4, 'company_email', '', 'contact', '2026-11-18 05:03:45'),
(5, 'company_phone', '', 'contact', '2026-11-18 05:03:45'),
(6, 'company_address', '', 'contact', '2026-11-18 05:03:45'),
(7, 'footer_description', 'Event Decoration Management System', 'footer', '2026-11-18 05:03:45');

-- --------------------------------------------------------

--
-- Table structure for table `task_assignments`
--

CREATE TABLE `task_assignments` (
  `assignment_id` int(10) UNSIGNED NOT NULL,
  `task_id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_updates`
--

CREATE TABLE `task_updates` (
  `update_id` int(10) UNSIGNED NOT NULL,
  `task_id` int(10) UNSIGNED NOT NULL,
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `progress_percent` tinyint(3) UNSIGNED NOT NULL,
  `status` enum('pending','in_progress','completed') NOT NULL,
  `update_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `team_member_id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `designation` varchar(120) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_id` int(10) UNSIGNED DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `team_roles`
--

CREATE TABLE `team_roles` (
  `role_id` int(10) UNSIGNED NOT NULL,
  `role_name` varchar(120) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_roles`
--

INSERT INTO `team_roles` (`role_id`, `role_name`, `description`) VALUES
(1, 'Photographer', NULL),
(2, 'Videographer', NULL),
(3, 'Video Editor', NULL),
(4, 'Lead Decorator', NULL),
(5, 'Flower Decorator', NULL),
(6, 'Setup Assistant', NULL),
(7, 'Head Chef', NULL),
(8, 'Assistant Chef', NULL),
(9, 'Serving Team', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `testimonials`
--

CREATE TABLE `testimonials` (
  `testimonial_id` int(10) UNSIGNED NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `designation` varchar(120) DEFAULT NULL,
  `review` text NOT NULL,
  `rating` tinyint(3) UNSIGNED DEFAULT 5,
  `image_id` int(10) UNSIGNED DEFAULT NULL,
  `is_visible` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `role` enum('admin','customer','employee') NOT NULL DEFAULT 'customer',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `phone`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Mst. Rabeya', 'admin@gmail.com', '$2y$10$uciWOtKR1fF0Gq53s.swvu//.7bWrHVXWr0.IjWBsAmo8bHR3r68q', '01861093392', 'admin', 'active', '2026-11-18 05:44:24', '2026-11-18 05:44:24'),
(7, 'Nusrat Jahan', 'nusrat@gmail.com', '$2y$10$CBO6i/0nvLkOWzytNtYLLetsJIV.JMhmSjjiz0mqBzn9uim2ab0ai', '01710000001', 'customer', 'active', '2026-11-18 06:33:08', '2026-11-18 11:20:27'),
(8, 'Sadia Rahman', 'sadia.demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Y4oKoEa3Ro9llC/.og/at2uheWG/igi.', '01710000002', 'customer', 'active', '2026-11-18 06:33:08', '2026-11-18 06:33:08'),
(9, 'Tanvir Ahmed', 'tanvir.demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Y4oKoEa3Ro9llC/.og/at2uheWG/igi.', '01710000003', 'customer', 'active', '2026-11-18 06:33:08', '2026-11-18 06:33:08'),
(10, 'Mst. Rabeya', 'rabeya@gmail.com', '$2y$10$abXrTokXoc0BoI39FEvAa.FVaxSnoQ9UsOyRmbYwYgwNR4f9ss3Qi', '0189562315', 'employee', 'active', '2026-11-18 06:33:08', '2026-11-22 03:43:19'),
(11, 'Mehedi Hasan', 'mehedi.demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Y4oKoEa3Ro9llC/.og/at2uheWG/igi.', '01710000005', 'employee', 'active', '2026-11-18 06:33:08', '2026-11-18 06:33:08'),
(12, 'Kazi Arifur Rahman', 'arif@gmail.com', '$2y$10$90VChz2KG6bCp66u2pYy1eu2xrpSOnyi5l7phNat5kE491ACCKxsK', '01765319630', 'employee', 'active', '2026-11-18 09:09:45', '2026-11-22 03:48:37'),
(13, 'Nusrat Jahan', 'nusrat.jahan@gmail.com', '$2y$10$nNnxMnsPFN4LnGnYQuHJpu6lK4pPzj/hlzC6nBcgEo2SIUMG/B252', '0189562314', 'employee', 'active', '2026-11-22 03:53:10', '2026-11-22 03:53:10'),
(14, 'Tanvir Ahmed', 'tanvir@gmail.com', '$2y$10$643g32StDYAgtEWER3kV7ODCVJDsVFDyB8x.YuaK3P6RFxS1.vLBG', '0189562314', 'employee', 'active', '2026-11-22 03:56:13', '2026-11-22 03:56:13'),
(15, 'Samiul Islam', 'saimul@gmail.com', '$2y$10$ZPdodXUfkyxdNVm62YaH.OYI3uVbakbpk3mPEHP/O2ls0ZVnnjZ7C', '0189562315', 'employee', 'active', '2026-11-22 03:59:46', '2026-11-22 03:59:46'),
(16, 'Sakib Rahman', 'sakib.video@decorease.com', '$2y$10$kiiTiN69wfZH6xyVOtnvEuV68B/JkxcuTM94vVn3Cw1JqozlGuZYy', '01711000004', 'employee', 'active', '2026-11-22 06:41:24', '2026-11-22 06:41:24'),
(17, 'Mehedi Hasan', 'mehedi.video@decorease.com', '$2y$10$veXOQ6np4G3U7FuoiNh3UO4o3rs1gCP1Q2YfejIVoGmwk3VhwIwqK', '0189562311', 'employee', 'active', '2026-11-22 06:43:00', '2026-11-22 06:43:00'),
(18, 'Tanvir Hasan', 'tanvir.photographer@decorease.com', '$2y$10$wlH1AhGOEA1pUS3F8m8u5.i93m9smZ5zfZJlCJBtFatvs/aNdARPW', '01711000000', 'employee', 'active', '2026-11-22 06:44:04', '2026-11-22 06:44:04'),
(19, 'Arif Mahmud', 'arif.photographer@decorease.com', '$2y$10$S3z4CARtEzhq7U2TAAhK7.f3st0rjMbNXUt8ocAOJupqyrZR69sOS', '01711000009', 'employee', 'active', '2026-11-22 06:45:46', '2026-11-22 06:45:46'),
(20, 'Fahim Ahmed', 'fahim.editor@decorease.com', '$2y$10$LnGXvlqME.bBUDMTv5jaYO9/XsyTK9zLcHto9WbUzIHrecyZjoDPi', '01711000010', 'employee', 'active', '2026-11-22 06:47:50', '2026-11-22 06:47:50'),
(21, 'Rakibul Islam', 'rakib.decorator@decorease.com', '$2y$10$boqx/B0DC1ZrrX/rxG3gn.YopoH6WJbrI9pBUlWfhmJFM2rXYl0hC', '01711000011', 'employee', 'active', '2026-11-22 06:49:11', '2026-11-22 06:49:11'),
(22, 'Imran Hossain', 'imran.decorator@decorease.com', '$2y$10$NKQOAatRdBbPcOzU7VVHleXcyZKWt5Cv7xWgmE5GIdxo.OfRYrI0G', '01711000015', 'employee', 'active', '2026-11-22 06:50:40', '2026-11-22 06:50:40'),
(23, 'Nusrat Islam', 'nusrat.flower@decorease.com', '$2y$10$./D2r9tHh3.ZzAihdvqILuo24SIju7.WU.eME8ZMEI.5i6GqCLPQK', '01711000033', 'employee', 'active', '2026-11-22 06:52:18', '2026-11-22 06:52:18'),
(24, 'Jannatul Ferdous', 'jannat@gamil.com', '$2y$10$GMfYXXEp5DDPf4sNI2VTPuwP7.kjdbmGbYSXC6pgW2B.ODIO5A1ii', '01711000031', 'employee', 'active', '2026-11-22 06:53:28', '2026-11-22 06:53:28'),
(25, 'Rafi Ahmed', 'rifat@gmail.com', '$2y$10$.g1w4SsK0wkKmATl4KzToe1WkjZ1.QQ2BsOnjMeiBwtWvJNK7uY8K', '01711000055', 'employee', 'active', '2026-11-22 07:00:21', '2026-11-22 07:00:21'),
(26, 'Hasan Hahmud', 'hasan@gmail.com', '$2y$10$pKGPqDpPiqJ526H.0UKoHeGO4/wWnCIlhjXpHxaTXlS1nGEOajyMa', '', 'employee', 'active', '2026-11-22 07:01:02', '2026-11-22 07:01:02'),
(27, 'Kamal Uddin', 'kamal@gmail.com', '$2y$10$MnT5LU9XmgqZxtKPmRDF..WvkrDVk/vs/n2vOwezKLfkPzxwjUJbW', '01711000008', 'employee', 'active', '2026-11-22 07:02:24', '2026-11-22 07:02:24'),
(28, 'Rashed Ali', 'ali@gmail.com', '$2y$10$H0hAALwmnmOr.UtErOcKLeBozz2nQeA3/RE40sxufnQtPKlgmBcBG', '01711000007', 'employee', 'active', '2026-11-22 07:04:11', '2026-11-22 07:04:11'),
(29, 'Sumon Mia', 'sumon12@gmail.com', '$2y$10$hTJUjIXWT7XTL9.u3ovAZu.Npgj5bwzQeYG7zjkrzu2usYN66uP6.', '01565878735', 'employee', 'active', '2026-11-22 07:05:08', '2026-11-22 07:05:08'),
(30, 'Shakil Ahmed', 'shakil@gmail.com', '$2y$10$pFQFOSjEHB74ThckLZc2bONiKYSqYm2HMq/3m4RTBkNFE/8mtWcwK', '0189562319', 'employee', 'active', '2026-11-22 07:06:10', '2026-11-22 07:06:10'),
(31, 'Ashik Hasan', 'ashik@gmail.com', '$2y$10$S28SWheI8JhZ6bhSf9cBeOZqL78j9XEwu/TUCFLGO431lAyaId3dK', '0189562317', 'employee', 'active', '2026-11-22 07:06:58', '2026-11-22 07:06:58'),
(32, 'Nasir Uddin', 'nasir@gmail.com', '$2y$10$g.9VzuqnkF.894d4wdIZ2eyF255xnH07ydhORjzQfVIqPpebMS.bW', '0189562312', 'employee', 'active', '2026-11-22 07:07:42', '2026-11-22 07:07:42'),
(33, 'Rokon Uddin', 'rokon@gmail.com', '$2y$10$X21dq44/gxuu4.tONsK0ieI967KA4y4AmTyzwfyoWazYAwjbodYvC', '01711000125', 'employee', 'active', '2026-11-22 07:08:32', '2026-11-22 07:08:32'),
(34, 'Nela Moni', 'nela@gmail.com', '$2y$10$XgAfNq8JQOFs3IgleYMaDeWj3BsBI0ZbvN0ONJLGJNtF00K7X.TTi', '0189562309', 'employee', 'active', '2026-11-22 07:10:59', '2026-11-22 07:10:59'),
(35, 'Farjana Akter', 'farjana@gmail.com', '$2y$10$qaZD/76vwbSZUef6cwgN2O9dslIcHB4BspVI2VQprdjRDaQJ6d5D6', '0189562307', 'employee', 'active', '2026-11-22 07:12:00', '2026-11-22 07:16:03'),
(36, 'Rabeya Binte', 'rabi@gmail.com', '$2y$10$4FJieuZc5lwp/ucQ20Pea.baI2knLxTwK0cfyY9NkVpDopm2EsMIO', '016523987', 'employee', 'active', '2026-11-22 07:18:22', '2026-11-22 07:18:22');

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `vendor_id` int(10) UNSIGNED NOT NULL,
  `vendor_name` varchar(180) NOT NULL,
  `service_type` varchar(120) DEFAULT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive','pending') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `venues`
--

CREATE TABLE `venues` (
  `venue_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `address` text DEFAULT NULL,
  `capacity` int(10) UNSIGNED DEFAULT NULL,
  `contact_phone` varchar(30) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `venues`
--

INSERT INTO `venues` (`venue_id`, `name`, `address`, `capacity`, `contact_phone`, `status`) VALUES
(1, 'Abida Community Center', '98/A, Sukrabad, Dhaka -1207', 350, '013698452059', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`banner_id`),
  ADD KEY `image_id` (`image_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `idx_booking_date` (`event_date`),
  ADD KEY `idx_booking_status` (`status`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `venue_id` (`venue_id`);

--
-- Indexes for table `booking_addons`
--
ALTER TABLE `booking_addons`
  ADD PRIMARY KEY (`booking_addon_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `booking_selections`
--
ALTER TABLE `booking_selections`
  ADD PRIMARY KEY (`selection_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `booking_staff`
--
ALTER TABLE `booking_staff`
  ADD PRIMARY KEY (`booking_staff_id`),
  ADD UNIQUE KEY `uq_booking_employee_role` (`booking_id`,`employee_id`,`assignment_role`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `decoration_areas`
--
ALTER TABLE `decoration_areas`
  ADD PRIMARY KEY (`area_id`),
  ADD UNIQUE KEY `area_name` (`area_name`),
  ADD KEY `image_id` (`image_id`);

--
-- Indexes for table `employee_profiles`
--
ALTER TABLE `employee_profiles`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `event_categories`
--
ALTER TABLE `event_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `image_id` (`image_id`);

--
-- Indexes for table `event_tasks`
--
ALTER TABLE `event_tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `idx_task_booking` (`booking_id`);

--
-- Indexes for table `facilities`
--
ALTER TABLE `facilities`
  ADD PRIMARY KEY (`facility_id`),
  ADD UNIQUE KEY `facility_name` (`facility_name`),
  ADD KEY `image_id` (`image_id`);

--
-- Indexes for table `flowers`
--
ALTER TABLE `flowers`
  ADD PRIMARY KEY (`flower_id`),
  ADD UNIQUE KEY `flower_name` (`flower_name`),
  ADD KEY `image_id` (`image_id`);

--
-- Indexes for table `flower_designs`
--
ALTER TABLE `flower_designs`
  ADD PRIMARY KEY (`design_id`),
  ADD KEY `image_id` (`image_id`);

--
-- Indexes for table `food_menu_items`
--
ALTER TABLE `food_menu_items`
  ADD PRIMARY KEY (`food_id`),
  ADD KEY `image_id` (`image_id`);

--
-- Indexes for table `gallery_albums`
--
ALTER TABLE `gallery_albums`
  ADD PRIMARY KEY (`album_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `cover_image_id` (`cover_image_id`);

--
-- Indexes for table `gallery_images`
--
ALTER TABLE `gallery_images`
  ADD PRIMARY KEY (`gallery_image_id`),
  ADD KEY `album_id` (`album_id`),
  ADD KEY `image_id` (`image_id`);

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
-- Indexes for table `lighting_equipment`
--
ALTER TABLE `lighting_equipment`
  ADD PRIMARY KEY (`lighting_id`),
  ADD UNIQUE KEY `equipment_name` (`equipment_name`),
  ADD KEY `image_id` (`image_id`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`media_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `navigation_items`
--
ALTER TABLE `navigation_items`
  ADD PRIMARY KEY (`nav_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`package_id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_package_category` (`category_id`),
  ADD KEY `image_id` (`image_id`);

--
-- Indexes for table `package_decoration_areas`
--
ALTER TABLE `package_decoration_areas`
  ADD PRIMARY KEY (`package_area_id`),
  ADD UNIQUE KEY `uq_package_area` (`package_id`,`area_id`),
  ADD KEY `area_id` (`area_id`),
  ADD KEY `image_id` (`image_id`);

--
-- Indexes for table `package_details`
--
ALTER TABLE `package_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `package_facilities`
--
ALTER TABLE `package_facilities`
  ADD PRIMARY KEY (`package_facility_id`),
  ADD UNIQUE KEY `uq_package_facility` (`package_id`,`facility_id`),
  ADD KEY `facility_id` (`facility_id`);

--
-- Indexes for table `package_flower_options`
--
ALTER TABLE `package_flower_options`
  ADD PRIMARY KEY (`option_id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `flower_id` (`flower_id`),
  ADD KEY `design_id` (`design_id`);

--
-- Indexes for table `package_food_menus`
--
ALTER TABLE `package_food_menus`
  ADD PRIMARY KEY (`package_food_id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `food_id` (`food_id`);

--
-- Indexes for table `package_inventory`
--
ALTER TABLE `package_inventory`
  ADD PRIMARY KEY (`package_item_id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `inventory_id` (`inventory_id`);

--
-- Indexes for table `package_lighting`
--
ALTER TABLE `package_lighting`
  ADD PRIMARY KEY (`package_lighting_id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `lighting_id` (`lighting_id`);

--
-- Indexes for table `package_team_roles`
--
ALTER TABLE `package_team_roles`
  ADD PRIMARY KEY (`package_team_id`),
  ADD UNIQUE KEY `uq_package_role` (`package_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `package_vendors`
--
ALTER TABLE `package_vendors`
  ADD PRIMARY KEY (`package_vendor_id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `vendor_id` (`vendor_id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`page_id`),
  ADD UNIQUE KEY `page_slug` (`page_slug`);

--
-- Indexes for table `page_sections`
--
ALTER TABLE `page_sections`
  ADD PRIMARY KEY (`section_id`),
  ADD UNIQUE KEY `uq_page_section` (`page_id`,`section_key`),
  ADD KEY `image_id` (`image_id`);

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
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD UNIQUE KEY `uq_task_employee` (`task_id`,`employee_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `task_updates`
--
ALTER TABLE `task_updates`
  ADD PRIMARY KEY (`update_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`team_member_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `image_id` (`image_id`);

--
-- Indexes for table `team_roles`
--
ALTER TABLE `team_roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `testimonials`
--
ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`testimonial_id`),
  ADD KEY `image_id` (`image_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`vendor_id`);

--
-- Indexes for table `venues`
--
ALTER TABLE `venues`
  ADD PRIMARY KEY (`venue_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `banner_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `booking_addons`
--
ALTER TABLE `booking_addons`
  MODIFY `booking_addon_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking_selections`
--
ALTER TABLE `booking_selections`
  MODIFY `selection_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking_staff`
--
ALTER TABLE `booking_staff`
  MODIFY `booking_staff_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `message_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `decoration_areas`
--
ALTER TABLE `decoration_areas`
  MODIFY `area_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_profiles`
--
ALTER TABLE `employee_profiles`
  MODIFY `employee_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `event_categories`
--
ALTER TABLE `event_categories`
  MODIFY `category_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `event_tasks`
--
ALTER TABLE `event_tasks`
  MODIFY `task_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `facilities`
--
ALTER TABLE `facilities`
  MODIFY `facility_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `flowers`
--
ALTER TABLE `flowers`
  MODIFY `flower_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `flower_designs`
--
ALTER TABLE `flower_designs`
  MODIFY `design_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `food_menu_items`
--
ALTER TABLE `food_menu_items`
  MODIFY `food_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gallery_albums`
--
ALTER TABLE `gallery_albums`
  MODIFY `album_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `gallery_images`
--
ALTER TABLE `gallery_images`
  MODIFY `gallery_image_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `inventory_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `invoice_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lighting_equipment`
--
ALTER TABLE `lighting_equipment`
  MODIFY `lighting_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `media_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `navigation_items`
--
ALTER TABLE `navigation_items`
  MODIFY `nav_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `package_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `package_decoration_areas`
--
ALTER TABLE `package_decoration_areas`
  MODIFY `package_area_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_details`
--
ALTER TABLE `package_details`
  MODIFY `detail_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `package_facilities`
--
ALTER TABLE `package_facilities`
  MODIFY `package_facility_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_flower_options`
--
ALTER TABLE `package_flower_options`
  MODIFY `option_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_food_menus`
--
ALTER TABLE `package_food_menus`
  MODIFY `package_food_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_inventory`
--
ALTER TABLE `package_inventory`
  MODIFY `package_item_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_lighting`
--
ALTER TABLE `package_lighting`
  MODIFY `package_lighting_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_team_roles`
--
ALTER TABLE `package_team_roles`
  MODIFY `package_team_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `package_vendors`
--
ALTER TABLE `package_vendors`
  MODIFY `package_vendor_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `page_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `page_sections`
--
ALTER TABLE `page_sections`
  MODIFY `section_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `setting_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `task_assignments`
--
ALTER TABLE `task_assignments`
  MODIFY `assignment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_updates`
--
ALTER TABLE `task_updates`
  MODIFY `update_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `team_member_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `team_roles`
--
ALTER TABLE `team_roles`
  MODIFY `role_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `testimonial_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `vendor_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `venues`
--
ALTER TABLE `venues`
  MODIFY `venue_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `banners`
--
ALTER TABLE `banners`
  ADD CONSTRAINT `banners_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `media` (`media_id`) ON DELETE SET NULL;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`venue_id`) ON DELETE SET NULL;

--
-- Constraints for table `booking_addons`
--
ALTER TABLE `booking_addons`
  ADD CONSTRAINT `booking_addons_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `booking_selections`
--
ALTER TABLE `booking_selections`
  ADD CONSTRAINT `booking_selections_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `booking_staff`
--
ALTER TABLE `booking_staff`
  ADD CONSTRAINT `booking_staff_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_staff_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`);

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `decoration_areas`
--
ALTER TABLE `decoration_areas`
  ADD CONSTRAINT `decoration_areas_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `media` (`media_id`) ON DELETE SET NULL;

--
-- Constraints for table `employee_profiles`
--
ALTER TABLE `employee_profiles`
  ADD CONSTRAINT `employee_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `event_categories`
--
ALTER TABLE `event_categories`
  ADD CONSTRAINT `event_categories_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `media` (`media_id`) ON DELETE SET NULL;

--
-- Constraints for table `event_tasks`
--
ALTER TABLE `event_tasks`
  ADD CONSTRAINT `event_tasks_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `facilities`
--
ALTER TABLE `facilities`
  ADD CONSTRAINT `facilities_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `media` (`media_id`) ON DELETE SET NULL;

--
-- Constraints for table `flowers`
--
ALTER TABLE `flowers`
  ADD CONSTRAINT `flowers_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `media` (`media_id`) ON DELETE SET NULL;

--
-- Constraints for table `flower_designs`
--
ALTER TABLE `flower_designs`
  ADD CONSTRAINT `flower_designs_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `media` (`media_id`) ON DELETE SET NULL;

--
-- Constraints for table `food_menu_items`
--
ALTER TABLE `food_menu_items`
  ADD CONSTRAINT `food_menu_items_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `media` (`media_id`) ON DELETE SET NULL;

--
-- Constraints for table `gallery_albums`
--
ALTER TABLE `gallery_albums`
  ADD CONSTRAINT `gallery_albums_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `event_categories` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `gallery_albums_ibfk_2` FOREIGN KEY (`cover_image_id`) REFERENCES `media` (`media_id`) ON DELETE SET NULL;

--
-- Constraints for table `gallery_images`
--
ALTER TABLE `gallery_images`
  ADD CONSTRAINT `gallery_images_ibfk_1` FOREIGN KEY (`album_id`) REFERENCES `gallery_albums` (`album_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `gallery_images_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `media` (`media_id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `lighting_equipment`
--
ALTER TABLE `lighting_equipment`
  ADD CONSTRAINT `lighting_equipment_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `media` (`media_id`) ON DELETE SET NULL;

--
-- Constraints for table `media`
--
ALTER TABLE `media`
  ADD CONSTRAINT `media_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `packages`
--
ALTER TABLE `packages`
  ADD CONSTRAINT `packages_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `event_categories` (`category_id`),
  ADD CONSTRAINT `packages_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `media` (`media_id`) ON DELETE SET NULL;

--
-- Constraints for table `package_decoration_areas`
--
ALTER TABLE `package_decoration_areas`
  ADD CONSTRAINT `package_decoration_areas_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_decoration_areas_ibfk_2` FOREIGN KEY (`area_id`) REFERENCES `decoration_areas` (`area_id`),
  ADD CONSTRAINT `package_decoration_areas_ibfk_3` FOREIGN KEY (`image_id`) REFERENCES `media` (`media_id`) ON DELETE SET NULL;

--
-- Constraints for table `package_details`
--
ALTER TABLE `package_details`
  ADD CONSTRAINT `package_details_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`) ON DELETE CASCADE;

--
-- Constraints for table `package_facilities`
--
ALTER TABLE `package_facilities`
  ADD CONSTRAINT `package_facilities_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_facilities_ibfk_2` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`facility_id`);

--
-- Constraints for table `package_flower_options`
--
ALTER TABLE `package_flower_options`
  ADD CONSTRAINT `package_flower_options_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_flower_options_ibfk_2` FOREIGN KEY (`flower_id`) REFERENCES `flowers` (`flower_id`),
  ADD CONSTRAINT `package_flower_options_ibfk_3` FOREIGN KEY (`design_id`) REFERENCES `flower_designs` (`design_id`) ON DELETE SET NULL;

--
-- Constraints for table `package_food_menus`
--
ALTER TABLE `package_food_menus`
  ADD CONSTRAINT `package_food_menus_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_food_menus_ibfk_2` FOREIGN KEY (`food_id`) REFERENCES `food_menu_items` (`food_id`);

--
-- Constraints for table `package_inventory`
--
ALTER TABLE `package_inventory`
  ADD CONSTRAINT `package_inventory_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_inventory_ibfk_2` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`inventory_id`);

--
-- Constraints for table `package_lighting`
--
ALTER TABLE `package_lighting`
  ADD CONSTRAINT `package_lighting_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_lighting_ibfk_2` FOREIGN KEY (`lighting_id`) REFERENCES `lighting_equipment` (`lighting_id`);

--
-- Constraints for table `package_team_roles`
--
ALTER TABLE `package_team_roles`
  ADD CONSTRAINT `package_team_roles_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_team_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `team_roles` (`role_id`);

--
-- Constraints for table `package_vendors`
--
ALTER TABLE `package_vendors`
  ADD CONSTRAINT `package_vendors_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_vendors_ibfk_2` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`);

--
-- Constraints for table `page_sections`
--
ALTER TABLE `page_sections`
  ADD CONSTRAINT `page_sections_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `pages` (`page_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `page_sections_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `media` (`media_id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`);

--
-- Constraints for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD CONSTRAINT `task_assignments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `event_tasks` (`task_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_assignments_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`);

--
-- Constraints for table `task_updates`
--
ALTER TABLE `task_updates`
  ADD CONSTRAINT `task_updates_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `event_tasks` (`task_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_updates_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `team_members_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `team_members_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `media` (`media_id`) ON DELETE SET NULL;

--
-- Constraints for table `testimonials`
--
ALTER TABLE `testimonials`
  ADD CONSTRAINT `testimonials_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `media` (`media_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
