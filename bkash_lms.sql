-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 20, 2025 at 06:53 PM
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
-- Database: `bkash_lms`
--

-- --------------------------------------------------------

--
-- Table structure for table `approval_flows`
--

CREATE TABLE `approval_flows` (
  `id` int(10) UNSIGNED NOT NULL,
  `meal_type_id` int(10) UNSIGNED NOT NULL,
  `emp_type_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `type` enum('MANUAL','AUTO') NOT NULL DEFAULT 'MANUAL',
  `effective_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approval_flows`
--

INSERT INTO `approval_flows` (`id`, `meal_type_id`, `emp_type_id`, `is_active`, `type`, `effective_date`, `created_at`, `updated_at`) VALUES
(13, 2, 0, 1, 'MANUAL', '2025-09-20', '2025-09-19 23:02:37', '2025-09-19 23:02:37'),
(14, 3, 0, 1, 'MANUAL', '2025-09-20', '2025-09-19 23:06:29', '2025-09-19 23:06:47');

-- --------------------------------------------------------

--
-- Table structure for table `approval_steps`
--

CREATE TABLE `approval_steps` (
  `id` int(10) UNSIGNED NOT NULL,
  `flow_id` int(10) UNSIGNED NOT NULL,
  `approver_role` int(11) DEFAULT NULL,
  `approver_user_id` int(11) DEFAULT NULL,
  `fallback_role` int(11) DEFAULT NULL,
  `step_order` int(10) UNSIGNED NOT NULL,
  `approver_type` enum('ROLE','USER','LINE_MANAGER') NOT NULL DEFAULT 'ROLE'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approval_steps`
--

INSERT INTO `approval_steps` (`id`, `flow_id`, `approver_role`, `approver_user_id`, `fallback_role`, `step_order`, `approver_type`) VALUES
(2, 2, 1, NULL, NULL, 1, 'ROLE'),
(17, 4, NULL, 4, NULL, 2, 'USER'),
(20, 11, 3, NULL, NULL, 1, 'ROLE'),
(18, 7, NULL, NULL, 1, 1, 'LINE_MANAGER'),
(16, 4, NULL, NULL, 1, 1, 'LINE_MANAGER'),
(22, 14, NULL, NULL, 2, 1, 'LINE_MANAGER'),
(23, 14, 2, NULL, NULL, 2, 'ROLE');

-- --------------------------------------------------------

--
-- Table structure for table `cafeterias`
--

CREATE TABLE `cafeterias` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cafeterias`
--

INSERT INTO `cafeterias` (`id`, `name`, `location`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'ST', 'ST', 1, '2025-06-23 07:45:13', '2025-09-11 10:38:40'),
(2, 'SKS Level 5', 'SKS Level 5', 1, '2025-06-23 14:47:53', '2025-07-20 14:28:05'),
(3, 'SKS Level 6', 'SKS Level 6', 1, '2025-06-26 15:09:24', '2025-07-20 14:28:23'),
(4, 'SKS Level 9', 'SKS Level 9', 1, '2025-06-26 15:09:42', '2025-07-20 14:28:38'),
(5, 'SKS Level 11', 'SKS Level 11', 1, '2025-07-20 14:28:57', '2025-07-20 14:28:57'),
(6, 'RAOWA', 'RAOWA', 1, '2025-07-20 14:29:09', '2025-07-20 14:29:09');

-- --------------------------------------------------------

--
-- Table structure for table `cutoff_times`
--

CREATE TABLE `cutoff_times` (
  `id` int(10) UNSIGNED NOT NULL,
  `meal_type_id` int(10) UNSIGNED NOT NULL,
  `cut_off_time` time NOT NULL,
  `lead_days` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `max_horizon_days` int(10) UNSIGNED NOT NULL DEFAULT 30,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `cutoff_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cutoff_times`
--

INSERT INTO `cutoff_times` (`id`, `meal_type_id`, `cut_off_time`, `lead_days`, `max_horizon_days`, `is_active`, `cutoff_date`, `created_at`, `updated_at`) VALUES
(1, 1, '17:00:00', 1, 30, 1, NULL, '2025-09-19 23:19:45', '2025-09-19 23:19:45'),
(2, 2, '09:00:00', 0, 31, 1, NULL, '2025-09-19 23:22:08', '2025-09-19 23:22:08'),
(3, 3, '19:00:00', 1, 31, 1, NULL, '2025-09-19 23:22:49', '2025-09-19 23:22:49');

-- --------------------------------------------------------

--
-- Table structure for table `employment_types`
--

CREATE TABLE `employment_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `employment_types`
--

INSERT INTO `employment_types` (`id`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(7, 'INTERN', 'INTERN', 1, '2025-09-09 11:38:04', '2025-09-09 11:38:04'),
(2, 'FTC', 'FTC', 1, '2025-09-09 11:40:03', '2025-09-09 11:40:10'),
(3, 'NEW JOINER', 'NEW JOINER', 1, '2025-09-09 11:40:53', '2025-09-09 11:40:53'),
(4, 'OS', 'OS', 1, '2025-09-09 11:41:12', '2025-09-09 11:41:12'),
(5, 'SECURITY GUARD', 'Security Guard', 1, '2025-09-09 11:42:11', '2025-09-09 16:14:32'),
(6, 'SUPPORT STAFF', 'Support Staff', 1, '2025-09-09 11:42:20', '2025-09-09 16:14:50'),
(1, 'EMPLOYEE', 'Employee', 1, '2025-09-09 11:42:20', '2025-09-09 16:14:50'),
(8, 'HR GUEST', 'HR Guest', 1, '2025-09-09 11:42:20', '2025-09-09 16:14:50'),
(9, 'PROJECT GUEST', 'Project Guest', 1, '2025-09-09 11:42:20', '2025-09-09 16:14:50'),
(10, 'PERSONAL GUEST', 'Personal Guest', 1, '2025-09-09 11:42:20', '2025-09-09 16:14:50');

-- --------------------------------------------------------

--
-- Table structure for table `meal_approvals`
--

CREATE TABLE `meal_approvals` (
  `id` int(10) UNSIGNED NOT NULL,
  `subs_id` int(10) UNSIGNED NOT NULL,
  `approver_role` int(10) UNSIGNED DEFAULT NULL,
  `approver_user_id` int(10) UNSIGNED DEFAULT NULL,
  `approved_by` int(10) UNSIGNED NOT NULL,
  `approval_status` enum('PENDING','ACTIVE','REJECTED') NOT NULL DEFAULT 'PENDING',
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meal_cards`
--

CREATE TABLE `meal_cards` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `card_code` varchar(64) NOT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `meal_cards`
--

INSERT INTO `meal_cards` (`id`, `user_id`, `employee_id`, `card_code`, `status`, `created_at`, `updated_at`) VALUES
(13, 8, '2024', '123456', 'ACTIVE', '2025-09-19 16:35:52', '2025-09-19 16:35:52');

-- --------------------------------------------------------

--
-- Table structure for table `meal_contributions`
--

CREATE TABLE `meal_contributions` (
  `id` int(10) UNSIGNED NOT NULL,
  `meal_type_id` int(10) UNSIGNED NOT NULL,
  `emp_type_id` int(11) NOT NULL,
  `cafeteria_id` int(10) UNSIGNED DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `company_tk` decimal(10,2) NOT NULL,
  `user_tk` decimal(10,2) NOT NULL,
  `effective_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meal_contributions`
--

INSERT INTO `meal_contributions` (`id`, `meal_type_id`, `emp_type_id`, `cafeteria_id`, `base_price`, `company_tk`, `user_tk`, `effective_date`, `is_active`, `created_at`) VALUES
(1, 1, 1, NULL, 300.00, 140.00, 160.00, '2025-09-18', 1, '2025-09-20 00:56:35'),
(3, 1, 10, NULL, 300.00, 0.00, 300.00, NULL, 1, '2025-09-20 16:23:36'),
(4, 1, 8, NULL, 300.00, 0.00, 300.00, NULL, 1, '2025-09-20 16:24:51'),
(5, 1, 9, NULL, 300.00, 0.00, 300.00, NULL, 1, '2025-09-20 16:25:12');

-- --------------------------------------------------------

--
-- Table structure for table `meal_costs`
--

CREATE TABLE `meal_costs` (
  `id` int(10) UNSIGNED NOT NULL,
  `cafeteria_id` int(11) DEFAULT NULL,
  `meal_type_id` int(11) NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `effective_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meal_costs`
--

INSERT INTO `meal_costs` (`id`, `cafeteria_id`, `meal_type_id`, `base_price`, `effective_date`, `is_active`, `created_at`) VALUES
(1, NULL, 1, 150.00, '2025-09-01', 1, '2025-09-19 17:58:50'),
(2, NULL, 2, 200.00, '2025-10-21', 1, '2025-09-19 17:59:16'),
(3, NULL, 3, 200.00, '2025-10-21', 1, '2025-09-19 17:59:32'),
(4, NULL, 1, 300.00, '2025-09-18', 1, '2025-09-19 17:59:55');

-- --------------------------------------------------------

--
-- Table structure for table `meal_reference`
--

CREATE TABLE `meal_reference` (
  `id` int(11) NOT NULL,
  `subs_id` int(11) NOT NULL,
  `ref_id` varchar(15) DEFAULT NULL,
  `ref_name` varchar(50) DEFAULT NULL,
  `ref_phone` varchar(15) DEFAULT NULL,
  `otp` int(10) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meal_reference`
--

INSERT INTO `meal_reference` (`id`, `subs_id`, `ref_id`, `ref_name`, `ref_phone`, `otp`, `created_at`) VALUES
(1, 3, NULL, 'partha', '01838737333', 485219, '2025-09-20 17:06:14'),
(2, 4, NULL, 'jamal', '01521450824', 532011, '2025-09-20 22:32:03'),
(3, 5, NULL, 'kamal', '01838737333', 825685, '2025-09-20 22:32:03'),
(4, 6, NULL, 'jamal', '01521450824', 20958, '2025-09-20 22:36:10'),
(5, 7, NULL, 'jamal', '01521450824', 20958, '2025-09-20 22:36:10'),
(6, 8, NULL, 'kamal', '01838737333', 65029, '2025-09-20 22:36:10'),
(7, 9, NULL, 'kamal', '01838737333', 65029, '2025-09-20 22:36:10');

-- --------------------------------------------------------

--
-- Table structure for table `meal_subscriptions`
--

CREATE TABLE `meal_subscriptions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `meal_type_id` int(11) NOT NULL COMMENT 'lunch,ifter,sehri',
  `emp_type_id` int(11) NOT NULL COMMENT 'employee,guest,intern',
  `cafeteria_id` int(11) NOT NULL COMMENT 'st,raowa,sks level',
  `subs_date` date NOT NULL,
  `status` enum('ACTIVE','CANCELLED','REDEEMED','NO_SHOW','PENDING') NOT NULL DEFAULT 'ACTIVE',
  `price` decimal(10,2) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `unsubs_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meal_subscriptions`
--

INSERT INTO `meal_subscriptions` (`id`, `user_id`, `meal_type_id`, `emp_type_id`, `cafeteria_id`, `subs_date`, `status`, `price`, `created_by`, `unsubs_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 2, '2025-09-21', 'CANCELLED', 160.00, 1, 1, '2025-09-20 10:59:39', '2025-09-20 11:57:08'),
(2, 7, 1, 1, 3, '2025-09-21', 'CANCELLED', 160.00, 1, 1, '2025-09-20 11:19:16', '2025-09-20 12:00:32'),
(3, 1, 1, 10, 2, '2025-09-22', 'ACTIVE', 300.00, 1, 0, '2025-09-20 17:06:14', '2025-09-20 17:06:14'),
(4, 1, 1, 8, 2, '2025-09-23', 'ACTIVE', 300.00, 1, 0, '2025-09-20 22:32:03', '2025-09-20 22:32:03'),
(5, 1, 1, 8, 1, '2025-09-23', 'ACTIVE', 300.00, 1, 0, '2025-09-20 22:32:03', '2025-09-20 22:32:03'),
(6, 1, 1, 8, 2, '2025-09-24', 'ACTIVE', 300.00, 1, 0, '2025-09-20 22:36:10', '2025-09-20 22:36:10'),
(7, 1, 1, 8, 2, '2025-09-25', 'ACTIVE', 300.00, 1, 0, '2025-09-20 22:36:10', '2025-09-20 22:36:10'),
(8, 1, 1, 8, 1, '2025-09-24', 'ACTIVE', 300.00, 1, 0, '2025-09-20 22:36:10', '2025-09-20 22:36:10'),
(9, 1, 1, 8, 1, '2025-09-25', 'ACTIVE', 300.00, 1, 0, '2025-09-20 22:36:10', '2025-09-20 22:36:10');

-- --------------------------------------------------------

--
-- Table structure for table `meal_tokens`
--

CREATE TABLE `meal_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `subs_id` int(11) NOT NULL,
  `meal_type_id` int(10) UNSIGNED NOT NULL,
  `emp_type_id` int(11) NOT NULL,
  `cafeteria_id` int(10) UNSIGNED NOT NULL,
  `token_code` varchar(100) NOT NULL,
  `meal_date` date NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meal_types`
--

CREATE TABLE `meal_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meal_types`
--

INSERT INTO `meal_types` (`id`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Lunch', 'Lunch Meal', 1, '2025-06-29 19:00:15', '2025-09-19 23:10:53'),
(2, 'Ifter', 'Ifter Meal', 1, '2025-06-29 19:00:25', '2025-09-19 23:10:27'),
(3, 'Sehri', 'Sehri Meal', 1, '2025-07-15 14:04:44', '2025-09-19 23:11:03'),
(4, 'Eid Morning Snacks', 'Eid Morning Snacks', 1, '2025-07-15 14:05:16', '2025-07-23 18:51:49'),
(5, 'Eid Lunch', 'Eid Lunch', 1, '2025-07-23 18:52:20', '2025-09-19 23:12:54'),
(6, 'Eid Evening Snacks', 'Eid Evening Snacks', 1, '2025-07-23 18:52:56', '2025-09-19 23:13:05'),
(7, 'Eid Dinner', 'Eid Dinner', 1, '2025-07-23 18:53:27', '2025-09-19 23:13:12');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `occasions`
--

CREATE TABLE `occasions` (
  `id` int(10) UNSIGNED NOT NULL,
  `tag` varchar(100) NOT NULL,
  `name` varchar(50) NOT NULL,
  `occasion_date` date NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `occasions`
--

INSERT INTO `occasions` (`id`, `tag`, `name`, `occasion_date`) VALUES
(1, 'eid_al_fitr', 'Eid-al‑Fitr', '2026-03-20'),
(2, 'eid_al_adha', 'Eid al‑Adha', '2026-05-27');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `description`) VALUES
(1, 'admin.dashboard', 'Dashboard'),
(2, 'meal.subscriptions', 'Lunch Management'),
(3, 'admin.subscriptions.new', 'Lunch Subscription Form'),
(4, 'admin.subscriptions.history', 'My Subscriptions'),
(5, 'admin.subscriptions.all-subscriptions', 'All Lunch Subscription'),
(14, 'rbac.permissions.read', 'List permissions'),
(13, 'rbac.manage', 'Access RBAC module'),
(12, 'admin.approvals', 'Meal Approvals'),
(11, 'admin.users', 'Employee Management'),
(15, 'rbac.permissions.create', 'Create permissions'),
(16, 'rbac.permissions.update', 'Update permissions'),
(17, 'rbac.permissions.delete', 'Delete permissions'),
(18, 'rbac.assign', 'Assign roles > users & permissions > roles'),
(20, 'admin.subscriptions.unsubscribe_single', 'Single unsubs'),
(21, 'admin.subscriptions.unsubscribe_bulk', 'Bulk Unsubscribe'),
(22, 'admin.ramadan.ifter-subscription.history', 'Subscribe Ifter List'),
(23, 'admin.ramadan.ifter-subscription.all-ifter-list', 'All Ifter List'),
(24, 'admin.ramadan.ifter-subscription.new', 'New Ifter Subscription Form'),
(26, 'admin.ifter-subscription.unsubscribe', 'Unsubscribe Ifter Subscription'),
(27, 'admin.ramadan.sehri-subscription.history', 'Subscribe Sehri List'),
(28, 'admin.ramadan.sehri-subscription.all-sehri-list', 'All Sehri List'),
(29, 'admin.ramadan.sehri-subscription.new', 'Sehri Subscription Form'),
(30, 'admin.sehri-subscription.store', 'Sehri Subscription Save'),
(31, 'admin.sehri-subscription.unsubscribe', 'Unsubscribe Sehri Subscription'),
(32, 'admin.eid-subscription.history', 'My Subscriptions'),
(33, 'admin.eid-subscription.all-eid-subscription-list', 'All Subscriptions'),
(34, 'admin.eid-subscription.new', 'Subscribe Meal Form'),
(35, 'admin.eid-subscription.store', 'Save Eid Subscription'),
(36, 'admin.eid-subscription.unsubscribe', 'Unsubscribe Eid Subscription'),
(37, 'admin.guest-subscriptions.history', 'My Guest List'),
(38, 'admin.guest-subscriptions.all-guest-list', 'All Guest List'),
(39, 'admin.guest-subscriptions.new', 'Personal Guest Subscription Form'),
(40, 'admin.guest-subscriptions.store', 'Guest Subscription Save'),
(41, 'admin.guest-subscriptions.unsubscribe', 'Unsubscribe Guest Subscription'),
(42, 'admin.guest-subscriptions.bulk-upload', 'Bulk Upload'),
(43, 'admin.guest-subscriptions.process-upload', 'Bulk Upload Save'),
(44, 'admin.guest-subscriptions.bulk-list', 'Bulk Upload list'),
(45, 'admin.intern-requisitions.index', 'Subscription List'),
(46, 'admin.intern-requisitions.new', 'Bulk Subscription Form'),
(47, 'admin.intern-requisitions.process-upload', 'Bulk Subscription Save'),
(48, 'admin.intern-subscriptions.unsubscribe_single', 'Unsubscribe Intern Requisitions'),
(51, 'admin.intern-requisitions', 'Intern Requisitions'),
(52, 'admin.eid-subscription', 'Eid Meal'),
(53, 'admin.ramadan', 'Ramadan Meal'),
(54, 'admin.guest-subscriptions', 'Guest Subscription'),
(55, 'admin.settings', 'Settings'),
(56, 'admin.approval-flows.index', 'Approval Flows'),
(57, 'admin.cafeterias.index', 'Cafeterias'),
(58, 'admin.meal-costs.index', 'Meal Costs'),
(59, 'admin.contributions.index', 'Contributions'),
(60, 'admin.public-holidays.index', 'Public Holidays'),
(61, 'admin.occasions.index', 'Occasions'),
(62, 'admin.cutoff-times.index', 'Meal Cut-Off Times'),
(63, 'admin.ramadan-periods.index', 'Ramadan Periods'),
(64, 'admin.meal-types.index', 'Meal Types'),
(65, 'admin.guest-subscriptions.unsubscribe_bulk', 'Guest Bulk Unsubs'),
(66, 'employee.dashboard', 'Employee Dashboard'),
(67, 'employee.subscriptions.new', 'employee.subscriptions.new'),
(68, 'employee.subscriptions.store', 'employee.subscriptions.store'),
(69, 'employee.subscriptions.history', 'employee.subscriptions.history'),
(70, 'employee.subscriptions.view', 'employee.subscriptions.view'),
(71, 'employee.subscriptions.unsubscribe', 'employee.subscriptions.unsubscribe'),
(72, 'employee.ifter.history', 'employee.ifter.history'),
(73, 'employee.ifter.new', 'employee.ifter.new'),
(120, 'admin.sehri-subscription.unsubscribe_bulk', 'Sehri Bulk Unsubscribe'),
(75, 'employee.ifter.unsubscribe', 'employee.ifter.unsubscribe'),
(76, 'employee.sehri.history', 'employee.sehri.history'),
(77, 'employee.sehri.new', 'employee.sehri.new'),
(79, 'employee.sehri.unsubscribe', 'employee.sehri.unsubscribe'),
(80, 'employee.eid.history', 'employee.eid.history'),
(81, 'employee.eid.new', 'Eid Subscription Form'),
(121, 'admin.eid-subscription.unsubscribe_bulk', 'Eid Bulk Unsubscribe'),
(83, 'employee.eid.unsubscribe', 'employee.eid.unsubscribe'),
(84, 'employee.guests.index', 'guest-subscriptions'),
(85, 'employee.guests.new', 'Guest Subscription Form'),
(87, 'employee.guests.unsubscribe', 'employee.guests.unsubscribe'),
(88, 'employee.meal.subscriptions', 'Employee Lunch'),
(89, 'employee.guest-subscriptions', 'Employee Guest-subscriptions'),
(90, 'employee.eid-subscription', 'Eid Meal'),
(91, 'employee.ramadan', 'Ramadan Meal'),
(92, 'vendor.dashboard', 'Vendor Dashboard'),
(93, 'vendor.registrations.view', 'Vendor Registrations List'),
(94, 'vendor.registrations.monthly', 'vendor.registrations.monthly'),
(95, 'vendor.meals.view', 'vendor.meals.view'),
(96, 'vendor.reports.view', 'vendor.reports.view'),
(97, 'vendor.reports.export', 'vendor.reports.export'),
(98, 'vendor.history.view', 'vendor.history.view'),
(99, 'vendor.history.export', 'vendor.history.export'),
(100, 'vendor.profile.view', 'vendor.profile.view'),
(101, 'vendor.profile.update', 'vendor.profile.update'),
(102, 'vendor.registrations', 'vendor.registrations'),
(103, 'vendor.reports', 'Reports'),
(119, 'admin.ifter-subscription.unsubscribe_bulk', 'Unsubscribe Ifter Bulk'),
(104, 'admin.report', 'Report'),
(105, 'admin.report.meal-charge-list-for-payroll', 'Meal Charge list for payroll'),
(106, 'admin.report.meal-report-for-billing', 'Meal Report for billing'),
(107, 'admin.report.meal-detail-report', 'Meal Detail Report'),
(110, 'admin.report.daily-mealreport', 'Daily Meal Report'),
(109, 'admin.report.food-consumption-report', 'Food Consumption Report'),
(111, 'admin.meal-cards', 'Meal Cards'),
(112, 'admin.users.new', 'Add User'),
(113, 'admin.user.set-rule', 'Set Rules'),
(114, 'admin.users.edit', 'Edit'),
(115, 'admin.users.active', 'Active'),
(116, 'admin.users.inactive', 'Inactive'),
(117, 'admin.users.line-manager-set', 'Set Line Manager'),
(118, 'employee.approvals', 'Employee Approvals'),
(122, 'admin.intern-subscriptions.unsubscribe_bulk', 'Intern Bulk Unsubscribe'),
(123, 'admin.meal-cards.form', 'Meal Card Create'),
(124, 'admin.meal-cards.edit', 'Meal Card Edit'),
(125, 'admin.employment-types.index', 'Employment Types List'),
(126, 'admin.employment-types.new', 'Employment Types Create'),
(127, 'admin.employment-types.edit', 'Employment Types Edit'),
(128, 'admin.cafeterias.new', 'Cafeterias Create'),
(129, 'admin.cafeterias.edit', 'Cafeterias Edit'),
(130, 'admin.meal-types.new', 'Meal Types Create'),
(131, 'admin.meal-types.edit', 'Meal Types Edit'),
(132, 'admin.meal-costs.new', 'Meal-costs Create'),
(133, 'admin.meal-costs.edit', 'Meal-costs edit'),
(134, 'admin.contributions.new', 'Contributions Create'),
(135, 'admin.contributions.edit', 'Contributions Edit'),
(136, 'admin.public-holidays.new', 'Public-holidays Create'),
(137, 'admin.public-holidays.edit', 'Public-holidays Edit'),
(138, 'admin.occasions.new', 'Occasions Create'),
(139, 'admin.occasions.edit', 'Occasions edit'),
(140, 'admin.cutoff-times.new', 'Cutoff-times Create'),
(141, 'admin.cutoff-times.edit', 'Cutoff-times Edit'),
(142, 'admin.ramadan-periods.create', 'Ramadan Periods Create'),
(143, 'ramadan.periods.update', 'Ramadan Periods Edit'),
(144, 'admin.ramadan-periods.edit', 'Ramadan-periods Edit');

-- --------------------------------------------------------

--
-- Table structure for table `public_holidays`
--

CREATE TABLE `public_holidays` (
  `id` int(10) UNSIGNED NOT NULL,
  `holiday_date` date NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `public_holidays`
--

INSERT INTO `public_holidays` (`id`, `holiday_date`, `description`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '2025-10-01', 'Durga Puja Holiday', 1, NULL, '2025-06-26 13:21:31', '2025-09-19 01:28:14'),
(2, '2025-10-02', 'Durga Puja Holiday', 1, NULL, '2025-06-26 14:10:14', '2025-09-19 01:28:18'),
(5, '2025-12-16', 'Victory Day', 1, NULL, '2025-07-03 16:54:15', '2025-09-19 01:25:51'),
(6, '2025-12-25', 'Christmas Day', 1, 1, '2025-07-03 16:56:24', '2025-09-19 01:28:21'),
(7, '2026-02-04', 'Shab e-Barat', 1, 1, '2025-09-11 14:52:22', '2025-09-19 01:26:27'),
(8, '2026-02-21', 'Shaheed Day', 1, 1, '2025-09-11 15:00:46', '2025-09-19 01:28:24'),
(9, '2026-03-18', 'Laylat al-Qadr', 1, 1, '2025-09-11 15:07:48', '2025-09-19 01:28:08');

-- --------------------------------------------------------

--
-- Table structure for table `ramadan_config`
--

CREATE TABLE `ramadan_config` (
  `id` int(10) UNSIGNED NOT NULL,
  `year` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ramadan_config`
--

INSERT INTO `ramadan_config` (`id`, `year`, `start_date`, `end_date`) VALUES
(3, 2024, '2026-02-17', '2026-03-18'),
(5, 2025, '2025-09-01', '2025-09-30');

-- --------------------------------------------------------

--
-- Table structure for table `remarks`
--

CREATE TABLE `remarks` (
  `id` int(11) NOT NULL,
  `subs_id` int(11) NOT NULL,
  `remark` text DEFAULT NULL,
  `approver_remark` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `remarks`
--

INSERT INTO `remarks` (`id`, `subs_id`, `remark`, `approver_remark`, `created_at`) VALUES
(1, 2, 'test3', '', '2025-09-20 11:54:03'),
(2, 1, 'hh', '', '2025-09-20 11:57:08'),
(3, 2, 'sdf', '', '2025-09-20 12:00:32'),
(4, 3, 'll', NULL, '2025-09-20 17:06:14'),
(5, 4, 'kk', NULL, '2025-09-20 22:32:03'),
(6, 5, 'kk', NULL, '2025-09-20 22:32:03'),
(7, 6, 'll', NULL, '2025-09-20 22:36:10'),
(8, 7, 'll', NULL, '2025-09-20 22:36:10'),
(9, 8, 'll', NULL, '2025-09-20 22:36:10'),
(10, 9, 'll', NULL, '2025-09-20 22:36:10');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(3, 'Employee', NULL),
(2, 'ADMIN', NULL),
(4, 'VENDOR', 'Vendor portal'),
(1, 'SUPER ADMIN', 'God mode');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `permission_id` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`) VALUES
(786, 2, 144),
(785, 2, 142),
(784, 2, 63),
(783, 2, 141),
(782, 2, 140),
(781, 2, 62),
(780, 2, 139),
(779, 2, 138),
(778, 2, 61),
(777, 2, 137),
(776, 2, 136),
(775, 2, 60),
(774, 2, 135),
(773, 2, 134),
(772, 2, 59),
(771, 2, 133),
(770, 2, 132),
(769, 2, 58),
(768, 2, 127),
(767, 2, 126),
(766, 2, 125),
(765, 2, 131),
(764, 2, 130),
(763, 2, 64),
(762, 2, 129),
(761, 2, 128),
(760, 2, 57),
(759, 2, 55),
(758, 2, 109),
(34, 3, 66),
(35, 3, 118),
(36, 3, 88),
(37, 3, 67),
(38, 3, 69),
(39, 3, 71),
(40, 3, 89),
(41, 3, 85),
(42, 3, 84),
(43, 3, 87),
(44, 3, 91),
(45, 3, 73),
(46, 3, 72),
(47, 3, 75),
(48, 3, 77),
(49, 3, 76),
(50, 3, 79),
(51, 4, 92),
(52, 4, 102),
(53, 4, 93),
(54, 4, 94),
(55, 4, 95),
(56, 4, 103),
(57, 4, 96),
(58, 4, 98),
(59, 4, 100),
(757, 2, 110),
(756, 2, 107),
(755, 2, 106),
(754, 2, 105),
(753, 2, 104),
(752, 2, 124),
(751, 2, 123),
(750, 2, 111),
(749, 2, 122),
(748, 2, 48),
(747, 2, 45),
(746, 2, 46),
(745, 2, 51),
(744, 2, 121),
(743, 2, 36),
(742, 2, 33),
(741, 2, 32),
(740, 2, 34),
(739, 2, 52),
(738, 2, 120),
(737, 2, 31),
(736, 2, 28),
(735, 2, 29),
(734, 2, 27),
(733, 2, 119),
(732, 2, 26),
(731, 2, 23),
(730, 2, 24),
(729, 2, 22),
(728, 2, 53),
(727, 2, 44),
(726, 2, 42),
(725, 2, 38),
(724, 2, 37),
(723, 2, 39),
(722, 2, 54),
(721, 2, 21),
(720, 2, 20),
(719, 2, 5),
(718, 2, 4),
(717, 2, 3),
(716, 2, 2),
(715, 2, 12),
(714, 2, 117),
(713, 2, 116),
(712, 2, 115),
(711, 2, 114),
(710, 2, 113),
(709, 2, 112),
(708, 2, 11),
(707, 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `msisdn` varchar(16) NOT NULL,
  `message` text NOT NULL,
  `status` enum('PENDING','QUEUED','SENT','DELIVERED','FAILED') NOT NULL DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms_logs`
--

INSERT INTO `sms_logs` (`id`, `msisdn`, `message`, `status`, `created_at`) VALUES
(1, '8801838737333', 'bKash Lunch OTP: 485219. Valid once per day on Sep 22, 2025, at Cafeteria SKS Level 5 only. Thank you', 'SENT', '2025-09-20 11:06:14'),
(2, '8801521450824', 'bKash Lunch OTP: 532011. Valid once per day on Sep 23, 2025, at Cafeteria SKS Level 5 only. Thank you', 'SENT', '2025-09-20 16:32:03'),
(3, '8801838737333', 'bKash Lunch OTP: 825685. Valid once per day on Sep 23, 2025, at Cafeteria ST only. Thank you', 'SENT', '2025-09-20 16:32:03'),
(4, '8801521450824', 'bKash Lunch OTP: 020958. Valid once per day on Sep 24-25, 2025, at Cafeteria SKS Level 5 only. Thank you', 'SENT', '2025-09-20 16:36:10'),
(5, '8801838737333', 'bKash Lunch OTP: 065029. Valid once per day on Sep 24-25, 2025, at Cafeteria ST only. Thank you', 'SENT', '2025-09-20 16:36:10');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `division` varchar(100) DEFAULT NULL,
  `user_type` enum('EMPLOYEE','VENDOR','ADMIN') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'EMPLOYEE',
  `login_method` enum('SSO','LOCAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'SSO',
  `local_user_type` enum('SYSTEM','VENDOR','ADFS') NOT NULL,
  `password` varchar(25) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ACTIVE',
  `line_manager_id` int(11) DEFAULT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `employee_id`, `name`, `email`, `phone`, `department`, `designation`, `division`, `user_type`, `login_method`, `local_user_type`, `password`, `status`, `line_manager_id`, `password_hash`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Super Admin', 'superadmin@bkash.test', '01838737333', NULL, NULL, NULL, 'ADMIN', 'LOCAL', 'SYSTEM', 'DevPass123', 'ACTIVE', NULL, NULL, '2025-09-09 10:44:30', '2025-09-09 10:48:54'),
(2, NULL, 'Vendor 001', 'vendor@bkash.test', '', NULL, NULL, NULL, 'VENDOR', 'LOCAL', 'SYSTEM', 'VendorPass123', 'ACTIVE', NULL, NULL, '2025-09-09 10:45:58', '2025-09-09 10:45:58'),
(3, '2009', 'Rohan', 'rohan@gmail.com', '018844054147', NULL, NULL, NULL, 'EMPLOYEE', 'LOCAL', 'SYSTEM', 'arena123', 'ACTIVE', 8, NULL, '2025-09-09 10:47:43', '2025-09-10 09:04:28'),
(4, '2006', 'Subhrajyoti Halder', 'halder.subhrajyoti@bkash.com', '01723352188', 'IT Governance', 'Assistant Lead Engineer', 'Product & Technology', 'EMPLOYEE', 'SSO', 'SYSTEM', NULL, 'ACTIVE', 5, NULL, '2025-09-09 11:06:47', '2025-09-10 05:59:22'),
(5, '2136', 'Hasib Ahmed Rizvi', 'hasib.rizvi@bkash.com', '01710954941', 'Project & Partner Management', 'Manager', 'Product & Technology', 'EMPLOYEE', 'SSO', 'SYSTEM', NULL, 'ACTIVE', 6, NULL, '2025-09-09 11:06:47', '2025-09-10 05:48:29'),
(6, '0821', 'Tahmid Farabi', 'tahmid.farabi@bkash.com', '01603006363', 'Project & Partner Management', 'Assistant Manager', 'Product & Technology', 'EMPLOYEE', 'SSO', 'SYSTEM', NULL, 'ACTIVE', NULL, NULL, '2025-09-09 11:06:47', '2025-09-09 11:06:47'),
(7, '', 'OSR SCAN', 'osr.scan@bkash.com', '', '', '', '', 'EMPLOYEE', 'SSO', 'SYSTEM', NULL, 'ACTIVE', 6, NULL, '2025-09-09 11:06:47', '2025-09-19 07:29:25'),
(8, '2024', 'Employee1', 'employee@bkash.test', '01844054147', NULL, NULL, NULL, 'EMPLOYEE', 'LOCAL', 'SYSTEM', 'EmployeePass123', 'ACTIVE', 0, NULL, '2025-09-09 12:45:40', '2025-09-14 09:32:39'),
(9, '2025', 'Apou', 'apoudatto6@gmail.com', '01838737333', NULL, NULL, NULL, 'ADMIN', 'LOCAL', 'SYSTEM', 'arena123', 'ACTIVE', 3, NULL, '2025-09-11 05:37:27', '2025-09-11 06:16:39'),
(10, NULL, 'test', 'apou@gmail.com', '01838737333', NULL, NULL, NULL, 'VENDOR', 'LOCAL', 'SYSTEM', '123456', 'ACTIVE', NULL, '$2y$10$TRgjtNO4LtWs595dwtDvG.E.5Zb6/8bQ94wCQ9yz88ikfy4ZPgpD.', '2025-09-19 07:28:25', '2025-09-19 07:29:08');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `user_id`, `role_id`) VALUES
(123, 4, 3),
(130, 3, 3),
(124, 5, 3),
(17, 1, 1),
(121, 2, 4),
(138, 9, 2),
(125, 7, 3),
(126, 6, 3),
(133, 8, 3),
(140, 10, 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `approval_flows`
--
ALTER TABLE `approval_flows`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `approval_steps`
--
ALTER TABLE `approval_steps`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cafeterias`
--
ALTER TABLE `cafeterias`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cutoff_times`
--
ALTER TABLE `cutoff_times`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cutoff_times_meal_type_id_foreign` (`meal_type_id`);

--
-- Indexes for table `employment_types`
--
ALTER TABLE `employment_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meal_approvals`
--
ALTER TABLE `meal_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `meal_approvals_subscription_id_foreign` (`subs_id`),
  ADD KEY `meal_approvals_approved_by_foreign` (`approved_by`),
  ADD KEY `idx_meal_approvals_sub` (`subs_id`);

--
-- Indexes for table `meal_cards`
--
ALTER TABLE `meal_cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_meal_card_code` (`card_code`),
  ADD KEY `idx_meal_card_user_id` (`user_id`),
  ADD KEY `idx_meal_card_employee_id` (`employee_id`);

--
-- Indexes for table `meal_contributions`
--
ALTER TABLE `meal_contributions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `meal_contributions_meal_type_id_foreign` (`meal_type_id`),
  ADD KEY `meal_contributions_cafeteria_id_foreign` (`cafeteria_id`);

--
-- Indexes for table `meal_costs`
--
ALTER TABLE `meal_costs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `meal_costs_cafeteria_id_foreign` (`cafeteria_id`),
  ADD KEY `meal_costs_meal_type_id_foreign` (`meal_type_id`);

--
-- Indexes for table `meal_reference`
--
ALTER TABLE `meal_reference`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meal_subscriptions`
--
ALTER TABLE `meal_subscriptions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meal_tokens`
--
ALTER TABLE `meal_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_code` (`token_code`),
  ADD KEY `meal_tokens_user_id_foreign` (`user_id`),
  ADD KEY `meal_tokens_meal_type_id_foreign` (`meal_type_id`),
  ADD KEY `meal_tokens_cafeteria_id_foreign` (`cafeteria_id`);

--
-- Indexes for table `meal_types`
--
ALTER TABLE `meal_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `occasions`
--
ALTER TABLE `occasions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_permissions_name` (`name`);

--
-- Indexes for table `public_holidays`
--
ALTER TABLE `public_holidays`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ramadan_config`
--
ALTER TABLE `ramadan_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `remarks`
--
ALTER TABLE `remarks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rp_role` (`role_id`),
  ADD KEY `rp_perm` (`permission_id`);

--
-- Indexes for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_msisdn_created` (`msisdn`,`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_users_email` (`email`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_roles_user_id_foreign` (`user_id`),
  ADD KEY `user_roles_role_id_foreign` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `approval_flows`
--
ALTER TABLE `approval_flows`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `approval_steps`
--
ALTER TABLE `approval_steps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `cafeterias`
--
ALTER TABLE `cafeterias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `cutoff_times`
--
ALTER TABLE `cutoff_times`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employment_types`
--
ALTER TABLE `employment_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `meal_approvals`
--
ALTER TABLE `meal_approvals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meal_cards`
--
ALTER TABLE `meal_cards`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `meal_contributions`
--
ALTER TABLE `meal_contributions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `meal_costs`
--
ALTER TABLE `meal_costs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `meal_reference`
--
ALTER TABLE `meal_reference`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `meal_subscriptions`
--
ALTER TABLE `meal_subscriptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `meal_tokens`
--
ALTER TABLE `meal_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `meal_types`
--
ALTER TABLE `meal_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `occasions`
--
ALTER TABLE `occasions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=145;

--
-- AUTO_INCREMENT for table `public_holidays`
--
ALTER TABLE `public_holidays`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `ramadan_config`
--
ALTER TABLE `ramadan_config`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `remarks`
--
ALTER TABLE `remarks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=787;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `meal_cards`
--
ALTER TABLE `meal_cards`
  ADD CONSTRAINT `fk_meal_card_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
