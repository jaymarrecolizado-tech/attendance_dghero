-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 12, 2025 at 01:16 AM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u872883517_dbissp`
--

-- --------------------------------------------------------

--
-- Table structure for table `action_logs`
--

CREATE TABLE `action_logs` (
  `id` bigint(20) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `detail` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `action_logs`
--

INSERT INTO `action_logs` (`id`, `admin_id`, `action`, `detail`, `created_at`) VALUES
(1, NULL, 'email_sent', '{\"to\":\"qa2100849569@example.com\"}', '2025-11-16 01:50:28'),
(2, NULL, 'email_sent', '{\"to\":\"jay.galil619@gmail.com\"}', '2025-11-16 01:53:21'),
(3, 1, 'login_success', '[]', '2025-11-16 01:56:02'),
(4, 1, 'login_success', '[]', '2025-11-16 01:56:36'),
(5, NULL, 'email_sent', '{\"to\":\"qa35040128@example.com\"}', '2025-11-16 01:59:11'),
(6, NULL, 'email_sent', '{\"to\":\"jay.galil619@gmail.com\"}', '2025-11-16 02:03:00'),
(7, 1, 'login_success', '[]', '2025-11-16 02:58:01'),
(8, 1, 'export_attendance_csv', '[]', '2025-11-16 02:58:55'),
(9, 1, 'login_success', '[]', '2025-11-16 03:13:51'),
(10, 1, 'login_success', '[]', '2025-11-16 03:34:02'),
(11, 1, 'login_success', '[]', '2025-11-16 03:38:14'),
(12, 1, 'login_success', '[]', '2025-11-16 05:08:35'),
(13, 1, 'login_success', '[]', '2025-11-16 05:09:07'),
(14, 1, 'login_success', '[]', '2025-11-16 05:13:01'),
(15, 1, 'login_success', '[]', '2025-11-16 05:15:31'),
(16, 1, 'login_success', '[]', '2025-11-16 05:22:33'),
(17, 1, 'login_success', '[]', '2025-11-16 05:25:48'),
(18, 1, 'login_success', '[]', '2025-11-16 05:28:15'),
(19, 1, 'signature_view', '{\"attendance_id\":16}', '2025-11-16 05:28:22'),
(20, 1, 'signature_view', '{\"attendance_id\":12}', '2025-11-16 05:28:22'),
(21, 1, 'signature_view', '{\"attendance_id\":13}', '2025-11-16 05:28:22'),
(22, 1, 'signature_view', '{\"attendance_id\":15}', '2025-11-16 05:28:22'),
(23, 1, 'signature_view', '{\"attendance_id\":14}', '2025-11-16 05:28:22'),
(24, 1, 'signature_view', '{\"attendance_id\":11}', '2025-11-16 05:28:22'),
(25, 1, 'signature_view', '{\"attendance_id\":22}', '2025-11-16 05:28:22'),
(26, 1, 'signature_view', '{\"attendance_id\":21}', '2025-11-16 05:28:22'),
(27, 1, 'signature_view', '{\"attendance_id\":20}', '2025-11-16 05:28:22'),
(28, 1, 'signature_view', '{\"attendance_id\":19}', '2025-11-16 05:28:22'),
(29, 1, 'signature_view', '{\"attendance_id\":18}', '2025-11-16 05:28:22'),
(30, 1, 'signature_view', '{\"attendance_id\":17}', '2025-11-16 05:28:22'),
(31, 1, 'signature_view', '{\"attendance_id\":22}', '2025-11-16 05:34:18'),
(32, 1, 'signature_view', '{\"attendance_id\":21}', '2025-11-16 05:34:18'),
(33, 1, 'signature_view', '{\"attendance_id\":20}', '2025-11-16 05:34:18'),
(34, 1, 'signature_view', '{\"attendance_id\":19}', '2025-11-16 05:34:18'),
(35, 1, 'signature_view', '{\"attendance_id\":18}', '2025-11-16 05:34:18'),
(36, 1, 'signature_view', '{\"attendance_id\":17}', '2025-11-16 05:34:18'),
(37, 1, 'signature_view', '{\"attendance_id\":16}', '2025-11-16 05:34:18'),
(38, 1, 'signature_view', '{\"attendance_id\":15}', '2025-11-16 05:34:18'),
(39, 1, 'signature_view', '{\"attendance_id\":14}', '2025-11-16 05:34:18'),
(40, 1, 'signature_view', '{\"attendance_id\":13}', '2025-11-16 05:34:19'),
(41, 1, 'signature_view', '{\"attendance_id\":12}', '2025-11-16 05:34:19'),
(42, 1, 'signature_view', '{\"attendance_id\":11}', '2025-11-16 05:34:19'),
(43, 1, 'login_success', '[]', '2025-11-16 05:39:07'),
(44, 1, 'signature_view', '{\"attendance_id\":15}', '2025-11-16 05:39:14'),
(45, 1, 'signature_view', '{\"attendance_id\":16}', '2025-11-16 05:39:14'),
(46, 1, 'signature_view', '{\"attendance_id\":14}', '2025-11-16 05:39:14'),
(47, 1, 'signature_view', '{\"attendance_id\":22}', '2025-11-16 05:39:14'),
(48, 1, 'signature_view', '{\"attendance_id\":21}', '2025-11-16 05:39:14'),
(49, 1, 'signature_view', '{\"attendance_id\":20}', '2025-11-16 05:39:14'),
(50, 1, 'signature_view', '{\"attendance_id\":19}', '2025-11-16 05:39:14'),
(51, 1, 'signature_view', '{\"attendance_id\":18}', '2025-11-16 05:39:14'),
(52, 1, 'signature_view', '{\"attendance_id\":17}', '2025-11-16 05:39:14'),
(53, 1, 'signature_view', '{\"attendance_id\":13}', '2025-11-16 05:39:14'),
(54, 1, 'signature_view', '{\"attendance_id\":12}', '2025-11-16 05:39:14'),
(55, 1, 'signature_view', '{\"attendance_id\":11}', '2025-11-16 05:39:14'),
(56, 1, 'login_success', '[]', '2025-11-16 05:39:50'),
(57, 1, 'signature_view', '{\"attendance_id\":22}', '2025-11-16 05:39:54'),
(58, 1, 'signature_view', '{\"attendance_id\":21}', '2025-11-16 05:39:54'),
(59, 1, 'signature_view', '{\"attendance_id\":20}', '2025-11-16 05:39:54'),
(60, 1, 'signature_view', '{\"attendance_id\":19}', '2025-11-16 05:39:54'),
(61, 1, 'signature_view', '{\"attendance_id\":18}', '2025-11-16 05:39:54'),
(62, 1, 'signature_view', '{\"attendance_id\":17}', '2025-11-16 05:39:54'),
(63, 1, 'signature_view', '{\"attendance_id\":16}', '2025-11-16 05:39:54'),
(64, 1, 'signature_view', '{\"attendance_id\":15}', '2025-11-16 05:39:54'),
(65, 1, 'signature_view', '{\"attendance_id\":14}', '2025-11-16 05:39:54'),
(66, 1, 'signature_view', '{\"attendance_id\":13}', '2025-11-16 05:39:55'),
(67, 1, 'signature_view', '{\"attendance_id\":12}', '2025-11-16 05:39:55'),
(68, 1, 'signature_view', '{\"attendance_id\":11}', '2025-11-16 05:39:55'),
(69, 1, 'signature_view', '{\"attendance_id\":14}', '2025-11-16 05:41:17'),
(70, 1, 'signature_view', '{\"attendance_id\":15}', '2025-11-16 05:41:17'),
(71, 1, 'signature_view', '{\"attendance_id\":16}', '2025-11-16 05:41:17'),
(72, 1, 'signature_view', '{\"attendance_id\":22}', '2025-11-16 05:41:17'),
(73, 1, 'signature_view', '{\"attendance_id\":21}', '2025-11-16 05:41:17'),
(74, 1, 'signature_view', '{\"attendance_id\":20}', '2025-11-16 05:41:17'),
(75, 1, 'signature_view', '{\"attendance_id\":19}', '2025-11-16 05:41:17'),
(76, 1, 'signature_view', '{\"attendance_id\":18}', '2025-11-16 05:41:17'),
(77, 1, 'signature_view', '{\"attendance_id\":17}', '2025-11-16 05:41:17'),
(78, 1, 'signature_view', '{\"attendance_id\":13}', '2025-11-16 05:41:17'),
(79, 1, 'signature_view', '{\"attendance_id\":12}', '2025-11-16 05:41:17'),
(80, 1, 'signature_view', '{\"attendance_id\":11}', '2025-11-16 05:41:17'),
(81, 1, 'signature_view', '{\"attendance_id\":16}', '2025-11-16 05:50:18'),
(82, 1, 'signature_view', '{\"attendance_id\":17}', '2025-11-16 05:50:18'),
(83, 1, 'signature_view', '{\"attendance_id\":18}', '2025-11-16 05:50:18'),
(84, 1, 'signature_view', '{\"attendance_id\":15}', '2025-11-16 05:50:18'),
(85, 1, 'signature_view', '{\"attendance_id\":23}', '2025-11-16 05:50:18'),
(86, 1, 'signature_view', '{\"attendance_id\":22}', '2025-11-16 05:50:18'),
(87, 1, 'signature_view', '{\"attendance_id\":21}', '2025-11-16 05:50:18'),
(88, 1, 'signature_view', '{\"attendance_id\":20}', '2025-11-16 05:50:18'),
(89, 1, 'signature_view', '{\"attendance_id\":19}', '2025-11-16 05:50:18'),
(90, 1, 'signature_view', '{\"attendance_id\":14}', '2025-11-16 05:50:18'),
(91, 1, 'signature_view', '{\"attendance_id\":13}', '2025-11-16 05:50:18'),
(92, 1, 'signature_view', '{\"attendance_id\":12}', '2025-11-16 05:50:19'),
(93, 1, 'signature_view', '{\"attendance_id\":11}', '2025-11-16 05:50:19'),
(94, 1, 'signature_view', '{\"attendance_id\":17}', '2025-11-16 05:53:57'),
(95, 1, 'signature_view', '{\"attendance_id\":15}', '2025-11-16 05:53:57'),
(96, 1, 'signature_view', '{\"attendance_id\":16}', '2025-11-16 05:53:57'),
(97, 1, 'signature_view', '{\"attendance_id\":23}', '2025-11-16 05:53:57'),
(98, 1, 'signature_view', '{\"attendance_id\":22}', '2025-11-16 05:53:57'),
(99, 1, 'signature_view', '{\"attendance_id\":21}', '2025-11-16 05:53:57'),
(100, 1, 'signature_view', '{\"attendance_id\":20}', '2025-11-16 05:53:57'),
(101, 1, 'signature_view', '{\"attendance_id\":19}', '2025-11-16 05:53:57'),
(102, 1, 'signature_view', '{\"attendance_id\":18}', '2025-11-16 05:53:57'),
(103, 1, 'signature_view', '{\"attendance_id\":14}', '2025-11-16 05:53:57'),
(104, 1, 'signature_view', '{\"attendance_id\":13}', '2025-11-16 05:53:57'),
(105, 1, 'signature_view', '{\"attendance_id\":12}', '2025-11-16 05:53:57'),
(106, 1, 'signature_view', '{\"attendance_id\":11}', '2025-11-16 05:53:57'),
(107, 1, 'signature_replace', '{\"aid\":23,\"uuid\":\"219792d5-56ba-450e-a2c9-2f5590e6c13f\",\"ip\":\"127.0.0.1\"}', '2025-11-16 05:54:05'),
(108, 1, 'signature_view', '{\"attendance_id\":23}', '2025-11-16 05:54:06'),
(109, 1, 'signature_replace', '{\"aid\":22,\"uuid\":\"219792d5-56ba-450e-a2c9-2f5590e6c13f\",\"ip\":\"127.0.0.1\"}', '2025-11-16 05:54:19'),
(110, 1, 'signature_view', '{\"attendance_id\":22}', '2025-11-16 05:54:19'),
(111, 1, 'signature_replace', '{\"aid\":21,\"uuid\":\"f91ad2a1-5d60-4592-aa77-88be68f9f5ed\",\"ip\":\"127.0.0.1\"}', '2025-11-16 05:54:30'),
(112, 1, 'signature_view', '{\"attendance_id\":21}', '2025-11-16 05:54:30'),
(113, 1, 'signature_replace', '{\"aid\":20,\"uuid\":\"258a6518-c732-4932-8cff-86ab10499d9c\",\"ip\":\"127.0.0.1\"}', '2025-11-16 05:56:06'),
(114, 1, 'signature_view', '{\"attendance_id\":20}', '2025-11-16 05:56:06'),
(115, 1, 'signature_replace', '{\"aid\":19,\"uuid\":\"c5da065e-74de-45e5-aff7-eed497496fbe\",\"ip\":\"127.0.0.1\"}', '2025-11-16 05:56:13'),
(116, 1, 'signature_view', '{\"attendance_id\":19}', '2025-11-16 05:56:13'),
(117, 1, 'signature_replace', '{\"aid\":18,\"uuid\":\"17c87521-be57-4496-ad71-dc210e26ec79\",\"ip\":\"127.0.0.1\"}', '2025-11-16 05:56:19'),
(118, 1, 'signature_view', '{\"attendance_id\":18}', '2025-11-16 05:56:19'),
(119, 1, 'signature_replace', '{\"aid\":17,\"uuid\":\"12784385-b3a2-4931-8b51-bd681feec187\",\"ip\":\"127.0.0.1\"}', '2025-11-16 05:56:28'),
(120, 1, 'signature_view', '{\"attendance_id\":17}', '2025-11-16 05:56:28'),
(121, 1, 'signature_replace', '{\"aid\":16,\"uuid\":\"be2630a7-cefb-4ae8-b5e5-82a64a9fa3c4\",\"ip\":\"127.0.0.1\"}', '2025-11-16 05:57:38'),
(122, 1, 'signature_view', '{\"attendance_id\":16}', '2025-11-16 05:57:38'),
(123, 1, 'signature_replace', '{\"aid\":15,\"uuid\":\"3a9f6c54-7a53-46b7-b17e-05a88e679fcb\",\"ip\":\"127.0.0.1\"}', '2025-11-16 05:57:45'),
(124, 1, 'signature_view', '{\"attendance_id\":15}', '2025-11-16 05:57:45'),
(125, 1, 'signature_replace', '{\"aid\":14,\"uuid\":\"dac03352-9da6-419b-83a5-96feb09edafa\",\"ip\":\"127.0.0.1\"}', '2025-11-16 05:57:51'),
(126, 1, 'signature_view', '{\"attendance_id\":14}', '2025-11-16 05:57:51'),
(127, 1, 'signature_replace', '{\"aid\":13,\"uuid\":\"d84657eb-9de6-413c-a083-6ce5171fc4f0\",\"ip\":\"127.0.0.1\"}', '2025-11-16 05:58:40'),
(128, 1, 'signature_view', '{\"attendance_id\":13}', '2025-11-16 05:58:40'),
(129, 1, 'signature_replace', '{\"aid\":12,\"uuid\":\"779e29f9-3acd-43e4-9896-f87e2418eee7\",\"ip\":\"127.0.0.1\"}', '2025-11-16 05:58:47'),
(130, 1, 'signature_view', '{\"attendance_id\":12}', '2025-11-16 05:58:47'),
(131, 1, 'signature_replace', '{\"aid\":11,\"uuid\":\"31ee4139-c7db-44c9-9ced-4bcf3d164723\",\"ip\":\"127.0.0.1\"}', '2025-11-16 05:58:53'),
(132, 1, 'signature_view', '{\"attendance_id\":11}', '2025-11-16 05:58:53'),
(133, 1, 'login_success', '[]', '2025-11-16 06:08:36'),
(134, 1, 'login_success', '[]', '2025-11-16 06:44:08'),
(135, 1, 'login_success', '[]', '2025-11-16 06:47:28'),
(136, 1, 'login_success', '[]', '2025-11-16 06:53:31'),
(137, 1, 'login_success', '[]', '2025-11-16 07:05:16'),
(138, 1, 'login_success', '[]', '2025-11-16 07:06:09'),
(139, NULL, 'login_failed', '{\"username\":\"reaper\"}', '2025-11-16 07:09:20'),
(140, 1, 'login_success', '[]', '2025-11-16 07:09:30'),
(141, 1, 'login_success', '[]', '2025-11-16 07:11:35'),
(142, 1, 'signature_view', '{\"attendance_id\":15}', '2025-11-16 07:21:51'),
(143, 1, 'signature_view', '{\"attendance_id\":16}', '2025-11-16 07:21:51'),
(144, 1, 'signature_view', '{\"attendance_id\":13}', '2025-11-16 07:21:51'),
(145, 1, 'signature_view', '{\"attendance_id\":12}', '2025-11-16 07:21:51'),
(146, 1, 'signature_view', '{\"attendance_id\":11}', '2025-11-16 07:21:51'),
(147, 1, 'signature_view', '{\"attendance_id\":18}', '2025-11-16 07:21:51'),
(148, 1, 'signature_view', '{\"attendance_id\":17}', '2025-11-16 07:21:52'),
(149, 1, 'signature_view', '{\"attendance_id\":14}', '2025-11-16 07:21:52'),
(150, 1, 'signature_view', '{\"attendance_id\":23}', '2025-11-16 07:21:52'),
(151, 1, 'signature_view', '{\"attendance_id\":22}', '2025-11-16 07:21:52'),
(152, 1, 'signature_view', '{\"attendance_id\":21}', '2025-11-16 07:21:52'),
(153, 1, 'signature_view', '{\"attendance_id\":20}', '2025-11-16 07:21:52'),
(154, 1, 'signature_view', '{\"attendance_id\":19}', '2025-11-16 07:21:52'),
(155, 1, 'login_success', '[]', '2025-11-16 07:22:01'),
(156, 1, 'login_success', '[]', '2025-11-16 07:35:40'),
(157, 1, 'signature_view', '{\"attendance_id\":23}', '2025-11-16 09:40:27'),
(158, 1, 'signature_view', '{\"attendance_id\":22}', '2025-11-16 09:40:27'),
(159, 1, 'signature_view', '{\"attendance_id\":21}', '2025-11-16 09:40:27'),
(160, 1, 'signature_view', '{\"attendance_id\":20}', '2025-11-16 09:40:27'),
(161, 1, 'signature_view', '{\"attendance_id\":19}', '2025-11-16 09:40:27'),
(162, 1, 'signature_view', '{\"attendance_id\":18}', '2025-11-16 09:40:27'),
(163, 1, 'signature_view', '{\"attendance_id\":17}', '2025-11-16 09:40:28'),
(164, 1, 'signature_view', '{\"attendance_id\":16}', '2025-11-16 09:40:28'),
(165, 1, 'signature_view', '{\"attendance_id\":15}', '2025-11-16 09:40:28'),
(166, 1, 'signature_view', '{\"attendance_id\":14}', '2025-11-16 09:40:28'),
(167, 1, 'signature_view', '{\"attendance_id\":13}', '2025-11-16 09:40:28'),
(168, 1, 'signature_view', '{\"attendance_id\":12}', '2025-11-16 09:40:28'),
(169, 1, 'signature_view', '{\"attendance_id\":11}', '2025-11-16 09:40:28'),
(170, 1, 'login_success', '[]', '2025-11-16 10:00:35'),
(171, 1, 'login_success', '[]', '2025-11-16 10:28:26'),
(172, 1, 'signature_view', '{\"attendance_id\":16}', '2025-11-16 10:32:35'),
(173, 1, 'signature_view', '{\"attendance_id\":17}', '2025-11-16 10:32:35'),
(174, 1, 'signature_view', '{\"attendance_id\":12}', '2025-11-16 10:32:35'),
(175, 1, 'signature_view', '{\"attendance_id\":18}', '2025-11-16 10:32:36'),
(176, 1, 'signature_view', '{\"attendance_id\":14}', '2025-11-16 10:32:36'),
(177, 1, 'signature_view', '{\"attendance_id\":13}', '2025-11-16 10:32:36'),
(178, 1, 'signature_view', '{\"attendance_id\":11}', '2025-11-16 10:32:36'),
(179, 1, 'signature_view', '{\"attendance_id\":15}', '2025-11-16 10:32:36'),
(180, 1, 'signature_view', '{\"attendance_id\":23}', '2025-11-16 10:32:36'),
(181, 1, 'signature_view', '{\"attendance_id\":22}', '2025-11-16 10:32:36'),
(182, 1, 'signature_view', '{\"attendance_id\":21}', '2025-11-16 10:32:36'),
(183, 1, 'signature_view', '{\"attendance_id\":20}', '2025-11-16 10:32:36'),
(184, 1, 'signature_view', '{\"attendance_id\":19}', '2025-11-16 10:32:36'),
(185, 1, 'signature_view', '{\"attendance_id\":23}', '2025-11-16 10:32:41'),
(186, 1, 'signature_view', '{\"attendance_id\":23}', '2025-11-16 10:32:46'),
(187, 1, 'login_success', '[]', '2025-11-16 10:43:37'),
(188, NULL, 'email_sent', '{\"to\":\"aprilalysongarduque@gmail.com\"}', '2025-11-16 11:05:51'),
(189, 1, 'login_success', '[]', '2025-11-16 11:08:26'),
(190, NULL, 'email_sent', '{\"to\":\"alison.abbas@dict.gov.ph\"}', '2025-11-17 06:17:05'),
(191, 1, 'login_success', '[]', '2025-11-17 06:41:36'),
(192, 1, 'login_success', '[]', '2025-11-17 06:45:46'),
(193, 1, 'signature_view', '{\"attendance_id\":25}', '2025-11-17 06:45:50'),
(194, 1, 'signature_view', '{\"attendance_id\":24}', '2025-11-17 06:45:50'),
(195, 1, 'signature_view', '{\"attendance_id\":23}', '2025-11-17 06:45:50'),
(196, 1, 'signature_view', '{\"attendance_id\":22}', '2025-11-17 06:45:50'),
(197, 1, 'signature_view', '{\"attendance_id\":21}', '2025-11-17 06:45:51'),
(198, 1, 'signature_view', '{\"attendance_id\":19}', '2025-11-17 06:45:51'),
(199, 1, 'signature_view', '{\"attendance_id\":20}', '2025-11-17 06:45:51'),
(200, 1, 'signature_view', '{\"attendance_id\":18}', '2025-11-17 06:45:51'),
(201, 1, 'signature_view', '{\"attendance_id\":17}', '2025-11-17 06:45:51'),
(202, 1, 'signature_view', '{\"attendance_id\":16}', '2025-11-17 06:45:51'),
(203, 1, 'signature_view', '{\"attendance_id\":14}', '2025-11-17 06:45:51'),
(204, 1, 'signature_view', '{\"attendance_id\":15}', '2025-11-17 06:45:51'),
(205, 1, 'signature_view', '{\"attendance_id\":13}', '2025-11-17 06:45:51'),
(206, 1, 'signature_view', '{\"attendance_id\":12}', '2025-11-17 06:45:51'),
(207, 1, 'signature_view', '{\"attendance_id\":11}', '2025-11-17 06:45:51'),
(208, 1, 'signature_view', '{\"attendance_id\":13}', '2025-11-17 06:46:08'),
(209, 1, 'login_success', '[]', '2025-11-17 06:48:54'),
(210, 1, 'signature_view', '{\"attendance_id\":25}', '2025-11-17 06:49:03'),
(211, 1, 'signature_view', '{\"attendance_id\":24}', '2025-11-17 06:49:03'),
(212, 1, 'signature_view', '{\"attendance_id\":22}', '2025-11-17 06:49:03'),
(213, 1, 'signature_view', '{\"attendance_id\":12}', '2025-11-17 06:49:04'),
(214, 1, 'signature_view', '{\"attendance_id\":16}', '2025-11-17 06:49:04'),
(215, 1, 'signature_view', '{\"attendance_id\":19}', '2025-11-17 06:49:04'),
(216, 1, 'signature_view', '{\"attendance_id\":17}', '2025-11-17 06:49:04'),
(217, 1, 'signature_view', '{\"attendance_id\":21}', '2025-11-17 06:49:04'),
(218, 1, 'signature_view', '{\"attendance_id\":14}', '2025-11-17 06:49:04'),
(219, 1, 'signature_view', '{\"attendance_id\":18}', '2025-11-17 06:49:04'),
(220, 1, 'signature_view', '{\"attendance_id\":15}', '2025-11-17 06:49:04'),
(221, 1, 'signature_view', '{\"attendance_id\":23}', '2025-11-17 06:49:04'),
(222, 1, 'signature_view', '{\"attendance_id\":20}', '2025-11-17 06:49:04'),
(223, 1, 'signature_view', '{\"attendance_id\":13}', '2025-11-17 06:49:04'),
(224, 1, 'signature_view', '{\"attendance_id\":11}', '2025-11-17 06:49:04'),
(225, 1, 'signature_replace', '{\"aid\":25,\"uuid\":\"ea73dc37-5022-4f63-987b-bf4599033fde\",\"ip\":\"10.10.22.204\"}', '2025-11-17 06:49:30'),
(226, 1, 'signature_view', '{\"attendance_id\":25}', '2025-11-17 06:49:30'),
(227, 1, 'signature_view', '{\"attendance_id\":25}', '2025-11-17 06:50:10'),
(228, 1, 'signature_view', '{\"attendance_id\":25}', '2025-11-17 06:50:17'),
(229, 1, 'signature_view', '{\"attendance_id\":24}', '2025-11-17 06:50:17'),
(230, 1, 'signature_view', '{\"attendance_id\":12}', '2025-11-17 06:50:17'),
(231, 1, 'signature_view', '{\"attendance_id\":22}', '2025-11-17 06:50:17'),
(232, 1, 'signature_view', '{\"attendance_id\":16}', '2025-11-17 06:50:17'),
(233, 1, 'signature_view', '{\"attendance_id\":19}', '2025-11-17 06:50:17'),
(234, 1, 'signature_view', '{\"attendance_id\":17}', '2025-11-17 06:50:17'),
(235, 1, 'signature_view', '{\"attendance_id\":14}', '2025-11-17 06:50:17'),
(236, 1, 'signature_view', '{\"attendance_id\":21}', '2025-11-17 06:50:17'),
(237, 1, 'signature_view', '{\"attendance_id\":18}', '2025-11-17 06:50:17'),
(238, 1, 'signature_view', '{\"attendance_id\":15}', '2025-11-17 06:50:17'),
(239, 1, 'signature_view', '{\"attendance_id\":11}', '2025-11-17 06:50:17'),
(240, 1, 'signature_view', '{\"attendance_id\":23}', '2025-11-17 06:50:17'),
(241, 1, 'signature_view', '{\"attendance_id\":20}', '2025-11-17 06:50:17'),
(242, 1, 'signature_view', '{\"attendance_id\":13}', '2025-11-17 06:50:17'),
(243, 1, 'signature_view', '{\"attendance_id\":25}', '2025-11-16 06:52:34'),
(244, 1, 'signature_view', '{\"attendance_id\":26}', '2025-11-16 06:52:34'),
(245, 1, 'signature_view', '{\"attendance_id\":17}', '2025-11-16 06:52:34'),
(246, 1, 'signature_view', '{\"attendance_id\":22}', '2025-11-16 06:52:34'),
(247, 1, 'signature_view', '{\"attendance_id\":16}', '2025-11-16 06:52:34'),
(248, 1, 'signature_view', '{\"attendance_id\":19}', '2025-11-16 06:52:34'),
(249, 1, 'signature_view', '{\"attendance_id\":21}', '2025-11-16 06:52:34'),
(250, 1, 'signature_view', '{\"attendance_id\":24}', '2025-11-16 06:52:34'),
(251, 1, 'signature_view', '{\"attendance_id\":14}', '2025-11-16 06:52:34'),
(252, 1, 'signature_view', '{\"attendance_id\":18}', '2025-11-16 06:52:34'),
(253, 1, 'signature_view', '{\"attendance_id\":15}', '2025-11-16 06:52:34'),
(254, 1, 'signature_view', '{\"attendance_id\":23}', '2025-11-16 06:52:34'),
(255, 1, 'signature_view', '{\"attendance_id\":20}', '2025-11-16 06:52:34'),
(256, 1, 'signature_view', '{\"attendance_id\":13}', '2025-11-16 06:52:34'),
(257, 1, 'signature_view', '{\"attendance_id\":12}', '2025-11-16 06:52:34'),
(258, 1, 'signature_view', '{\"attendance_id\":11}', '2025-11-16 06:52:34'),
(259, 1, 'signature_view', '{\"attendance_id\":25}', '2025-11-16 06:52:38'),
(260, 1, 'signature_view', '{\"attendance_id\":26}', '2025-11-16 06:52:38'),
(261, 1, 'login_success', '[]', '2025-11-16 06:55:00'),
(262, 1, 'login_success', '[]', '2025-11-18 03:23:19'),
(263, 1, 'signature_view', '{\"attendance_id\":28}', '2025-11-18 03:23:40'),
(264, 1, 'signature_view', '{\"attendance_id\":27}', '2025-11-18 03:23:40'),
(265, 1, 'signature_view', '{\"attendance_id\":22}', '2025-11-18 03:23:40'),
(266, 1, 'signature_view', '{\"attendance_id\":26}', '2025-11-18 03:23:40'),
(267, 1, 'signature_view', '{\"attendance_id\":25}', '2025-11-18 03:23:40'),
(268, 1, 'signature_view', '{\"attendance_id\":21}', '2025-11-18 03:23:40'),
(269, 1, 'signature_view', '{\"attendance_id\":24}', '2025-11-18 03:23:40'),
(270, 1, 'signature_view', '{\"attendance_id\":23}', '2025-11-18 03:23:40'),
(271, 1, 'signature_view', '{\"attendance_id\":20}', '2025-11-18 03:23:40'),
(272, 1, 'signature_view', '{\"attendance_id\":18}', '2025-11-18 03:23:40'),
(273, 1, 'signature_view', '{\"attendance_id\":19}', '2025-11-18 03:23:40'),
(274, 1, 'signature_view', '{\"attendance_id\":17}', '2025-11-18 03:23:40'),
(275, 1, 'signature_view', '{\"attendance_id\":16}', '2025-11-18 03:23:40'),
(276, 1, 'signature_view', '{\"attendance_id\":15}', '2025-11-18 03:23:40'),
(277, 1, 'signature_view', '{\"attendance_id\":14}', '2025-11-18 03:23:40'),
(278, 1, 'signature_view', '{\"attendance_id\":13}', '2025-11-18 03:23:40'),
(279, 1, 'signature_view', '{\"attendance_id\":12}', '2025-11-18 03:23:40'),
(280, 1, 'signature_view', '{\"attendance_id\":11}', '2025-11-18 03:23:40'),
(281, 1, 'signature_replace', '{\"aid\":28,\"uuid\":\"ea73dc37-5022-4f63-987b-bf4599033fde\",\"ip\":\"::1\"}', '2025-11-18 03:23:48'),
(282, 1, 'signature_view', '{\"attendance_id\":28}', '2025-11-18 03:23:48'),
(283, 1, 'signature_replace', '{\"aid\":26,\"uuid\":\"ea73dc37-5022-4f63-987b-bf4599033fde\",\"ip\":\"::1\"}', '2025-11-18 03:24:02'),
(284, 1, 'signature_view', '{\"attendance_id\":26}', '2025-11-18 03:24:02'),
(285, 1, 'signature_view', '{\"attendance_id\":28}', '2025-11-18 03:24:41'),
(286, 1, 'signature_view', '{\"attendance_id\":27}', '2025-11-18 03:24:41'),
(287, 1, 'signature_view', '{\"attendance_id\":25}', '2025-11-18 03:24:41'),
(288, 1, 'signature_view', '{\"attendance_id\":26}', '2025-11-18 03:24:41'),
(289, 1, 'signature_view', '{\"attendance_id\":24}', '2025-11-18 03:24:41'),
(290, 1, 'signature_view', '{\"attendance_id\":23}', '2025-11-18 03:24:41'),
(291, 1, 'signature_view', '{\"attendance_id\":22}', '2025-11-18 03:24:41'),
(292, 1, 'signature_view', '{\"attendance_id\":20}', '2025-11-18 03:24:41'),
(293, 1, 'signature_view', '{\"attendance_id\":21}', '2025-11-18 03:24:41'),
(294, 1, 'signature_view', '{\"attendance_id\":18}', '2025-11-18 03:24:41'),
(295, 1, 'signature_view', '{\"attendance_id\":19}', '2025-11-18 03:24:41'),
(296, 1, 'signature_view', '{\"attendance_id\":17}', '2025-11-18 03:24:41'),
(297, 1, 'signature_view', '{\"attendance_id\":16}', '2025-11-18 03:24:41'),
(298, 1, 'signature_view', '{\"attendance_id\":15}', '2025-11-18 03:24:41'),
(299, 1, 'signature_view', '{\"attendance_id\":14}', '2025-11-18 03:24:41'),
(300, 1, 'signature_view', '{\"attendance_id\":13}', '2025-11-18 03:24:41'),
(301, 1, 'signature_view', '{\"attendance_id\":12}', '2025-11-18 03:24:41'),
(302, 1, 'signature_view', '{\"attendance_id\":11}', '2025-11-18 03:24:41'),
(303, NULL, 'email_sent', '{\"to\":\"jay.galil619@gmail.com\"}', '2025-11-18 03:26:22'),
(304, 1, 'login_success', '[]', '2025-11-18 05:07:25'),
(305, 1, 'signature_view', '{\"attendance_id\":28}', '2025-11-18 05:07:31'),
(306, 1, 'signature_view', '{\"attendance_id\":27}', '2025-11-18 05:07:31'),
(307, 1, 'signature_view', '{\"attendance_id\":26}', '2025-11-18 05:07:31'),
(308, 1, 'signature_view', '{\"attendance_id\":25}', '2025-11-18 05:07:31'),
(309, 1, 'signature_view', '{\"attendance_id\":23}', '2025-11-18 05:07:31'),
(310, 1, 'signature_view', '{\"attendance_id\":24}', '2025-11-18 05:07:31'),
(311, 1, 'signature_view', '{\"attendance_id\":21}', '2025-11-18 05:07:31'),
(312, 1, 'signature_view', '{\"attendance_id\":22}', '2025-11-18 05:07:31'),
(313, 1, 'signature_view', '{\"attendance_id\":20}', '2025-11-18 05:07:31'),
(314, 1, 'signature_view', '{\"attendance_id\":19}', '2025-11-18 05:07:31'),
(315, 1, 'signature_view', '{\"attendance_id\":18}', '2025-11-18 05:07:31'),
(316, 1, 'signature_view', '{\"attendance_id\":17}', '2025-11-18 05:07:31'),
(317, 1, 'signature_view', '{\"attendance_id\":16}', '2025-11-18 05:07:31'),
(318, 1, 'signature_view', '{\"attendance_id\":15}', '2025-11-18 05:07:31'),
(319, 1, 'signature_view', '{\"attendance_id\":14}', '2025-11-18 05:07:31'),
(320, 1, 'signature_view', '{\"attendance_id\":13}', '2025-11-18 05:07:31'),
(321, 1, 'signature_view', '{\"attendance_id\":12}', '2025-11-18 05:07:31'),
(322, 1, 'signature_view', '{\"attendance_id\":11}', '2025-11-18 05:07:31'),
(323, 1, 'signature_view', '{\"attendance_id\":28}', '2025-11-18 05:08:24'),
(324, 1, 'signature_view', '{\"attendance_id\":27}', '2025-11-18 05:08:24'),
(325, 1, 'signature_view', '{\"attendance_id\":26}', '2025-11-18 05:08:24'),
(326, 1, 'signature_view', '{\"attendance_id\":24}', '2025-11-18 05:08:24'),
(327, 1, 'signature_view', '{\"attendance_id\":25}', '2025-11-18 05:08:24'),
(328, 1, 'signature_view', '{\"attendance_id\":22}', '2025-11-18 05:08:24'),
(329, 1, 'signature_view', '{\"attendance_id\":23}', '2025-11-18 05:08:24'),
(330, 1, 'signature_view', '{\"attendance_id\":21}', '2025-11-18 05:08:24'),
(331, 1, 'signature_view', '{\"attendance_id\":20}', '2025-11-18 05:08:24'),
(332, 1, 'signature_view', '{\"attendance_id\":19}', '2025-11-18 05:08:24'),
(333, 1, 'signature_view', '{\"attendance_id\":18}', '2025-11-18 05:08:24'),
(334, 1, 'signature_view', '{\"attendance_id\":17}', '2025-11-18 05:08:24'),
(335, 1, 'signature_view', '{\"attendance_id\":16}', '2025-11-18 05:08:24'),
(336, 1, 'signature_view', '{\"attendance_id\":15}', '2025-11-18 05:08:24'),
(337, 1, 'signature_view', '{\"attendance_id\":14}', '2025-11-18 05:08:24'),
(338, 1, 'signature_view', '{\"attendance_id\":13}', '2025-11-18 05:08:24'),
(339, 1, 'signature_view', '{\"attendance_id\":12}', '2025-11-18 05:08:24'),
(340, 1, 'signature_view', '{\"attendance_id\":11}', '2025-11-18 05:08:24'),
(341, 1, 'login_success', '[]', '2025-11-18 05:40:17'),
(342, 1, 'login_success', '[]', '2025-11-18 05:45:56'),
(343, 1, 'login_success', '[]', '2025-11-18 05:56:20'),
(344, 1, 'login_success', '[]', '2025-11-18 06:04:03'),
(345, 1, 'signature_new', '{\"aid\":29,\"uuid\":\"37f7ef58-d02f-44b1-912b-d09d581a9bd9\",\"date\":\"2025-11-18\",\"ip\":\"::1\"}', '2025-11-18 06:25:21'),
(346, 1, 'signature_view', '{\"attendance_id\":29}', '2025-11-18 06:25:21'),
(347, 1, 'signature_new', '{\"aid\":30,\"uuid\":\"16eefc0b-c428-40bd-be62-009fd88d367b\",\"date\":\"2025-11-18\",\"ip\":\"::1\"}', '2025-11-18 06:25:38'),
(348, 1, 'signature_view', '{\"attendance_id\":30}', '2025-11-18 06:25:38'),
(349, 1, 'signature_view', '{\"attendance_id\":29}', '2025-11-18 06:25:38'),
(350, 1, 'signature_view', '{\"attendance_id\":30}', '2025-11-18 06:26:14'),
(351, 1, 'signature_view', '{\"attendance_id\":30}', '2025-11-18 06:26:18'),
(352, 1, 'signature_view', '{\"attendance_id\":29}', '2025-11-18 06:26:18'),
(353, 1, 'signature_view', '{\"attendance_id\":29}', '2025-11-18 06:26:46'),
(354, 1, 'signature_view', '{\"attendance_id\":30}', '2025-11-18 06:26:46'),
(355, 1, 'login_success', '[]', '2025-11-18 10:34:57'),
(356, 1, 'login_success', '[]', '2025-11-18 10:38:01'),
(357, NULL, 'login_failed', '{\"username\":\"admin\"}', '2025-11-18 22:24:07'),
(358, 1, 'login_success', '[]', '2025-11-18 22:24:16'),
(359, NULL, 'email_sent', '{\"to\":\"jay.gail619@gmail.com\"}', '2025-11-18 22:26:37'),
(360, NULL, 'email_sent', '{\"to\":\"jay.gail619@gmail.com\"}', '2025-11-18 22:31:41'),
(361, NULL, 'email_sent', '{\"to\":\"jay.galil619@gmail.com\"}', '2025-11-18 22:33:06'),
(362, NULL, 'email_sent', '{\"to\":\"jay.recolizado@gmail.com\"}', '2025-11-18 22:44:17'),
(363, NULL, 'email_sent', '{\"to\":\"jay.galil619@gmail.com\"}', '2025-11-18 22:47:16'),
(364, 1, 'login_success', '[]', '2025-11-18 23:04:39'),
(365, 1, 'login_success', '[]', '2025-11-18 23:14:58'),
(366, 1, 'login_success', '[]', '2025-11-18 23:18:03'),
(367, 1, 'login_success', '[]', '2025-11-18 23:22:52'),
(368, 1, 'login_success', '[]', '2025-11-18 23:26:15'),
(369, 1, 'login_success', '[]', '2025-11-18 23:28:29'),
(370, NULL, 'email_sent', '{\"to\":\"jay.galil619@gmail.com\"}', '2025-11-18 23:30:19'),
(371, 1, 'login_success', '[]', '2025-11-18 23:34:14'),
(372, 1, 'signature_replace', '{\"aid\":33,\"uuid\":\"08aaa803-c71c-4832-80c9-0d25addbd129\",\"ip\":\"210.213.157.42\"}', '2025-11-18 23:34:42'),
(373, 1, 'login_success', '[]', '2025-11-18 23:35:55'),
(374, 1, 'login_success', '[]', '2025-11-18 23:41:57'),
(375, 1, 'login_success', '[]', '2025-11-18 23:55:48'),
(376, NULL, 'email_sent', '{\"to\":\"r02cagayan@gmail.com\"}', '2025-11-19 00:07:11'),
(377, NULL, 'email_sent', '{\"to\":\"m.oli@psa.gov.ph\"}', '2025-11-19 00:19:00'),
(378, 1, 'login_success', '[]', '2025-11-19 00:26:44'),
(379, NULL, 'email_sent', '{\"to\":\"edison.agaoid@dict.gov.ph\"}', '2025-11-19 00:26:58'),
(380, NULL, 'email_sent', '{\"to\":\"Anton.ebord24@gmail.com\"}', '2025-11-19 00:31:01'),
(381, NULL, 'email_sent', '{\"to\":\"antoneborde24@gmail.com\"}', '2025-11-19 00:32:32'),
(382, NULL, 'email_sent', '{\"to\":\"giovannipalogan@GMAIL.COM\"}', '2025-11-19 00:34:50'),
(383, 1, 'login_success', '[]', '2025-11-19 00:35:10'),
(384, NULL, 'email_sent', '{\"to\":\"mina.villafuerte@dict.gov.ph\"}', '2025-11-19 00:39:32'),
(385, NULL, 'email_sent', '{\"to\":\"ojmasigan@gmail.com\"}', '2025-11-19 00:55:00'),
(386, NULL, 'email_sent', '{\"to\":\"iolanisilvestre@csu.edu.ph\"}', '2025-11-19 02:02:36'),
(387, NULL, 'email_sent', '{\"to\":\"rcijr95@gmail.com\"}', '2025-11-19 02:10:33'),
(388, NULL, 'email_sent', '{\"to\":\"pajanilmarthius@gmail.com\"}', '2025-11-19 02:13:19'),
(389, NULL, 'email_sent', '{\"to\":\"ito.bscbatanes@gmail.com\"}', '2025-11-19 02:18:05'),
(390, 1, 'login_success', '[]', '2025-11-19 02:18:35'),
(391, 1, 'export_attendance_csv', '[]', '2025-11-19 02:24:33'),
(392, 1, 'login_success', '[]', '2025-11-19 02:25:27'),
(393, 1, 'export_attendance_csv', '[]', '2025-11-19 02:25:46'),
(394, 1, 'login_success', '[]', '2025-11-19 03:15:21'),
(395, 1, 'login_success', '[]', '2025-11-19 03:53:17'),
(396, 1, 'login_success', '[]', '2025-11-19 04:08:31'),
(397, 1, 'login_success', '[]', '2025-11-19 04:12:27'),
(398, 1, 'export_attendance_csv', '[]', '2025-11-19 04:13:21'),
(399, 1, 'login_success', '[]', '2025-11-19 05:21:16'),
(400, 1, 'login_success', '[]', '2025-11-19 05:40:25'),
(401, 1, 'login_success', '[]', '2025-11-19 05:43:47'),
(402, 1, 'export_attendance_csv', '[]', '2025-11-19 05:43:55'),
(403, 1, 'login_success', '[]', '2025-11-19 05:50:52'),
(404, 1, 'export_attendance_csv', '[]', '2025-11-19 05:51:34'),
(405, 1, 'login_success', '[]', '2025-11-19 08:31:57'),
(406, 1, 'login_success', '[]', '2025-11-19 08:31:58'),
(407, 1, 'login_success', '[]', '2025-11-19 09:31:26'),
(408, 1, 'login_success', '[]', '2025-11-19 23:14:54'),
(409, 1, 'login_success', '[]', '2025-11-19 23:46:09'),
(410, 1, 'login_success', '[]', '2025-11-19 23:47:11'),
(411, 1, 'login_success', '[]', '2025-11-20 00:03:50'),
(412, 1, 'login_success', '[]', '2025-11-20 00:06:29'),
(413, 1, 'login_success', '[]', '2025-11-20 00:07:37'),
(414, 1, 'login_success', '[]', '2025-11-20 00:41:12'),
(415, NULL, 'email_sent', '{\"to\":\"gie.baculi@dict.gov.ph\"}', '2025-11-20 00:43:49'),
(416, 1, 'login_success', '[]', '2025-11-21 00:24:07'),
(417, 1, 'login_success', '[]', '2025-11-21 01:25:42'),
(418, 1, 'login_success', '[]', '2025-11-21 01:50:16'),
(419, 1, 'login_success', '[]', '2025-11-21 01:57:30'),
(420, 1, 'login_success', '[]', '2025-11-21 06:41:13'),
(421, 1, 'login_success', '[]', '2025-11-21 06:47:12'),
(422, 1, 'export_attendance_csv', '[]', '2025-11-21 06:47:36'),
(423, 1, 'export_registrants_csv', '[]', '2025-11-21 06:47:56'),
(424, 1, 'login_success', '[]', '2025-11-21 06:57:41'),
(425, 1, 'login_success', '[]', '2025-11-21 07:00:20'),
(426, 1, 'export_attendance_csv', '[]', '2025-11-21 07:10:40'),
(427, 1, 'export_attendance_csv', '[]', '2025-11-21 07:14:40'),
(428, 1, 'export_attendance_csv', '[]', '2025-11-21 07:15:15'),
(429, 1, 'export_attendance_csv', '[]', '2025-11-21 07:17:18'),
(430, 1, 'export_attendance_csv', '[]', '2025-11-21 07:17:49'),
(431, NULL, 'login_failed', '{\"username\":\"admim\"}', '2025-11-24 03:15:43'),
(432, NULL, 'login_failed', '{\"username\":\"admim\"}', '2025-11-24 03:15:52'),
(433, 1, 'login_success', '[]', '2025-11-24 03:16:00'),
(434, NULL, 'email_sent', '{\"to\":\"jay.galil619@gmail.com\"}', '2025-11-24 03:40:15'),
(435, 1, 'login_success', '[]', '2025-11-24 04:02:22'),
(436, 1, 'login_success', '[]', '2025-11-24 04:03:23'),
(437, 1, 'login_success', '[]', '2025-11-24 04:11:42'),
(438, 1, 'login_success', '[]', '2025-11-24 04:14:12'),
(439, 1, 'login_success', '[]', '2025-11-24 04:14:38'),
(440, 1, 'login_success', '[]', '2025-11-24 04:17:03'),
(441, 1, 'login_success', '[]', '2025-11-24 04:50:31'),
(442, 1, 'login_success', '[]', '2025-11-24 05:07:29'),
(443, NULL, 'email_sent', '{\"to\":\"leo.alilam@dict.gov.ph\"}', '2025-11-24 06:12:01'),
(444, 1, 'login_success', '[]', '2025-11-24 06:13:50'),
(445, 1, 'login_success', '[]', '2025-11-24 07:43:22'),
(446, 1, 'signature_replace', '{\"aid\":250,\"uuid\":\"0adcc8c8-e23c-48fa-b982-2523f33cd92d\",\"ip\":\"103.167.116.66\"}', '2025-11-24 08:50:02'),
(447, 1, 'signature_replace', '{\"aid\":250,\"uuid\":\"0adcc8c8-e23c-48fa-b982-2523f33cd92d\",\"ip\":\"103.167.116.66\"}', '2025-11-24 10:06:25'),
(448, 1, 'login_success', '[]', '2025-11-24 23:22:26'),
(449, 1, 'login_success', '[]', '2025-11-24 23:27:38'),
(450, 1, 'login_success', '[]', '2025-11-24 23:29:48'),
(451, 1, 'login_success', '[]', '2025-11-24 23:30:46'),
(452, NULL, 'email_sent', '{\"to\":\"joey.d.masirag@gmail.com\"}', '2025-11-24 23:41:47'),
(453, NULL, 'email_sent', '{\"to\":\"ajaysunico@gmail.com\"}', '2025-11-24 23:45:22'),
(454, 1, 'login_success', '[]', '2025-11-24 23:52:03'),
(455, NULL, 'login_failed', '{\"username\":\"admin\"}', '2025-11-24 23:59:51'),
(456, 1, 'login_success', '[]', '2025-11-24 23:59:57'),
(457, NULL, 'email_sent', '{\"to\":\"edison.agaoid@dict.gov.ph\"}', '2025-11-25 00:03:57'),
(458, NULL, 'email_sent', '{\"to\":\"crest.agustin@gmail.com\"}', '2025-11-25 00:06:10'),
(459, 1, 'login_success', '[]', '2025-11-25 00:08:25'),
(460, NULL, 'email_sent', '{\"to\":\"orfel.l.bejarin@isu.edu.ph\"}', '2025-11-25 00:09:12'),
(461, NULL, 'email_sent', '{\"to\":\"janledran19@gmail.com\"}', '2025-11-25 00:14:42'),
(462, NULL, 'email_sent', '{\"to\":\"eric.nuez@yahoo.com\"}', '2025-11-25 00:25:10'),
(463, NULL, 'email_sent', '{\"to\":\"zellefernandezdayson@gmail.com\"}', '2025-11-25 00:33:12'),
(464, NULL, 'email_sent', '{\"to\":\"ems.sol@smsupermalls.com\"}', '2025-11-25 00:34:35'),
(465, NULL, 'email_sent', '{\"to\":\"romedannug@gmail.com\"}', '2025-11-25 00:50:17'),
(466, NULL, 'email_sent', '{\"to\":\"noraldeen.qaddoumi@deped.gov.ph\"}', '2025-11-25 00:54:08'),
(467, 1, 'login_success', '[]', '2025-11-25 00:56:22'),
(468, 1, 'login_success', '[]', '2025-11-25 00:56:31'),
(469, NULL, 'email_sent', '{\"to\":\"lotlotacera@gmail.com\"}', '2025-11-25 01:00:02'),
(470, 1, 'login_success', '[]', '2025-11-25 01:02:28'),
(471, NULL, 'login_failed', '{\"username\":\"aaaa\"}', '2025-11-25 01:02:35'),
(472, NULL, 'email_sent', '{\"to\":\"lguabulugcagayan@yahoo.com\"}', '2025-11-25 01:08:35'),
(473, NULL, 'email_sent', '{\"to\":\"christine.bueno@dict.gov.ph\"}', '2025-11-25 01:10:11'),
(474, NULL, 'email_sent', '{\"to\":\"johanna.tulauan@dict.gov.ph\"}', '2025-11-25 01:12:29'),
(475, NULL, 'email_sent', '{\"to\":\"EXEN.CLARO@DICT.GOV.PH\"}', '2025-11-25 01:15:14'),
(476, NULL, 'email_sent', '{\"to\":\"EXEN.CLARO@DICT.GOV.PH\"}', '2025-11-25 01:15:18'),
(477, NULL, 'email_sent', '{\"to\":\"mocorpuz@amaes.edu.ph\"}', '2025-11-25 01:22:02'),
(478, 1, 'login_success', '[]', '2025-11-25 01:29:40'),
(479, 1, 'login_success', '[]', '2025-11-25 01:30:09'),
(480, 1, 'login_success', '[]', '2025-11-25 01:30:48'),
(481, 1, 'login_success', '[]', '2025-11-25 01:34:43'),
(482, NULL, 'email_sent', '{\"to\":\"christiandale.aguda@dict.gov.ph\"}', '2025-11-25 01:34:53'),
(483, NULL, 'email_sent', '{\"to\":\"JOYCEANNE.URDILLAS@DICT.GOV.PH\"}', '2025-11-25 01:37:24'),
(484, NULL, 'email_sent', '{\"to\":\"joshuahapinat0816@gmai.com\"}', '2025-11-25 01:43:57'),
(485, NULL, 'email_sent', '{\"to\":\"shashacostales17@gmail.com\"}', '2025-11-25 01:51:12'),
(486, 1, 'login_success', '[]', '2025-11-25 01:51:41'),
(487, NULL, 'email_sent', '{\"to\":\"markgportabes@gmail.com\"}', '2025-11-25 02:01:49'),
(488, NULL, 'email_sent', '{\"to\":\"CHRISTIAN.CALDEZ@DICT.GOV.PH\"}', '2025-11-25 02:17:51'),
(489, 1, 'login_success', '[]', '2025-11-25 02:20:05'),
(490, NULL, 'email_sent', '{\"to\":\"kristine.valdez@dict.gov.ph\"}', '2025-11-25 02:26:11'),
(491, NULL, 'email_sent', '{\"to\":\"geefuerte@gmail.com\"}', '2025-11-25 02:28:49'),
(492, 1, 'login_success', '[]', '2025-11-25 02:31:40'),
(493, 1, 'export_attendance_csv', '[]', '2025-11-25 02:31:47'),
(494, 1, 'export_attendance_csv', '[]', '2025-11-25 02:32:01'),
(495, 1, 'login_success', '[]', '2025-11-25 02:33:21'),
(496, 1, 'export_attendance_csv', '[]', '2025-11-25 02:33:28'),
(497, 1, 'login_success', '[]', '2025-11-25 02:47:12'),
(498, 1, 'export_attendance_csv', '[]', '2025-11-25 02:47:18'),
(499, NULL, 'email_sent', '{\"to\":\"cyzione.mendoza@dict.gov.ph\"}', '2025-11-25 03:17:08'),
(500, 1, 'login_success', '[]', '2025-11-25 03:29:56'),
(501, 1, 'export_attendance_csv', '[]', '2025-11-25 03:30:05'),
(502, NULL, 'email_sent', '{\"to\":\"jayson.s.Lightnin@isu.edu.ph\"}', '2025-11-25 04:00:25'),
(503, 1, 'login_success', '[]', '2025-11-25 04:02:07'),
(504, 1, 'login_success', '[]', '2025-11-25 04:14:40'),
(505, NULL, 'email_sent', '{\"to\":\"preciousargonza17@gmail.com\"}', '2025-11-25 04:17:32'),
(506, 1, 'login_success', '[]', '2025-11-25 04:17:51'),
(507, NULL, 'email_sent', '{\"to\":\"gaoiranperlita48@gmail.com\"}', '2025-11-25 05:35:05'),
(508, 1, 'login_success', '[]', '2025-11-25 06:13:12'),
(509, 1, 'login_success', '[]', '2025-12-09 02:13:07'),
(510, 1, 'login_success', '[]', '2025-12-09 03:46:57'),
(511, NULL, 'email_sent', '{\"to\":\"ricavcasuga@gmail.com\"}', '2025-12-09 03:48:20'),
(512, NULL, 'email_sent', '{\"to\":\"aries.guim@dict.gov.ph\"}', '2025-12-09 03:48:57'),
(513, 1, 'login_success', '[]', '2025-12-09 03:50:33'),
(514, NULL, 'login_failed', '{\"username\":\"admin123\"}', '2025-12-09 03:52:22'),
(515, 1, 'login_success', '[]', '2025-12-09 03:52:38'),
(516, NULL, 'email_sent', '{\"to\":\"rheajoycarascon22@gmail.com\"}', '2025-12-09 03:56:32'),
(517, NULL, 'email_sent', '{\"to\":\"christinebullongan26@gmail.com\"}', '2025-12-09 03:59:11'),
(518, NULL, 'email_sent', '{\"to\":\"marlauberita2@gmail.com\"}', '2025-12-09 04:00:15'),
(519, NULL, 'email_sent', '{\"to\":\"madambarenz22@gmail.com\"}', '2025-12-09 04:01:11'),
(520, NULL, 'email_sent', '{\"to\":\"ulibasbryan@gmail.com\"}', '2025-12-09 04:01:13'),
(521, NULL, 'email_sent', '{\"to\":\"gabatinow17@gmail.com\"}', '2025-12-09 04:01:52'),
(522, NULL, 'email_sent', '{\"to\":\"martinbien1107@gmail.com\"}', '2025-12-09 04:01:54'),
(523, NULL, 'email_sent', '{\"to\":\"albertjrgselga2002@gmail.com\"}', '2025-12-09 05:12:24'),
(524, NULL, 'email_sent', '{\"to\":\"niloamorsolo@gmail.com\"}', '2025-12-09 05:14:15'),
(525, NULL, 'email_sent', '{\"to\":\"columnareyven0@gmail.com\"}', '2025-12-09 05:14:59'),
(526, NULL, 'email_sent', '{\"to\":\"isapshs0076@gmail.com\"}', '2025-12-09 05:15:25'),
(527, NULL, 'email_sent', '{\"to\":\"jordantarubal7@gmail.com\"}', '2025-12-09 05:16:32'),
(528, NULL, 'email_sent', '{\"to\":\"biendonelbadua04@gmail.com\"}', '2025-12-09 05:17:20'),
(529, NULL, 'email_sent', '{\"to\":\"whynezelp@gmail.com\"}', '2025-12-09 05:18:43'),
(530, NULL, 'email_sent', '{\"to\":\"johnkristoffcarino@gmail.com\"}', '2025-12-09 05:21:31'),
(531, NULL, 'email_sent', '{\"to\":\"neilkristopher.guimmayen@dict.gov.ph\"}', '2025-12-09 05:32:57'),
(532, NULL, 'email_sent', '{\"to\":\"marcandrei816@gmail.com\"}', '2025-12-09 05:44:27'),
(533, NULL, 'email_sent', '{\"to\":\"martinezdylan553@gmail.com\"}', '2025-12-09 05:45:39'),
(534, NULL, 'email_sent', '{\"to\":\"natanielmarcos11@gmail.com\"}', '2025-12-09 05:45:57'),
(535, NULL, 'email_sent', '{\"to\":\"kurtcaseymaddara8@gmail.com\"}', '2025-12-09 05:48:40'),
(536, NULL, 'email_sent', '{\"to\":\"kian.pauig31@gmail.com\"}', '2025-12-09 05:49:14'),
(537, NULL, 'email_sent', '{\"to\":\"judithreymundo03@gmail.com\"}', '2025-12-09 05:50:35'),
(538, 1, 'login_success', '[]', '2025-12-09 05:54:25'),
(539, NULL, 'email_sent', '{\"to\":\"calojoey014@gmail.com\"}', '2025-12-09 05:55:09'),
(540, NULL, 'email_sent', '{\"to\":\"anogbea3@gmail.com\"}', '2025-12-09 06:19:02'),
(541, NULL, 'email_sent', '{\"to\":\"gammadcassandra@gmail.com\"}', '2025-12-09 06:32:50'),
(542, NULL, 'email_sent', '{\"to\":\"jamiliamq@gmail.com\"}', '2025-12-09 06:34:19'),
(543, NULL, 'email_sent', '{\"to\":\"shikynaancheta2021@gmail.com\"}', '2025-12-09 06:35:51'),
(544, NULL, 'email_sent', '{\"to\":\"shikynaancheta2021u@gmail.com\"}', '2025-12-09 06:42:05'),
(545, NULL, 'email_sent', '{\"to\":\"mgsolita011@gmai.com\"}', '2025-12-09 06:45:51'),
(546, NULL, 'email_sent', '{\"to\":\"judesantilla30@gmail.com\"}', '2025-12-09 06:47:25'),
(547, NULL, 'email_sent', '{\"to\":\"riverajustinenicole1@gmail.com\"}', '2025-12-09 06:47:34'),
(548, NULL, 'email_sent', '{\"to\":\"roel.jimenez@dict.gov.ph\"}', '2025-12-09 06:50:48'),
(549, NULL, 'email_sent', '{\"to\":\"christopher.capili@dict.gov.ph\"}', '2025-12-09 07:21:08'),
(550, NULL, 'email_sent', '{\"to\":\"yancee.rafer1@dict.gov.ph\"}', '2025-12-09 07:27:09'),
(551, NULL, 'email_sent', '{\"to\":\"shane.cepeda1@dict.gov.ph\"}', '2025-12-09 07:28:39'),
(552, NULL, 'email_sent', '{\"to\":\"rito.banan1@gmail.com\"}', '2025-12-09 07:30:13'),
(553, NULL, 'email_sent', '{\"to\":\"vlad.viktorr@gmail.com\"}', '2025-12-09 07:31:15'),
(554, NULL, 'email_sent', '{\"to\":\"jei.jimenez@gmail.com\"}', '2025-12-09 07:33:32'),
(555, NULL, 'email_sent', '{\"to\":\"kyle.suyu@gmail.com\"}', '2025-12-09 07:35:45'),
(556, NULL, 'email_sent', '{\"to\":\"brentramoranorti1@gmail.com\"}', '2025-12-09 07:39:32'),
(557, NULL, 'email_sent', '{\"to\":\"kryssfranchezka151@gmail.com\"}', '2025-12-09 07:40:33'),
(558, NULL, 'email_sent', '{\"to\":\"janelav021@gmail.com\"}', '2025-12-09 07:41:34'),
(559, NULL, 'email_sent', '{\"to\":\"khaylaperez214@gmail.com\"}', '2025-12-09 07:42:38'),
(560, NULL, 'email_sent', '{\"to\":\"magmanlackarljhun23@gmail.com\"}', '2025-12-09 07:43:41'),
(561, 1, 'export_registrants_csv', '[]', '2025-12-09 07:49:07'),
(562, 1, 'login_success', '[]', '2025-12-09 07:54:04'),
(563, 1, 'export_attendance_csv', '[]', '2025-12-09 07:58:36'),
(564, 1, 'export_attendance_csv', '[]', '2025-12-09 08:02:18'),
(565, 1, 'export_registrants_csv', '[]', '2025-12-09 08:04:04'),
(566, 1, 'export_attendance_csv', '[]', '2025-12-09 08:04:52'),
(567, 1, 'login_success', '[]', '2025-12-09 08:06:34'),
(568, 1, 'export_attendance_csv', '[]', '2025-12-09 08:06:38'),
(569, 1, 'export_registrants_csv', '[]', '2025-12-09 08:07:35');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `email`, `created_at`) VALUES
(1, 'admin', '$2y$10$NDrc7k5J8Ze7NpgliqQNxOexnRbKLoTqhN3r5fCfctw3bZQd9ctnW', NULL, '2025-11-15 23:56:53');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `participant_id` bigint(20) UNSIGNED NOT NULL,
  `attendance_date` date NOT NULL,
  `time_in` time NOT NULL,
  `signature_path` varchar(255) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `purpose` varchar(20) NOT NULL DEFAULT 'standard'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `participant_id`, `attendance_date`, `time_in`, `signature_path`, `event_id`, `created_at`, `purpose`) VALUES
(348, 667, '2025-11-25', '05:51:23', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/d3642dcf-b817-47dd-8d63-890bb0e6bd95_1764049883.png', 3, '2025-11-25 05:51:23', 'standard'),
(347, 668, '2025-11-25', '05:50:51', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/bf3c8c52-3289-4cff-8484-f116a5487a50_1764049851.png', 3, '2025-11-25 05:50:51', 'standard'),
(346, 666, '2025-11-25', '05:49:45', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/e0902a83-b659-405b-99c6-2783730c3e24_1764049785.png', 3, '2025-11-25 05:49:45', 'standard'),
(345, 559, '2025-11-25', '05:25:01', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/995ca404-2020-49e8-8dd0-b32810cb3b85_1764048301.png', 3, '2025-11-25 05:25:01', 'standard'),
(344, 580, '2025-11-25', '05:23:30', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/c8b491a1-a278-47e7-bdc9-07940b9b9cc9_1764048210.png', 3, '2025-11-25 05:23:30', 'standard'),
(343, 553, '2025-11-25', '05:20:17', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/395c0413-9c17-4891-b667-479dceca07c1_1764048017.png', 3, '2025-11-25 05:20:17', 'standard'),
(342, 659, '2025-11-25', '04:18:38', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/4048ec8a-0e75-4f7b-aa87-26bba3f8c9b7_1764044318.png', 3, '2025-11-25 04:18:38', 'standard'),
(341, 665, '2025-11-25', '04:18:21', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/84d29adf-d319-4d31-8960-f7166b173fc5_1764044301.png', 3, '2025-11-25 04:18:21', 'standard'),
(340, 664, '2025-11-25', '04:15:39', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/290ab328-b59c-4329-9dbe-88f62742cc78_1764044139.png', 3, '2025-11-25 04:15:39', 'standard'),
(339, 663, '2025-11-25', '04:04:06', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/2ea774fb-4d6b-47fb-aed7-96d40f127b85_1764043446.png', 3, '2025-11-25 04:04:06', 'standard'),
(338, 662, '2025-11-25', '03:18:14', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/c4759978-18c8-4622-8ab6-0ba75bd0b0b8_1764040694.png', 3, '2025-11-25 03:18:14', 'standard'),
(337, 661, '2025-11-25', '02:29:22', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/d3ca1296-564d-4001-a200-5e5a501d692a_1764037762.png', 3, '2025-11-25 02:29:22', 'standard'),
(336, 658, '2025-11-25', '02:18:24', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/94f1eda7-6221-4ef7-a318-0dd89a2d0305_1764037104.png', 3, '2025-11-25 02:18:24', 'standard'),
(335, 605, '2025-11-25', '02:09:04', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/7b2fb1e8-a3c2-4fb8-993e-b0c7c8116863_1764036544.png', 3, '2025-11-25 02:09:04', 'standard'),
(334, 613, '2025-11-25', '01:55:43', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/2ba76358-59a9-419a-a9d6-b4aa04053a99_1764035743.png', 3, '2025-11-25 01:55:43', 'standard'),
(333, 575, '2025-11-25', '01:54:40', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/78aa91db-eed7-421b-a96f-67a5e684f923_1764035680.png', 3, '2025-11-25 01:54:40', 'standard'),
(332, 600, '2025-11-25', '01:54:01', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/2f4db875-611e-4ad2-8a64-0c8f67f2e827_1764035641.png', 3, '2025-11-25 01:54:01', 'standard'),
(331, 656, '2025-11-25', '01:53:22', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/5f66bce6-9778-499a-8629-14a723838326_1764035602.png', 3, '2025-11-25 01:53:22', 'standard'),
(330, 655, '2025-11-25', '01:52:17', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/099d599b-21e3-4eca-b62b-1ef5f37a61eb_1764035537.png', 3, '2025-11-25 01:52:17', 'standard'),
(329, 606, '2025-11-25', '01:51:36', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/f0cb58ce-0af4-45ad-a2b3-01a35779175c_1764035496.png', 3, '2025-11-25 01:51:36', 'standard'),
(328, 572, '2025-11-25', '01:47:38', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/f9a2d6dd-0e2a-4f9a-bcae-83aa03cb3064_1764035258.png', 3, '2025-11-25 01:47:38', 'standard'),
(327, 654, '2025-11-25', '01:45:15', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/c04bbd7e-80e6-404d-927b-98f5ed7e6fb9_1764035115.png', 3, '2025-11-25 01:45:15', 'standard'),
(326, 566, '2025-11-25', '01:40:38', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/bbb5cd30-d150-4701-8916-05480ef92e1a_1764034838.png', 3, '2025-11-25 01:40:38', 'standard'),
(325, 653, '2025-11-25', '01:37:51', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/9bfa5ea4-f080-4071-b37a-2e2a8dbd8c5e_1764034671.png', 3, '2025-11-25 01:37:51', 'standard'),
(324, 647, '2025-11-25', '01:35:56', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/5fa07531-6135-46a5-af0c-ddfddd72a051_1764034556.png', 3, '2025-11-25 01:35:56', 'standard'),
(323, 652, '2025-11-25', '01:35:20', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/722981c3-5fce-408a-b671-ba80c6a63bc0_1764034520.png', 3, '2025-11-25 01:35:20', 'standard'),
(322, 651, '2025-11-25', '01:34:14', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/aec7f0a4-d879-42b1-9c6e-a47fe2649665_1764034454.png', 3, '2025-11-25 01:34:14', 'standard'),
(321, 579, '2025-11-25', '01:27:59', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/ff34fb63-e397-470a-b279-897f2b9f25f0_1764034079.png', 3, '2025-11-25 01:27:59', 'standard'),
(320, 650, '2025-11-25', '01:24:59', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/3e805f56-8fe5-415c-9c8e-a4bcb34bfdd2_1764033899.png', 3, '2025-11-25 01:24:59', 'standard'),
(319, 649, '2025-11-25', '01:22:38', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/b08d314a-d64d-4fe4-9507-94cd7435375c_1764033758.png', 3, '2025-11-25 01:22:38', 'standard'),
(318, 608, '2025-11-25', '01:19:37', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/88c601de-b6e1-4028-83dd-b3f9992144a1_1764033577.png', 3, '2025-11-25 01:19:37', 'standard'),
(317, 590, '2025-11-25', '01:18:46', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/6a92202b-a14b-4279-ba82-e51d419b1a61_1764033526.png', 3, '2025-11-25 01:18:46', 'standard'),
(316, 563, '2025-11-25', '01:18:15', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/aec3a2c6-b46f-4bf4-bc88-548c72ccada7_1764033495.png', 3, '2025-11-25 01:18:15', 'standard'),
(315, 564, '2025-11-25', '01:17:56', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/0b601657-372f-44d1-be8e-1e4f38b4c0a2_1764033476.png', 3, '2025-11-25 01:17:56', 'standard'),
(314, 646, '2025-11-25', '01:15:39', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/df6b2f75-9788-4a53-b3ff-3c10da23eb23_1764033339.png', 3, '2025-11-25 01:15:39', 'standard'),
(313, 583, '2025-11-25', '01:13:15', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/bb383809-dc57-48cd-9e29-becfc09b0cf1_1764033195.png', 3, '2025-11-25 01:13:15', 'standard'),
(312, 644, '2025-11-25', '01:12:48', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/f03a9f1c-13ab-4a61-b628-610b5329af79_1764033168.png', 3, '2025-11-25 01:12:48', 'standard'),
(311, 643, '2025-11-25', '01:10:55', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/71973108-6cc7-4a6f-8ca5-f30c726d9f07_1764033055.png', 3, '2025-11-25 01:10:55', 'standard'),
(310, 642, '2025-11-25', '01:08:54', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/931f2655-8e80-4114-b9c9-deabdc45757f_1764032934.png', 3, '2025-11-25 01:08:54', 'standard'),
(309, 570, '2025-11-25', '01:04:22', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/18362478-63cc-4e12-873a-454bd5119201_1764032662.png', 3, '2025-11-25 01:04:22', 'standard'),
(308, 641, '2025-11-25', '01:03:51', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/060cbbf2-8ee8-44ea-b108-5262317342f0_1764032631.png', 3, '2025-11-25 01:03:51', 'standard'),
(307, 640, '2025-11-25', '01:00:49', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/2791723a-6db7-4a88-8261-8edf4e91f74e_1764032449.png', 3, '2025-11-25 01:00:49', 'standard'),
(306, 556, '2025-11-25', '00:55:46', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/4b6af396-67e5-4d35-9363-a9130dc8b04f_1764032146.png', 3, '2025-11-25 00:55:46', 'standard'),
(305, 639, '2025-11-25', '00:54:38', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/0dd09bde-91ec-48ad-8466-ebabd134b6cd_1764032078.png', 3, '2025-11-25 00:54:38', 'standard'),
(304, 577, '2025-11-25', '00:53:10', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/de71a5cf-e37d-40e2-b8b6-4ec4b6f718d6_1764031990.png', 3, '2025-11-25 00:53:10', 'standard'),
(303, 638, '2025-11-25', '00:52:56', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/7a3facc8-acdf-488e-9831-cf196dfe044b_1764031976.png', 3, '2025-11-25 00:52:56', 'standard'),
(302, 637, '2025-11-25', '00:50:52', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/65247bb5-2355-4e80-81c7-744426bb655b_1764031852.png', 3, '2025-11-25 00:50:52', 'standard'),
(301, 636, '2025-11-25', '00:49:40', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/45737aa8-aa7b-4c4d-9c23-09955be8ceed_1764031780.png', 3, '2025-11-25 00:49:40', 'standard'),
(300, 635, '2025-11-25', '00:49:22', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/cb549883-f53a-4d52-a51d-7de63424cf44_1764031762.png', 3, '2025-11-25 00:49:22', 'standard'),
(299, 594, '2025-11-25', '00:48:54', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/c6cfce8e-410b-40a4-bbc9-df6d6aa81319_1764031734.png', 3, '2025-11-25 00:48:54', 'standard'),
(298, 560, '2025-11-25', '00:48:27', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/9fd72027-3a4c-4214-bc62-44d113918bf6_1764031707.png', 3, '2025-11-25 00:48:27', 'standard'),
(297, 593, '2025-11-25', '00:48:09', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/255ec79e-64e6-4834-a414-5bc70c2b5965_1764031689.png', 3, '2025-11-25 00:48:09', 'standard'),
(296, 568, '2025-11-25', '00:47:03', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/d25ed8f4-a3a6-4634-b06d-df47d6112d95_1764031623.png', 3, '2025-11-25 00:47:03', 'standard'),
(295, 634, '2025-11-25', '00:45:41', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/21667182-0a9e-42dc-900f-684b3b445989_1764031541.png', 3, '2025-11-25 00:45:41', 'standard'),
(294, 599, '2025-11-25', '00:44:39', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/9fb0f369-8ffc-4d31-b0ab-229dc09d50cd_1764031479.png', 3, '2025-11-25 00:44:39', 'standard'),
(293, 558, '2025-11-25', '00:44:16', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/9f585e93-ff98-4d75-91ff-548040f00f81_1764031456.png', 3, '2025-11-25 00:44:16', 'standard'),
(292, 596, '2025-11-25', '00:42:00', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/17511d27-ecb4-4d63-b995-a2852ed6835d_1764031320.png', 3, '2025-11-25 00:42:00', 'standard'),
(291, 592, '2025-11-25', '00:41:28', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/d3983c67-10ce-4ff1-9e32-a51187ea6be6_1764031288.png', 3, '2025-11-25 00:41:28', 'standard'),
(290, 589, '2025-11-25', '00:41:03', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/95834ea9-fb87-4f7d-8eab-341f32b663bf_1764031263.png', 3, '2025-11-25 00:41:03', 'standard'),
(289, 633, '2025-11-25', '00:40:37', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/88fbc2d5-cfd8-4452-a0dd-cf05fcd0f9ce_1764031237.png', 3, '2025-11-25 00:40:37', 'standard'),
(288, 573, '2025-11-25', '00:39:38', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/30a320ce-b8dd-45ff-915c-99f07713a705_1764031178.png', 3, '2025-11-25 00:39:38', 'standard'),
(287, 576, '2025-11-25', '00:38:59', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/96dd764c-fc19-44d5-a983-8f4857522349_1764031139.png', 3, '2025-11-25 00:38:59', 'standard'),
(286, 587, '2025-11-25', '00:38:21', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/a44b35d1-1c02-481d-a430-85f6a724433f_1764031101.png', 3, '2025-11-25 00:38:21', 'standard'),
(285, 611, '2025-11-25', '00:37:37', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/b0c79c65-6676-44ff-b10b-cdadae7358f2_1764031057.png', 3, '2025-11-25 00:37:37', 'standard'),
(284, 630, '2025-11-25', '00:36:29', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/3cd0b92a-fc97-4358-9700-3eda6c7df0b2_1764030989.png', 3, '2025-11-25 00:36:29', 'standard'),
(283, 629, '2025-11-25', '00:35:09', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/c284471e-75c4-4c25-9d8d-2d8d678687e0_1764030909.png', 3, '2025-11-25 00:35:09', 'standard'),
(282, 628, '2025-11-25', '00:33:38', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/1481fcc6-2ec0-4106-84b0-2a530c5e3768_1764030818.png', 3, '2025-11-25 00:33:38', 'standard'),
(281, 569, '2025-11-25', '00:30:24', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/6426c99d-d682-4d5b-bbec-47ab7ea604cb_1764030624.png', 3, '2025-11-25 00:30:24', 'standard'),
(280, 627, '2025-11-25', '00:29:33', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/51917ee9-47bf-4328-9a96-b0cecf384ed1_1764030573.png', 3, '2025-11-25 00:29:33', 'standard'),
(279, 555, '2025-11-25', '00:28:38', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/e58be4e9-9f19-4966-85bc-3de2c634aa1b_1764030518.png', 3, '2025-11-25 00:28:38', 'standard'),
(278, 585, '2025-11-25', '00:26:52', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/1d278f23-fb1f-400e-9bc6-73e9db3c97b0_1764030412.png', 3, '2025-11-25 00:26:52', 'standard'),
(277, 567, '2025-11-25', '00:26:23', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/2c045e51-025b-4b96-89bf-b87da42cd2cc_1764030383.png', 3, '2025-11-25 00:26:23', 'standard'),
(276, 626, '2025-11-25', '00:25:33', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/e6a8ba42-3df3-4eae-83dc-8a84a7de995b_1764030333.png', 3, '2025-11-25 00:25:33', 'standard'),
(275, 595, '2025-11-25', '00:18:12', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/4fcdfee4-c443-4f5a-977a-6c55dd4eaffb_1764029892.png', 3, '2025-11-25 00:18:12', 'standard'),
(274, 625, '2025-11-25', '00:15:06', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/96eff0f4-a71a-476b-912e-d2a61415fcfd_1764029706.png', 3, '2025-11-25 00:15:06', 'standard'),
(273, 571, '2025-11-25', '00:14:29', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/3575402b-3ea2-441b-81b8-2e695be5c216_1764029669.png', 3, '2025-11-25 00:14:29', 'standard'),
(272, 584, '2025-11-25', '00:13:56', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/adaf9f52-86a8-48b6-a6cd-b0bbaa2f9957_1764029636.png', 3, '2025-11-25 00:13:56', 'standard'),
(271, 586, '2025-11-25', '00:13:30', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/f94e8339-51e3-4d2d-a1f6-d06db0b5db46_1764029610.png', 3, '2025-11-25 00:13:30', 'standard'),
(270, 624, '2025-11-25', '00:12:48', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/536638b9-f50a-4cbd-b3f1-74840c4d0a3d_1764029568.png', 3, '2025-11-25 00:12:48', 'standard'),
(269, 562, '2025-11-25', '00:11:31', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/0637544b-0f6f-45b1-873f-b791f6a96325_1764029491.png', 3, '2025-11-25 00:11:31', 'standard'),
(268, 601, '2025-11-25', '00:09:49', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/1bb0c629-65f8-4a1c-b31a-158fafa4da14_1764029389.png', 3, '2025-11-25 00:09:49', 'standard'),
(267, 607, '2025-11-25', '00:09:18', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/32e749ff-ce4f-4c03-a14d-9a8914d0a1ae_1764029358.png', 3, '2025-11-25 00:09:18', 'standard'),
(266, 622, '2025-11-25', '00:06:42', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/6af56d13-b709-4626-bafd-a4d114df6016_1764029202.png', 3, '2025-11-25 00:06:42', 'standard'),
(265, 604, '2025-11-25', '00:05:26', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/d8d8fc52-9e19-4e58-a582-4bffe67a4869_1764029126.png', 3, '2025-11-25 00:05:26', 'standard'),
(264, 609, '2025-11-25', '00:05:02', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/45df86a6-01b6-44d5-ab6c-453f9183fb69_1764029102.png', 3, '2025-11-25 00:05:02', 'standard'),
(263, 621, '2025-11-25', '00:04:28', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/79e230af-e61f-4695-93b0-62c66d060212_1764029068.png', 3, '2025-11-25 00:04:28', 'standard'),
(262, 620, '2025-11-25', '00:03:02', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/141a452c-17a3-459c-a558-a121b88f29b1_1764028982.png', 3, '2025-11-25 00:03:02', 'standard'),
(261, 602, '2025-11-25', '00:02:32', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/b39640a4-5df7-4058-a213-1ec0bb9ae09a_1764028952.png', 3, '2025-11-25 00:02:32', 'standard'),
(260, 603, '2025-11-25', '00:01:47', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/756d2e0c-7a8c-4cb2-b4d5-44a542d9fe83_1764028907.png', 3, '2025-11-25 00:01:47', 'standard'),
(259, 619, '2025-11-25', '23:59:11', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/32a1a39b-baee-4658-a6a5-8bd62ef1e78c_1764028751.png', 3, '2025-11-24 23:59:11', 'standard'),
(258, 554, '2025-11-25', '23:57:01', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/d8f98e8f-9af3-44eb-8d43-749957baae18_1764028621.png', 3, '2025-11-24 23:57:01', 'standard'),
(257, 612, '2025-11-25', '23:55:55', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/b3e95104-c852-4a70-8574-88b909012fa7_1764028555.png', 3, '2025-11-24 23:55:55', 'standard'),
(256, 618, '2025-11-25', '23:52:45', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/7cba6ca5-df3a-4750-9263-d8c8096b0092_1764028365.png', 3, '2025-11-24 23:52:45', 'standard'),
(255, 617, '2025-11-25', '23:48:48', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/25c3b73e-6af4-47b8-b825-ca819db2f1e5_1764028128.png', 3, '2025-11-24 23:48:48', 'standard'),
(254, 616, '2025-11-25', '23:46:33', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/ae9f225b-add8-455b-af57-f56bbb9bc6c3_1764027993.png', 3, '2025-11-24 23:46:33', 'standard'),
(253, 565, '2025-11-25', '23:44:54', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/8bd284ee-56ed-4e97-861e-9001ef188e23_1764027894.png', 3, '2025-11-24 23:44:54', 'standard'),
(252, 615, '2025-11-25', '23:44:15', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/fee079e1-e123-4525-8dd4-26d63158928f_1764027855.png', 3, '2025-11-24 23:44:15', 'standard'),
(251, 614, '2025-11-25', '23:42:32', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/01c743b5-ddd1-4bf9-8ca0-361462abe8c5_1764027752.png', 3, '2025-11-24 23:42:32', 'standard'),
(349, 669, '2025-11-25', '03:49:39', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/be1d25cc-14a3-4369-ae1e-2f6c3283c16a_1765252179.png', 3, '2025-12-09 03:49:39', 'standard'),
(350, 671, '2025-11-25', '03:56:58', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/c109ad63-7621-46aa-b88d-8bb2332bf18a_1765252618.png', 3, '2025-12-09 03:56:58', 'standard'),
(351, 672, '2025-11-25', '04:00:22', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/3c352846-bba4-47de-aa81-89a7ab7cea63_1765252822.png', 3, '2025-12-09 04:00:22', 'standard'),
(352, 673, '2025-11-25', '05:15:48', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/1be05e12-86d9-44f4-a20e-c3124cc62ae5_1765257348.png', 3, '2025-12-09 05:15:48', 'standard'),
(353, 674, '2025-11-25', '05:16:30', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/c0760fdd-049e-45fb-861a-7be2de2d2954_1765257390.png', 3, '2025-12-09 05:16:30', 'standard'),
(354, 676, '2025-11-25', '05:17:03', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/1b069c02-bc12-4383-a3ed-b7be9dbc8ee8_1765257423.png', 3, '2025-12-09 05:17:03', 'standard'),
(355, 680, '2025-11-25', '05:18:33', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/c272535d-136b-41f9-b4b5-83587d19abd2_1765257513.png', 3, '2025-12-09 05:18:33', 'standard'),
(356, 681, '2025-11-25', '05:19:16', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/542bb9d7-a070-403a-a0c3-1298301f9378_1765257556.png', 3, '2025-12-09 05:19:16', 'standard'),
(357, 675, '2025-11-25', '05:22:03', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/2d93e921-aac0-4d04-91a2-bf6fc5ad619c_1765257723.png', 3, '2025-12-09 05:22:03', 'standard'),
(358, 677, '2025-11-25', '05:23:01', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/2916dd3c-a24a-4afd-8b39-41df12d3f327_1765257781.png', 3, '2025-12-09 05:23:01', 'standard'),
(359, 678, '2025-11-25', '05:24:02', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/1ca5a362-ebc7-472c-b53d-72b55305c257_1765257842.png', 3, '2025-12-09 05:24:02', 'standard'),
(360, 684, '2025-11-25', '05:24:52', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/bb3b9bed-628e-4096-8d9d-635a0c64c23e_1765257892.png', 3, '2025-12-09 05:24:52', 'standard'),
(361, 687, '2025-11-25', '05:25:29', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/a65c190b-e1be-4520-9c0a-a20d01396d33_1765257929.png', 3, '2025-12-09 05:25:29', 'standard'),
(362, 686, '2025-11-25', '05:26:23', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/e7895111-367c-4140-acc7-bad011a00b0c_1765257983.png', 3, '2025-12-09 05:26:23', 'standard'),
(363, 682, '2025-11-25', '05:27:02', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/eff4ce2d-420e-415d-86d2-e94977d11c78_1765258022.png', 3, '2025-12-09 05:27:02', 'standard'),
(364, 679, '2025-11-25', '05:27:26', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/e71172cd-afc7-4383-a21b-0e85af91ec3b_1765258046.png', 3, '2025-12-09 05:27:26', 'standard'),
(365, 685, '2025-11-25', '05:28:01', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/d00ee443-a9f2-450b-b43d-4564c3d79e8c_1765258081.png', 3, '2025-12-09 05:28:01', 'standard'),
(366, 688, '2025-11-25', '05:35:33', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/00c2773d-75ee-46f7-ad0a-dcdb52d20601_1765258533.png', 3, '2025-12-09 05:35:33', 'standard'),
(367, 689, '2025-11-25', '05:45:14', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/3311c5b4-2e44-4bb7-9594-b590bf754b95_1765259114.png', 3, '2025-12-09 05:45:14', 'standard'),
(368, 690, '2025-11-25', '05:46:25', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/84d58bb6-8f1a-4f1a-937f-4291610d46f4_1765259185.png', 3, '2025-12-09 05:46:25', 'standard'),
(369, 691, '2025-11-25', '05:46:57', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/9f90770a-caf8-403d-ba2c-e1f266825895_1765259217.png', 3, '2025-12-09 05:46:57', 'standard'),
(370, 692, '2025-11-25', '05:49:11', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/862fa7d4-6ecf-45be-ae30-e3f189bfcf36_1765259351.png', 3, '2025-12-09 05:49:11', 'standard'),
(371, 693, '2025-11-25', '05:50:01', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/b1e23f64-6570-4524-aecb-d449117bae43_1765259401.png', 3, '2025-12-09 05:50:01', 'standard'),
(372, 694, '2025-11-25', '05:51:22', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/51a73ed6-eaaa-430c-a0a5-32e396ddb164_1765259482.png', 3, '2025-12-09 05:51:22', 'standard'),
(373, 695, '2025-11-25', '05:56:04', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/85f8eb23-08d1-4b12-a9f5-78ff7d013257_1765259764.png', 3, '2025-12-09 05:56:04', 'standard'),
(374, 696, '2025-11-25', '06:02:02', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/709523c9-b831-40fc-90d2-fc6e6424cde0_1765260122.png', 3, '2025-12-09 06:02:02', 'standard'),
(375, 697, '2025-11-25', '06:05:35', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/b8e43e0c-7aa8-44fb-8944-4e2bf1de3726_1765260335.png', 3, '2025-12-09 06:05:35', 'standard'),
(376, 699, '2025-11-25', '06:28:45', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/70c909fb-62a8-4c53-b60a-a142a70dfc36_1765261725.png', 3, '2025-12-09 06:28:45', 'standard'),
(377, 700, '2025-11-25', '06:32:32', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/7d1ebe9d-89a8-4cfb-a0e2-74e7cea624b9_1765261952.png', 3, '2025-12-09 06:32:32', 'standard'),
(378, 701, '2025-11-25', '06:33:12', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/54f2c2c1-007d-47af-8e84-d805f830ad4b_1765261992.png', 3, '2025-12-09 06:33:12', 'standard'),
(379, 702, '2025-11-25', '06:34:35', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/c8fb33ab-f269-42fc-ab60-ddfcc0600b4a_1765262075.png', 3, '2025-12-09 06:34:35', 'standard'),
(380, 703, '2025-11-25', '06:36:29', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/ce183a44-b347-4b08-93ca-6087d27937b2_1765262189.png', 3, '2025-12-09 06:36:29', 'standard'),
(381, 704, '2025-11-25', '06:39:40', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/a2e6d978-5cb9-419f-8941-1dfcb654715d_1765262380.png', 3, '2025-12-09 06:39:40', 'standard'),
(382, 705, '2025-11-25', '06:42:40', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/e14424fb-bc26-4c34-bf95-1b0f20c68403_1765262560.png', 3, '2025-12-09 06:42:40', 'standard'),
(383, 706, '2025-11-25', '06:46:05', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/9cb8c0f3-674b-4ee6-9141-2d0bc31dda1d_1765262765.png', 3, '2025-12-09 06:46:05', 'standard'),
(384, 708, '2025-11-25', '06:47:50', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/6836440a-1717-40a9-b8d6-48216c294304_1765262870.png', 3, '2025-12-09 06:47:50', 'standard'),
(385, 707, '2025-11-25', '06:48:14', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/6c589dd4-2eba-4511-a0ae-0c5e2666c94e_1765262894.png', 3, '2025-12-09 06:48:14', 'standard'),
(386, 709, '2025-11-25', '06:51:16', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/e1787a4e-1941-4232-9374-a903ac0e76c4_1765263076.png', 3, '2025-12-09 06:51:16', 'standard'),
(387, 660, '2025-11-25', '07:17:24', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/0c446407-9a6f-44be-954d-38ebf665fe12_1765264644.png', 3, '2025-12-09 07:17:24', 'standard'),
(388, 710, '2025-11-25', '07:21:25', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/e0aab821-bcc4-4bf1-b9a1-e4bef1595f62_1765264885.png', 3, '2025-12-09 07:21:25', 'standard'),
(389, 711, '2025-11-25', '07:27:36', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/21d478c5-87df-43d7-b5cc-cd39ba675046_1765265256.png', 3, '2025-12-09 07:27:36', 'standard'),
(390, 712, '2025-11-25', '07:28:54', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/ac8d49d9-69de-4b36-805b-37a64a5a3b85_1765265334.png', 3, '2025-12-09 07:28:54', 'standard'),
(391, 713, '2025-11-25', '07:30:27', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/a7d1a60b-8cf1-478a-89a1-68f3cfb9a578_1765265427.png', 3, '2025-12-09 07:30:27', 'standard'),
(392, 714, '2025-11-25', '07:31:29', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/ceb40ec3-45a4-45c9-9e52-2f9079976507_1765265489.png', 3, '2025-12-09 07:31:29', 'standard'),
(393, 670, '2025-11-25', '07:32:30', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/6abdae41-8604-40ee-863e-f4d86b6a9c9c_1765265550.png', 3, '2025-12-09 07:32:30', 'standard'),
(394, 715, '2025-11-25', '07:33:54', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/dbac6b16-d928-4d50-8878-08417cc0685b_1765265634.png', 3, '2025-12-09 07:33:54', 'standard'),
(395, 716, '2025-11-25', '07:36:03', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/64bdc227-a7ce-43d7-83f8-94b24ccd6734_1765265763.png', 3, '2025-12-09 07:36:03', 'standard'),
(396, 717, '2025-11-25', '07:39:47', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/7c13fd5c-6f23-4090-a4d0-f4eb0999d673_1765265987.png', 3, '2025-12-09 07:39:47', 'standard'),
(397, 718, '2025-11-25', '07:40:52', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/53a2eb67-f2df-4dc2-9046-2baa9327e1e9_1765266052.png', 3, '2025-12-09 07:40:52', 'standard'),
(398, 719, '2025-11-25', '07:41:46', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/3a8a210f-09b0-4a60-9e5b-4f7ef3a4b488_1765266106.png', 3, '2025-12-09 07:41:46', 'standard'),
(399, 720, '2025-11-25', '07:42:56', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/a3bc2667-107a-46c8-8812-c4ddce262da5_1765266176.png', 3, '2025-12-09 07:42:56', 'standard'),
(400, 721, '2025-11-25', '07:43:57', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/signatures/2025/e9368fb8-0240-4888-83c5-22fa91663e96_1765266237.png', 3, '2025-12-09 07:43:57', 'standard');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `enforce_single_time_in` tinyint(1) DEFAULT 1,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `name`, `enforce_single_time_in`, `active`, `created_at`) VALUES
(1, 'QA Event', 1, 1, '2025-11-16 02:49:39'),
(2, 'QA Event', 1, 1, '2025-11-16 02:49:56'),
(3, 'QA Event', 1, 1, '2025-11-16 02:50:16');

-- --------------------------------------------------------

--
-- Table structure for table `import_logs`
--

CREATE TABLE `import_logs` (
  `id` bigint(20) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `action` enum('preview','execute','cancel') NOT NULL,
  `duplicate_strategy` enum('skip','override_duplicates','override_all') DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `import_logs`
--

INSERT INTO `import_logs` (`id`, `admin_id`, `file_name`, `action`, `duplicate_strategy`, `summary`, `created_at`) VALUES
(16, 1, '1764026868_1.csv', 'execute', 'skip', '{\"inserted\":61,\"updated\":0,\"skipped\":16,\"errored\":0,\"changes\":[],\"stored_csv\":\"\\/home\\/u872883517\\/domains\\/digitalbayanihan.site\\/public_html\\/storage\\/imports\\/1764026868_1.csv\"}', '2025-11-24 23:27:53'),
(15, 1, '1763963857_1.csv', 'execute', 'skip', '{\"inserted\":45,\"updated\":0,\"skipped\":7,\"errored\":1,\"changes\":[],\"stored_csv\":\"\\/home\\/u872883517\\/domains\\/digitalbayanihan.site\\/public_html\\/storage\\/imports\\/1763963857_1.csv\"}', '2025-11-24 05:58:20'),
(14, 1, '1763963092_1.csv', 'execute', 'skip', '{\"inserted\":45,\"updated\":0,\"skipped\":7,\"errored\":0,\"changes\":[],\"stored_csv\":\"\\/home\\/u872883517\\/domains\\/digitalbayanihan.site\\/public_html\\/storage\\/imports\\/1763963092_1.csv\"}', '2025-11-24 05:44:56'),
(13, 1, '1763962848_1.csv', 'execute', 'skip', '{\"inserted\":45,\"updated\":0,\"skipped\":7,\"errored\":0,\"changes\":[],\"stored_csv\":\"\\/home\\/u872883517\\/domains\\/digitalbayanihan.site\\/public_html\\/storage\\/imports\\/1763962848_1.csv\"}', '2025-11-24 05:40:52'),
(12, 1, '1763962614_1.csv', 'execute', 'skip', '{\"inserted\":30,\"updated\":0,\"skipped\":2,\"errored\":20,\"changes\":[],\"stored_csv\":\"\\/home\\/u872883517\\/domains\\/digitalbayanihan.site\\/public_html\\/storage\\/imports\\/1763962614_1.csv\"}', '2025-11-24 05:36:59'),
(11, 1, '1763962328_1.csv', 'execute', 'skip', '{\"inserted\":2,\"updated\":0,\"skipped\":0,\"errored\":50,\"changes\":[],\"stored_csv\":\"\\/home\\/u872883517\\/domains\\/digitalbayanihan.site\\/public_html\\/storage\\/imports\\/1763962328_1.csv\"}', '2025-11-24 05:32:13'),
(10, 1, '1763962130_1.csv', 'execute', 'skip', '{\"inserted\":0,\"updated\":0,\"skipped\":14,\"errored\":38,\"changes\":[],\"stored_csv\":\"\\/home\\/u872883517\\/domains\\/digitalbayanihan.site\\/public_html\\/storage\\/imports\\/1763962130_1.csv\"}', '2025-11-24 05:29:07'),
(9, 1, '1763961965_1.csv', 'execute', 'skip', '{\"inserted\":2,\"updated\":0,\"skipped\":0,\"errored\":50,\"changes\":[],\"stored_csv\":\"\\/home\\/u872883517\\/domains\\/digitalbayanihan.site\\/public_html\\/storage\\/imports\\/1763961965_1.csv\"}', '2025-11-24 05:26:24');

-- --------------------------------------------------------

--
-- Table structure for table `participants`
--

CREATE TABLE `participants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp(),
  `email` varchar(191) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `nickname` varchar(100) DEFAULT NULL,
  `sex` enum('Male','Female','Other') DEFAULT NULL,
  `sector` varchar(100) DEFAULT NULL,
  `agency` varchar(255) DEFAULT NULL,
  `designation` varchar(150) DEFAULT NULL,
  `office_email` varchar(191) DEFAULT NULL,
  `contact_no` varchar(50) DEFAULT NULL,
  `qr_path` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `participants`
--

INSERT INTO `participants` (`id`, `uuid`, `timestamp`, `email`, `first_name`, `middle_name`, `last_name`, `nickname`, `sex`, `sector`, `agency`, `designation`, `office_email`, `contact_no`, `qr_path`, `created_by`) VALUES
(667, 'd3642dcf-b817-47dd-8d63-890bb0e6bd95', '2025-11-25 05:37:37', 'omegajohnricsantos@gmail.com', 'John ric', 'Fernadez', 'santos', NULL, 'Male', 'Local Government Unit', 'legislative', 'assistant secretary', NULL, NULL, NULL, NULL),
(666, 'e0902a83-b659-405b-99c6-2783730c3e24', '2025-11-25 05:35:00', 'gaoiranperlita48@gmail.com', 'Perlita', 'Gangan', 'Gaoiran', 'Perlita', 'Female', 'Local Government Unit', 'legislative', 'city councilor', NULL, NULL, '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/e0/e0902a83-b659-405b-99c6-2783730c3e24.png', NULL),
(665, '84d29adf-d319-4d31-8960-f7166b173fc5', '2025-11-25 04:17:26', 'preciousargonza17@gmail.com', 'Ric anne precious', 'Gumaru', 'Argonza', 'Precious', 'Female', 'Local Government Unit', 'LGU ilagan', 'Ilagan', 'preciousargonza17@gmail.com', '09752761531', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/84/84d29adf-d319-4d31-8960-f7166b173fc5.png', NULL),
(664, '290ab328-b59c-4329-9dbe-88f62742cc78', '2025-11-25 04:14:15', 'go21@gmail.com', 'Christine joy', 'alejo', 'balloga', 'jhoy', 'Female', 'Local Government Unit', 'Lgu ilagan', 'ilagan', NULL, '09357620914', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/29/290ab328-b59c-4329-9dbe-88f62742cc78.png', NULL),
(663, '2ea774fb-4d6b-47fb-aed7-96d40f127b85', '2025-11-25 04:00:20', 'jayson.s.Lightnin@isu.edu.ph', 'JAYSON', 'SALVADOR', 'LIQUIGAN', 'Jay', 'Male', 'State Universities and Colleges', 'Isabela state university- cauayan campus', 'campus director for MIS', NULL, '09056202202', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/2e/2ea774fb-4d6b-47fb-aed7-96d40f127b85.png', NULL),
(662, 'c4759978-18c8-4622-8ab6-0ba75bd0b0b8', '2025-11-25 03:17:03', 'cyzione.mendoza@dict.gov.ph', 'Czyione Dayl', 'Asuncion', 'Mendoza', 'Yon', 'Male', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', NULL, NULL, NULL, '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/c4/c4759978-18c8-4622-8ab6-0ba75bd0b0b8.png', NULL),
(661, 'd3ca1296-564d-4001-a200-5e5a501d692a', '2025-11-25 02:28:43', 'geefuerte@gmail.com', 'Eloisa Gee', 'Fuerte', 'Pagallamman', 'Gee', 'Female', 'Provincial Government Unit', 'Provincial Government of Isabela', 'Lawyer', NULL, '09279928527', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/d3/d3ca1296-564d-4001-a200-5e5a501d692a.png', NULL),
(660, '0c446407-9a6f-44be-954d-38ebf665fe12', '2025-11-25 02:26:07', 'kristine.valdez@dict.gov.ph', 'maria kristine', 'Taguinod', 'Valdez', 'Tine', 'Female', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'PDO II', NULL, '09760231578', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/0c/0c446407-9a6f-44be-954d-38ebf665fe12.png', NULL),
(659, '4048ec8a-0e75-4f7b-aa87-26bba3f8c9b7', '2025-11-25 02:23:49', 'deejay.anapi@dict.gov.ph', 'Deejay', 'Gatan', 'Anapi', 'Deejs', 'Male', 'National Government Agency', 'DICT', 'PDO II', 'deejay.anapi@dict.gov.ph', '09162757816', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/40/4048ec8a-0e75-4f7b-aa87-26bba3f8c9b7.png', NULL),
(647, '5fa07531-6135-46a5-af0c-ddfddd72a051', '2025-11-25 01:15:08', NULL, 'EXEN', 'BANTIYAN', 'BANTIYANCLARO', 'TEN', 'Male', 'National Government Agency', 'DICT', 'PLA1', 'EXEN.CLARO@DICT.GOV.PH', '09755709582', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/5f/5fa07531-6135-46a5-af0c-ddfddd72a051.png', NULL),
(646, 'df6b2f75-9788-4a53-b3ff-3c10da23eb23', '2025-11-25 01:15:05', NULL, 'Ricardo', 'Mata', 'Bernardo', 'Jun', 'Male', 'Local Government Unit', 'LGU Santiago City', 'Driver', NULL, '09069611903', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/df/df6b2f75-9788-4a53-b3ff-3c10da23eb23.png', NULL),
(644, 'f03a9f1c-13ab-4a61-b628-610b5329af79', '2025-11-25 01:12:24', 'johanna.tulauan@dict.gov.ph', 'JOHANNA', NULL, 'TULAUAN', 'JOH', 'Female', 'National Government Agency', 'DICT', 'PROVINCIAL OFFICER', NULL, NULL, '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/f0/f03a9f1c-13ab-4a61-b628-610b5329af79.png', NULL),
(643, '71973108-6cc7-4a6f-8ca5-f30c726d9f07', '2025-11-25 01:10:07', 'christine.bueno@dict.gov.ph', 'Christine Joyce', 'Villaluz', 'Bueno', 'Tine', 'Female', 'National Government Agency', 'DICT', 'Cashier II', NULL, '09613055414', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/71/71973108-6cc7-4a6f-8ca5-f30c726d9f07.png', NULL),
(642, '931f2655-8e80-4114-b9c9-deabdc45757f', '2025-11-25 01:08:31', 'lguabulugcagayan@yahoo.com', 'felma', 'maguddatu', 'deza', 'ems', 'Female', 'Local Government Unit', 'LGU Abulug', 'Center Manager', NULL, '09175991625', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/93/931f2655-8e80-4114-b9c9-deabdc45757f.png', NULL),
(641, '060cbbf2-8ee8-44ea-b108-5262317342f0', '2025-11-25 01:03:05', 'jenny.prudenciado@dict.gov.ph', 'Jenny', 'Sala', 'Prudenciado', 'Jen', 'Female', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'Budget Officer I', NULL, '09760989753', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/06/060cbbf2-8ee8-44ea-b108-5262317342f0.png', NULL),
(640, '2791723a-6db7-4a88-8261-8edf4e91f74e', '2025-11-25 00:59:58', 'lotlotacera@gmail.com', 'Lot-Lot', 'Acera', 'Abrigo', 'Lottie', 'Female', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'budget officer', 'lotlot.acera@dct.gov.ph', '09985535281', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/27/2791723a-6db7-4a88-8261-8edf4e91f74e.png', NULL),
(639, '0dd09bde-91ec-48ad-8466-ebabd134b6cd', '2025-11-25 00:54:03', 'noraldeen.qaddoumi@deped.gov.ph', 'Nor Aldeen', 'Espinosa', 'Qaddoumi', 'Adin', 'Male', 'National Government Agency', 'DepEd', 'ITO', NULL, '09067414451', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/0d/0dd09bde-91ec-48ad-8466-ebabd134b6cd.png', NULL),
(638, '7a3facc8-acdf-488e-9831-cf196dfe044b', '2025-11-25 00:51:55', 'ROSALINDANOTO83@GMAIL.COM', 'ROSALINDA', 'NOTO', 'ESPALDON', 'ROSA', 'Female', 'State Universities and Colleges', 'Quirino State University', 'IT FACULTY/DICT FOCAL', NULL, NULL, NULL, NULL),
(634, '21667182-0a9e-42dc-900f-684b3b445989', '2025-11-25 00:45:20', NULL, 'Alfred', 'B.', 'iringan', NULL, 'Male', 'Local Government Unit', NULL, NULL, NULL, NULL, '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/21/21667182-0a9e-42dc-900f-684b3b445989.png', NULL),
(633, '88fbc2d5-cfd8-4452-a0dd-cf05fcd0f9ce', '2025-11-25 00:40:03', NULL, 'jonathan', 'sibayan', 'uy', 'Tan', 'Male', 'Local Government Unit', 'LGU Santiago City', NULL, NULL, NULL, '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/88/88fbc2d5-cfd8-4452-a0dd-cf05fcd0f9ce.png', NULL),
(631, '75da7fd3-6fcd-4ff0-b352-a5bd564f0d3d', '2025-11-25 00:40:03', NULL, 'jonathan', 'sibayan', 'uy', 'Tan', 'Male', 'Local Government Unit', 'LGU Santiago City', NULL, NULL, NULL, NULL, NULL),
(628, '1481fcc6-2ec0-4106-84b0-2a530c5e3768', '2025-11-25 00:33:07', 'zellefernandezdayson@gmail.com', 'Rizelle Jeane', 'Buena agua', 'Fernandez', 'Zelle', 'Female', 'Local Government Unit', 'Provincial Government of Nueva Vizcaya', 'Administrative Officer IV', 'gopitd@plgu.nuevavizcaya.gov.ph', '09053267977', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/14/1481fcc6-2ec0-4106-84b0-2a530c5e3768.png', NULL),
(627, '51917ee9-47bf-4328-9a96-b0cecf384ed1', '2025-11-25 00:29:02', 'VIOLETA.GASILAO@DEPED.GOV.PH', 'VIOLETA', NULL, 'gASILAO', 'violy', 'Female', 'National Government Agency', 'DEPED Batanes', 'Chief Education Supervisor', NULL, NULL, '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/51/51917ee9-47bf-4328-9a96-b0cecf384ed1.png', NULL),
(606, 'f0cb58ce-0af4-45ad-a2b3-01a35779175c', '2025-11-24 23:27:53', 'patrickjames.m.sabado@isu.edu.ph', 'Patrick', 'James M.', 'Sabado', NULL, 'Male', NULL, 'Isabela State University', 'Administrative Aide I', NULL, '9910384605', NULL, 1),
(607, '32e749ff-ce4f-4c03-a14d-9a8914d0a1ae', '2025-11-24 23:27:53', 'campus.info@csucarig.edu.ph', 'ROGER', 'P.', 'RUMPON', NULL, 'Male', NULL, 'CAGAYAN STATE UNIVERSITY-CARIG CAMPUS', 'Campus Executive Officer', NULL, '0977-127-2261', NULL, 1),
(608, '88c601de-b6e1-4028-83dd-b3f9992144a1', '2025-11-24 23:27:53', 'tuguegaraocitycata@gmail.com', 'Janine', 'Carla', 'Lim', NULL, 'Female', NULL, 'Tuguegarao City Government', 'Project Development Officer III', NULL, '9274870717', NULL, 1),
(625, '96eff0f4-a71a-476b-912e-d2a61415fcfd', '2025-11-25 00:14:37', 'janledran19@gmail.com', 'Jan Ledran', 'Parolan', 'Andaya', NULL, 'Male', 'Other', 'Nueva Vizcaya Police Provincial Office', 'Patrolman', NULL, '09622847817', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/96/96eff0f4-a71a-476b-912e-d2a61415fcfd.png', NULL),
(620, '141a452c-17a3-459c-a558-a121b88f29b1', '2025-11-25 00:02:16', 'jr.gazzingan@dict.gov.ph', 'Cirilo', 'Nacino', 'Gazzingan', 'Jay', 'Male', 'National Government Agency', 'DICT', 'ITO2/Provincial Officer', 'region2@dict.got.ph', '09279128348', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/14/141a452c-17a3-459c-a558-a121b88f29b1.png', NULL),
(621, '79e230af-e61f-4695-93b0-62c66d060212', '2025-11-25 00:03:52', 'edison.agaoid@dict.gov.ph', 'Edison', NULL, 'Agaoid', 'Edz', 'Male', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'ISA1', NULL, NULL, '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/79/79e230af-e61f-4695-93b0-62c66d060212.png', NULL),
(622, '6af56d13-b709-4626-bafd-a4d114df6016', '2025-11-25 00:06:06', 'crest.agustin@gmail.com', 'Crestian', 'Almazan', 'Agustin', 'Crest', 'Male', 'State Universities and Colleges', 'Isabela State University', 'Dean', 'ceat.ilagan@isu.edu.ph', '09664463508', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/6a/6af56d13-b709-4626-bafd-a4d114df6016.png', NULL),
(623, '1a2c4de3-e498-41a2-b009-504097fb928f', '2025-11-25 00:07:35', 'nptabangay@pldt.com.ph', 'Nouf', 'Plan', 'Tabangay', 'Sam', 'Male', 'Other', 'PLDT', 'PLDT Tuguegarao', 'nptabangay@pldt.com.ph', '09382523445', NULL, NULL),
(624, '536638b9-f50a-4cbd-b3f1-74840c4d0a3d', '2025-11-25 00:09:08', 'orfel.l.bejarin@isu.edu.ph', 'Orfel', 'Ledesma', 'Bejarin', 'Fhel', 'Male', 'State Universities and Colleges', NULL, 'ICT Chairman', NULL, '09359749418', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/53/536638b9-f50a-4cbd-b3f1-74840c4d0a3d.png', NULL),
(619, '32a1a39b-baee-4658-a6a5-8bd62ef1e78c', '2025-11-24 23:58:23', 'gie.baculi@dict.gov.ph', 'Virginia', 'Cabaddu', 'Baculi', 'Gigie', 'Female', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'Information Technology Officer II', 'gie.baculi@dict.gov.ph', '09950844661', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/32/32a1a39b-baee-4658-a6a5-8bd62ef1e78c.png', NULL),
(618, '7cba6ca5-df3a-4750-9263-d8c8096b0092', '2025-11-24 23:51:59', 'amir.aquino@deped.gov.ph', 'Amir', 'Mateo', 'Aquino', 'Amir', 'Male', 'National Government Agency', 'DepEd', NULL, NULL, '09959218506', NULL, NULL),
(617, '25c3b73e-6af4-47b8-b825-ca819db2f1e5', '2025-11-24 23:48:08', NULL, 'German', 'B', 'Salvador', 'German', 'Male', 'State Universities and Colleges', 'Batanes State College', NULL, NULL, NULL, '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/25/25c3b73e-6af4-47b8-b825-ca819db2f1e5.png', NULL),
(616, 'ae9f225b-add8-455b-af57-f56bbb9bc6c3', '2025-11-24 23:45:17', 'ajaysunico@gmail.com', 'Allan Jay', 'Pillos', 'Sunico', 'Allan', 'Male', 'National Government Agency', NULL, 'Chief, PRM', NULL, NULL, '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/ae/ae9f225b-add8-455b-af57-f56bbb9bc6c3.png', NULL),
(615, 'fee079e1-e123-4525-8dd4-26d63158928f', '2025-11-24 23:43:32', NULL, 'Hino Jesus', NULL, 'Cielo', 'Jessie', NULL, 'Local Government Unit', NULL, 'Municipal Trpeasurer', NULL, '09985508521', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/fe/fee079e1-e123-4525-8dd4-26d63158928f.png', NULL),
(614, '01c743b5-ddd1-4bf9-8ca0-361462abe8c5', '2025-11-24 23:41:43', 'joey.d.masirag@gmail.com', 'Joey', 'Dangarang', 'Masirag', 'Joey', 'Male', 'State Universities and Colleges', 'St. Joseph’s College Of Baggao, Inc', 'Dean', NULL, '09153553200', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/01/01c743b5-ddd1-4bf9-8ca0-361462abe8c5.png', NULL),
(605, '7b2fb1e8-a3c2-4fb8-993e-b0c7c8116863', '2025-11-24 23:27:53', 'ryanjamesamon08@gmail.com', 'RYAN', 'JAMES S.', 'AMON', NULL, 'Male', NULL, 'DEPARTMENT OF SOCIAL WELFARE AND DEVELOPMENT', 'PROJECT DEVELOPMENT OFFICER II', NULL, '9079400016', NULL, 1),
(604, 'd8d8fc52-9e19-4e58-a582-4bffe67a4869', '2025-11-24 23:27:53', 'jhay.antonio@qsu.edu.ph', 'JHAY', 'B.', 'Antonio', NULL, 'Male', NULL, 'Quirino State University Cabarroguis Campus', 'Extension Services and community Engagement Administrative Staff', NULL, '9161292175', NULL, 1),
(603, '756d2e0c-7a8c-4cb2-b4d5-44a542d9fe83', '2025-11-24 23:27:53', 'dilgr2.rictu@gmail.com', 'Ethelyn', 'T.', 'Deza', NULL, 'Female', NULL, 'DILG Region2', 'Data Analyst', NULL, '9051856599', NULL, 1),
(597, '21a6d4c5-301f-48c8-a992-e4f8300ad70f', '2025-11-24 23:27:53', 'ricardo.cabugao@deped.gov.ph', 'Ricardo', 'Q. Cabugao', 'Jr.', NULL, 'Male', NULL, 'DepEd SDO-Santiago City', 'Information Technology Officer I', NULL, '9458376992', NULL, 1),
(594, 'c6cfce8e-410b-40a4-bbc9-df6d6aa81319', '2025-11-24 23:27:53', 'arsenia.duldulao@qsu.edu.ph', 'ARSENIA', 'V', 'DULDULAO', NULL, 'Female', NULL, 'Quirino State University', 'BSIT Reseach Coordinator', NULL, '9176267828', NULL, 1),
(593, '255ec79e-64e6-4834-a414-5bc70c2b5965', '2025-11-24 23:27:53', 'divinegrace.olano@qsu.edu.ph', 'DIVINE', 'GRACE D.', 'OLAÑO', NULL, 'Female', NULL, 'QUIRINO STATE UNIVERSITY', 'BSIT EXTENSION SERVICES AND COMMUNITY ENGAGEMENT COORDINATOR', NULL, '9266811877', NULL, 1),
(591, 'bbc2f919-7fd6-4dbd-826d-6fad6384a4f3', '2025-11-24 23:27:53', 'jayson.s.liquigan@isu.edu.ph', 'Jayson', 'S.', 'Liquigan', NULL, 'Male', NULL, 'Isabela State University - Cauayan Campus', 'MIS Director', NULL, '9056202202', NULL, 1),
(589, '95834ea9-fb87-4f7d-8eab-341f32b663bf', '2025-11-24 23:27:53', 'keziahannrosini@gmail.com', 'Keziah', 'Ann', 'Rosini', NULL, 'Female', NULL, 'Nueva Vizcaya State University - Bambang Campus', 'Chief, Management Information System', NULL, '9351868554', NULL, 1),
(588, 'bbd73107-8abf-4053-bc0c-180caf1a4383', '2025-11-24 23:27:53', 'ryanposalviejo@gmail.com', 'Ryan', 'P.', 'Salviejo', NULL, 'Male', NULL, 'Isabela State University - Cauayan Campus', 'Director for External Affairs, Public Relations, and International Linkages', NULL, '9175501717', NULL, 1),
(584, 'adaf9f52-86a8-48b6-a6cd-b0bbaa2f9957', '2025-11-24 23:27:53', 'balunosrexceljerome@gmail.com', 'Rexcel', 'Jerome S.', 'Balunos', NULL, 'Male', NULL, 'DICT Ilagan', 'Participant/Awardee', NULL, '9615356896', NULL, 1),
(581, '1884e4da-528c-442a-8f50-8a0775cbed6c', '2025-11-24 23:27:53', 'nr02.ord@gmail.com', 'Dionisio', 'C. Ledres,', 'Jr.', NULL, 'Male', NULL, 'DEPDev RO2', 'Regional Director', NULL, '9158855956', NULL, 1),
(578, '89c4e081-755a-477c-ae3f-31d338d63de5', '2025-11-24 23:27:53', 'gapgayang@gmail.com', 'Gerard', 'Ariston', 'Perez', NULL, 'Male', NULL, 'St. Joseph’s College Of Baggao, Inc', 'College President', NULL, '9175780176', NULL, 1),
(576, '96dd764c-fc19-44d5-a983-8f4857522349', '2025-11-24 23:27:53', 'guiteringmarnel@gmail.com', 'Marnel', NULL, 'Guitering', NULL, 'Female', NULL, 'Cagayan State University-Aparri Campus', 'Campus MIS Coordinator', NULL, '9954918060', NULL, 1),
(575, '78aa91db-eed7-421b-a96f-67a5e684f923', '2025-11-24 23:27:53', 'gericoncepcion@gmail.com', 'GENERICO', 'H.', 'CONCEPCION', NULL, 'Male', NULL, 'LGU DINAPIGUE, ISABELA/', 'Business Permit and Licensing Office', NULL, '9988555168', NULL, 1),
(574, 'f9d47677-a749-4237-8dd7-b14ae77ee8cd', '2025-11-24 23:27:53', 'mayorsoffice.lgudinapigue@gmail.com', 'HON.', 'VICENTE D.', 'MENDOZA', NULL, 'Male', NULL, 'LGU DINAPIGUE ISABELA', 'LOCAL CHIEF EXECUTIVE', NULL, '9851429714', NULL, 1),
(573, '30a320ce-b8dd-45ff-915c-99f07713a705', '2025-11-24 23:27:53', 'kyle.suyu@dict.gov.ph', 'KYLE', 'RUZZEL C.', 'SUYU', NULL, 'Male', NULL, 'DICT RII', 'PLO II', NULL, '9959561077', NULL, 1),
(572, 'f9a2d6dd-0e2a-4f9a-bcae-83aa03cb3064', '2025-11-24 23:27:53', 'lplumiwes@gmail.com', 'LYRNATHES', 'L.', 'BLANCE', NULL, 'Female', NULL, 'LGU QUEZON', 'Administrative Aide IV/ MPDO/ICT Staff', NULL, '9292160050', NULL, 1),
(555, 'e58be4e9-9f19-4966-85bc-3de2c634aa1b', '2025-11-24 23:27:53', 'polmabborangjr@csucarig.edu.ph', 'POLICARPIO', 'L. MABBORANG,', 'JR.', NULL, 'Male', NULL, 'CAGAYAN STATE UNIVERSITY', 'CAMPUS EXECUTIVE OFFICER', NULL, '9268582212', NULL, 1),
(556, '4b6af396-67e5-4d35-9363-a9130dc8b04f', '2025-11-24 23:27:53', 'cics.lasam@csu.edu.ph', 'Florante', 'Victor M. Balatico,', 'PhD', NULL, 'Male', NULL, 'Cagayan State University - Lasam Campus', 'Campus Executive Officer', NULL, '9175187729', NULL, 1),
(557, '42fdf2f9-e35a-4cb4-ab2f-e5e560c3ae23', '2025-11-24 23:27:53', 'esninenvppo@gmail.com', 'PLTCOL', 'WARLITO M', 'JAGTO', NULL, 'Male', NULL, 'Nueva Vizcaya Police Provincial Office', 'Chief, Provincial Plans and Programs Unit', NULL, '9566527338', NULL, 1),
(558, '9f585e93-ff98-4d75-91ff-548040f00f81', '2025-11-24 23:27:53', 'cgurat@nvsu.edu.ph', 'Christopher', 'A.', 'Gurat', NULL, 'Male', NULL, 'Nueva Vizcaya State University', 'MIS Director', NULL, '9175909622', NULL, 1),
(668, 'bf3c8c52-3289-4cff-8484-f116a5487a50', '2025-11-25 05:42:31', NULL, 'gerryme', 'Magudang', 'servilla', 'Gem', 'Male', 'Local Government Unit', NULL, 'LNB', NULL, '09664173828', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/bf/bf3c8c52-3289-4cff-8484-f116a5487a50.png', NULL),
(658, '94f1eda7-6221-4ef7-a318-0dd89a2d0305', '2025-11-25 02:17:46', 'CHRISTIAN.CALDEZ@DICT.GOV.PH', 'CHRISTIAN LAZARO', 'FLORES', 'CALDEZ', 'CHRIS', 'Male', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'Project Development Officer I', 'CHRISTIAN.CALDEZ@DICT.GOV.PH', '09663947326', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/94/94f1eda7-6221-4ef7-a318-0dd89a2d0305.png', NULL),
(657, 'eacc7a2d-abc8-46dd-b047-ce2c2574e282', '2025-11-25 02:01:44', NULL, 'MARK ANTHONY', 'GONZALVO', 'PORTABES', 'MARK', 'Female', 'Other', 'BREINSOFT TECHNOLOGIES', 'CEO', 'markgportabes@gmail.com', '09277949888', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/ea/eacc7a2d-abc8-46dd-b047-ce2c2574e282.png', NULL),
(656, '5f66bce6-9778-499a-8629-14a723838326', '2025-11-25 01:53:01', 'alfredluke.b.espiritu@isu.edu.ph', 'Alfred Luke', 'Barcela', 'Espiritu', 'Albe', 'Male', 'State Universities and Colleges', 'Isabela State University', 'Campus ICT officer', 'ictoffice@isu.edu.ph', '09705810959', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/5f/5f66bce6-9778-499a-8629-14a723838326.png', NULL),
(655, '099d599b-21e3-4eca-b62b-1ef5f37a61eb', '2025-11-25 01:51:07', 'shashacostales17@gmail.com', 'LESLIE ANN', 'QUINTERO', 'COSTALES', 'SHASHA', 'Female', 'Local Government Unit', 'LGU-QUEZON', 'ADAS-II', 'lguquezonlce@gmail.com', '09123175796', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/09/099d599b-21e3-4eca-b62b-1ef5f37a61eb.png', NULL),
(654, 'c04bbd7e-80e6-404d-927b-98f5ed7e6fb9', '2025-11-25 01:43:53', 'joshuahapinat0816@gmai.com', 'JOSHUA', 'AGGABAO', 'HAPINAT', 'Josh', 'Male', 'Provincial Government Unit', 'Provincial Government of Isabela', 'Information Officer', 'letters_info@yahoo.com', '09207741022', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/c0/c04bbd7e-80e6-404d-927b-98f5ed7e6fb9.png', NULL),
(653, '9bfa5ea4-f080-4071-b37a-2e2a8dbd8c5e', '2025-11-25 01:37:20', 'JOYCEANNE.URDILLAS@DICT.GOV.PH', 'JOYCE ANN', 'PADER', 'URDILLAS', 'JOYCE', 'Female', 'National Government Agency', NULL, NULL, NULL, NULL, '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/9b/9bfa5ea4-f080-4071-b37a-2e2a8dbd8c5e.png', NULL),
(652, '722981c3-5fce-408a-b671-ba80c6a63bc0', '2025-11-25 01:34:48', 'christiandale.aguda@dict.gov.ph', 'Christian Dale', 'Costales', 'Aguda', 'Dale', 'Male', 'National Government Agency', 'DICT Region 2', 'Project Development Officer I', NULL, '09663793730', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/72/722981c3-5fce-408a-b671-ba80c6a63bc0.png', NULL),
(651, 'aec7f0a4-d879-42b1-9c6e-a47fe2649665', '2025-11-25 01:33:10', 'marizanovamontes@gmail.com', 'Mariza Nova', 'Miranda', 'Montes', 'Mariz', 'Female', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'AO I', NULL, '09764582091', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/ae/aec7f0a4-d879-42b1-9c6e-a47fe2649665.png', NULL),
(650, '3e805f56-8fe5-415c-9c8e-a4bcb34bfdd2', '2025-11-25 01:24:22', 'CheezaLeiaTagarino@dti.gov.ph', 'cheeza Leia', NULL, 'Tagarino', 'Kisz', 'Female', 'National Government Agency', 'DTI R2', 'CTIDS', NULL, '09175642039', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/3e/3e805f56-8fe5-415c-9c8e-a4bcb34bfdd2.png', NULL),
(649, 'b08d314a-d64d-4fe4-9507-94cd7435375c', '2025-11-25 01:21:57', 'mocorpuz@amaes.edu.ph', 'melanie', 'obra', 'corpuz', 'lanie', 'Female', 'State Universities and Colleges', 'Batanes State College', 'Records Officer (Representative of Campus Administrator)', 'mocorpuz@amaes.edu.ph', '09360581029', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/b0/b08d314a-d64d-4fe4-9507-94cd7435375c.png', NULL),
(648, '38e6b52e-3131-4664-b876-e97144cce490', '2025-11-25 01:15:14', NULL, 'EXEN', 'BANTIYAN', 'BANTIYANCLARO', 'TEN', 'Male', 'National Government Agency', 'DICT', 'PLA1', 'EXEN.CLARO@DICT.GOV.PH', '09755709582', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/38/38e6b52e-3131-4664-b876-e97144cce490.png', NULL),
(645, '7874d958-3709-48f2-be0a-ba1e70a532d3', '2025-11-25 01:15:05', NULL, 'EXEN', 'BANTIYAN', 'BANTIYANCLARO', 'TEN', 'Male', 'National Government Agency', 'DICT', 'PLA1', 'EXEN.CLARO@DICT.GOV.PH', '09755709582', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/78/7874d958-3709-48f2-be0a-ba1e70a532d3.png', NULL),
(637, '65247bb5-2355-4e80-81c7-744426bb655b', '2025-11-25 00:50:12', 'romedannug@gmail.com', 'Jerome', 'Mariano', 'dannuD', NULL, 'Male', 'Provincial Government Unit', 'CPLRC', 'cplrc', NULL, '09358838217', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/65/65247bb5-2355-4e80-81c7-744426bb655b.png', NULL),
(636, '45737aa8-aa7b-4c4d-9c23-09955be8ceed', '2025-11-25 00:48:40', NULL, 'Jacqueline', 'Soriano', 'Ramos', 'Jacq', 'Female', 'National Government Agency', 'DepEd', 'Asst. Schools Division Superintendent', NULL, NULL, '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/45/45737aa8-aa7b-4c4d-9c23-09955be8ceed.png', NULL),
(635, 'cb549883-f53a-4d52-a51d-7de63424cf44', '2025-11-25 00:46:49', NULL, 'alfredo', 'Binag', 'Gumaru', 'Fred', 'Male', 'National Government Agency', 'DepEd SDO-Santiago City', 'SDS', NULL, '09263650999', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/cb/cb549883-f53a-4d52-a51d-7de63424cf44.png', NULL),
(632, '436d8437-ccef-44c1-b656-e3d996176616', '2025-11-25 00:40:03', NULL, 'jonathan', 'sibayan', 'uy', 'Tan', 'Male', 'Local Government Unit', 'LGU Santiago City', NULL, NULL, NULL, NULL, NULL),
(630, '3cd0b92a-fc97-4358-9700-3eda6c7df0b2', '2025-11-25 00:35:38', 'colette.jose@smsupermalls.com', 'Colette', 'Muncal', 'Jose', 'Let', 'Female', 'Other', 'Sm City Tuguegarao', 'Sec', NULL, NULL, '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/3c/3cd0b92a-fc97-4358-9700-3eda6c7df0b2.png', NULL),
(629, 'c284471e-75c4-4c25-9d8d-2d8d678687e0', '2025-11-25 00:34:30', 'ems.sol@smsupermalls.com', 'Maria Mercedes', 'G', 'Sol', 'Ems', 'Female', 'Other', 'SM City Tuguegarao', 'Assistant Mall Manager', NULL, NULL, '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/c2/c284471e-75c4-4c25-9d8d-2d8d678687e0.png', NULL),
(626, 'e6a8ba42-3df3-4eae-83dc-8a84a7de995b', '2025-11-25 00:25:06', 'eric.nuez@yahoo.com', 'eric', 'duerme', 'nunez', 'eric', 'Male', 'Local Government Unit', 'calayan', 'sb member', NULL, NULL, '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/e6/e6a8ba42-3df3-4eae-83dc-8a84a7de995b.png', NULL),
(613, '2ba76358-59a9-419a-a9d6-b4aa04053a99', '2025-11-24 23:27:53', 'kristian.saccuan01@gmail.com', 'Kristian', 'E.', 'Saccuan', NULL, 'Male', NULL, 'Deped - SDO City of Ilagan', 'IT Officer', NULL, '9167537357', NULL, 1),
(609, '45df86a6-01b6-44d5-ab6c-453f9183fb69', '2025-11-24 23:27:53', 'records.cabarroguis@qsu.edu.ph', 'JOVIE', 'S. DELA', 'PENA', NULL, 'Female', NULL, 'Quirino State University Cabarroguis Campus', 'Records Officer (Representative of Campus Administrator)', NULL, '9771706934', NULL, 1),
(610, 'e5f24447-8f8d-404b-a5b3-6eb1e31e82e6', '2025-11-24 23:27:53', 'laraanna03@gmail.com', 'Annaliza', NULL, 'Alisasis', NULL, 'Female', NULL, 'PLDT', 'PLDT Tuguegarao', NULL, '9214798801', NULL, 1),
(611, 'b0c79c65-6676-44ff-b10b-cdadae7358f2', '2025-11-24 23:27:53', 'algerwingarcia.official@gmail.com', 'Algerwin', 'Karl Joseph', 'Garcia', NULL, 'Male', NULL, 'LGU Santiago City', 'Administrative Assistant III', NULL, '9558312302', NULL, 1),
(612, 'b3e95104-c852-4a70-8574-88b909012fa7', '2025-11-24 23:27:53', 'rica.casuga@dict.gov.ph', 'Oliver', NULL, 'Baccay', NULL, 'Male', NULL, 'Philippine Information Agency - Regional Office 02', 'OIC Regional Director', NULL, '0', NULL, 1),
(599, '9fb0f369-8ffc-4d31-b0ab-229dc09d50cd', '2025-11-24 23:27:53', 'emman.m@gmail.com', 'Emmanuel', NULL, 'Danguilan', NULL, 'Male', NULL, 'Nueva Vizcaya State University', 'Department Chair, IS Department', NULL, '9152271657', NULL, 1),
(600, '2f4db875-611e-4ad2-8a64-0c8f67f2e827', '2025-11-24 23:27:53', 'aldus.agnar@gmail.com', 'Aldus', 'T.', 'Agnar', NULL, 'Male', NULL, 'DENR Regional Office 02', 'Asst. Division Chief, Planning and Management Division', NULL, '9175502505', NULL, 1),
(601, '1bb0c629-65f8-4a1c-b31a-158fafa4da14', '2025-11-24 23:27:53', 'r2.ictmd@bjmp.gov.ph', 'Jundy', 'Vanaile T', 'Potot', NULL, 'Male', NULL, 'BJMP', 'ICT JNOR', NULL, '9533699418', NULL, 1),
(602, 'b39640a4-5df7-4058-a213-1ec0bb9ae09a', '2025-11-24 23:27:53', 'tvet@csucarig.edu.ph', 'CHARMIE', 'S.', 'CALVO', NULL, 'Female', NULL, 'CAGAYAN STATE UNIVERSITY', 'DTC Manager', NULL, '9475148568', NULL, 1),
(598, '40aa3030-50d9-4ef3-9913-d8ac2c9def7b', '2025-11-24 23:27:53', 'raffy.ursulum001@deped.gov.ph', 'Raffy', 'A.', 'Ursulum', NULL, 'Male', NULL, 'DepEd', 'SCNHS ICT Coordinator', NULL, '9066273255', NULL, 1),
(596, '17511d27-ecb4-4d63-b995-a2852ed6835d', '2025-11-24 23:27:53', 'dmw.tuguegarao@dmw.gov.ph', 'JAY', 'B. TELAN', 'II', NULL, 'Male', NULL, 'DEPARTMENT OF MIGRANT WORKERS - REGION 2', 'Administrative Officer III', NULL, '9938193229', NULL, 1),
(595, '4fcdfee4-c443-4f5a-977a-6c55dd4eaffb', '2025-11-24 23:27:53', 'rapapa@dswd.gov.ph', 'Resty', 'Apolonio', 'Papa', NULL, 'Male', NULL, 'Department of Social Welfare And Development', 'Project Development Officer II/Provincial Coordinator', NULL, '9452852397', NULL, 1),
(592, 'd3983c67-10ce-4ff1-9e32-a51187ea6be6', '2025-11-24 23:27:53', 'ivderije@gmail.com', 'IREL', 'V.', 'DERIJE', NULL, 'Female', NULL, 'Nueva Vizcaya State University', 'Data Protection Officer', NULL, '9559214877', NULL, 1),
(590, '6a92202b-a14b-4279-ba82-e51d419b1a61', '2025-11-24 23:27:53', 'maryjoycosmefernandez31@gmail.com', 'Mary', 'Joy', 'Fernandez', NULL, 'Female', NULL, 'AMA Computer College Santiago', 'Academic Coordinator', NULL, '9128812659', NULL, 1),
(585, '1d278f23-fb1f-400e-9bc6-73e9db3c97b0', '2025-11-24 23:27:53', 'mars@hackthenorth.ph', 'Wilcar', NULL, 'Saturno', NULL, 'Male', NULL, 'Hackthenorth.ph, Inc', 'Chief Operating Officer', NULL, '1', NULL, 1),
(586, 'f94e8339-51e3-4d2d-a1f6-d06db0b5db46', '2025-11-24 23:27:53', 'kingczyrhon200331@gmail.com', 'King', 'Bulaklak', 'Cagayan', NULL, 'Male', NULL, 'DICT', 'SPARK SCHOLAR', NULL, '9621076317', NULL, 1),
(587, 'a44b35d1-1c02-481d-a430-85f6a724433f', '2025-11-24 23:27:53', 'seiferxiii@gmail.com', 'Eliezer', NULL, 'Rabadon', NULL, 'Male', NULL, 'DvCode Technologies Inc.', 'CEO', NULL, '9617532120', NULL, 1),
(583, 'bb383809-dc57-48cd-9e29-becfc09b0cf1', '2025-11-24 23:27:53', 'jaymar.recolizado@dict.gov.ph', 'Jaymar', 'Corsino', 'Recolizado', NULL, 'Male', NULL, 'Department of Information and Communications Technology -Region 02', 'Information Systems Analyst III', NULL, '9169732612', NULL, 1),
(582, '3d78eb22-88c1-4670-95cc-60bd6a9877db', '2025-11-24 23:27:53', 'krismagne.maximo@deped.gov.ph', 'Krismagne', 'M.', 'Maximo', NULL, 'Female', NULL, 'DepEd Schools Division Office of Cauayan City', 'Information Technology Officer I', NULL, '9667459760', NULL, 1),
(580, 'c8b491a1-a278-47e7-bdc9-07940b9b9cc9', '2025-11-24 23:27:53', 'pesoprovinceofisabela1@gmail.com', 'CECILIA', 'CLAIRE N.', 'REYES', NULL, 'Female', NULL, 'PESO-ISABELA', 'PESO MANAGER', NULL, '9178741976', NULL, 1),
(579, 'ff34fb63-e397-470a-b279-897f2b9f25f0', '2025-11-24 23:27:53', 'tabago.maricel@isabela.uphsl.edu.ph', 'Maricel', 'B.', 'Tabago', NULL, 'Female', NULL, 'University of Perpetual Help System Isabela Campus', 'Dean', NULL, '+63 906 024 4452', NULL, 1),
(577, 'de71a5cf-e37d-40e2-b8b6-4ec4b6f718d6', '2025-11-24 23:27:53', 'rosalinda.espaldon@qsu.edu.ph', 'ROSALINDA', 'N.', 'ESPALDON', NULL, 'Female', NULL, 'QUIRINO STATE UNIVERSITY', 'IT FACULTY/DICT FOCAL', NULL, '9265581091', NULL, 1),
(562, '0637544b-0f6f-45b1-873f-b791f6a96325', '2025-11-24 23:27:53', 'nuevavizcayappoict@gmail.com', 'WARLITO', 'M', 'JAGTO', NULL, 'Male', NULL, 'PNP', 'POLICE LIEUTENANT COLONEL', NULL, '9566527338', NULL, 1),
(563, 'aec3a2c6-b46f-4bf4-bc88-548c72ccada7', '2025-11-24 23:27:53', 'ama_santiago@amaes.edu.ph', 'LORD', 'ANN C.', 'CONCEPCION', NULL, 'Female', NULL, 'AMA COMPUTER COLLEGE', 'SCHOOL DIRECTOR', NULL, '9364005750', NULL, 1),
(564, '0b601657-372f-44d1-be8e-1e4f38b4c0a2', '2025-11-24 23:27:53', 'nolascokarljohn19@gmail.com', 'KARL', 'JOHN P.', 'NOLASCO', NULL, 'Male', NULL, 'AMA COMPUTER COLLEGE', 'IT INSTRUCTOR', NULL, '9626368370', NULL, 1),
(565, '8bd284ee-56ed-4e97-861e-9001ef188e23', '2025-11-24 23:27:53', 'castillorandallg@gmail.com', 'Randall', 'G.', 'Castillo', NULL, 'Male', NULL, 'Batanes State College', 'ICT Professor', NULL, '9326580524', NULL, 1),
(566, 'bbb5cd30-d150-4701-8916-05480ef92e1a', '2025-11-24 23:27:53', 'athenayklim@gmail.com', 'Milky', 'Joy M.', 'Camat', NULL, 'Female', NULL, 'Camat Agritech Training Center Inc.', 'Director', NULL, '9099371250', NULL, 1),
(567, '2c045e51-025b-4b96-89bf-b87da42cd2cc', '2025-11-24 23:27:53', 'georgann.cariaso@deped.gov.ph', 'Georgann', 'G.', 'Cariaso', NULL, 'Male', NULL, 'DEPED Batanes', 'Asst. Schools Division Superintendent', NULL, '9190046420', NULL, 1),
(568, 'd25ed8f4-a3a6-4634-b06d-df47d6112d95', '2025-11-24 23:27:53', 'dianamarana@gmail.com', 'EDELITA', 'G.', 'ALLAS', NULL, 'Female', NULL, 'LGU-DIFFUN, QUIRINO', 'HRMO III', NULL, '9175019756', NULL, 1),
(569, '6426c99d-d682-4d5b-bbec-47ab7ea604cb', '2025-11-24 23:27:53', 'mathetsenot@gmail.com', 'Sherlyn', 'B.', 'Fernandez', NULL, 'Female', NULL, 'Provincial Government of Nueva Vizcaya', 'Information Technology Officer II', NULL, '9175143855', NULL, 1),
(570, '18362478-63cc-4e12-873a-454bd5119201', '2025-11-24 23:27:53', 'csuaparri@csu.edu.ph', 'Audy', 'R.', 'Quebral', NULL, 'Male', NULL, 'Cagayan State University - Aparri Campus', 'Campus Executive Officer', NULL, '9162221101', NULL, 1),
(571, '3575402b-3ea2-441b-81b8-2e695be5c216', '2025-11-24 23:27:53', 'castillodexahmaree@gmail.com', 'Dexah', 'Maree', 'Castillo', NULL, 'Female', NULL, 'N/A', 'N/A', NULL, '9972938075', NULL, 1),
(561, 'e0e58198-4fec-4ad7-a32f-f5d508ff94b6', '2025-11-24 23:27:53', 'kyzyziondevera@gmail.com', 'Elma', 'P.', 'Apostol', NULL, 'Female', NULL, 'Nueva Vizcaya State University', 'Vice President for Academic Affairs', NULL, '9175652098', NULL, 1),
(559, '995ca404-2020-49e8-8dd0-b32810cb3b85', '2025-11-24 23:27:53', 'ezekel.garing@deped.gov.ph', 'Ezekel', NULL, 'Garing', NULL, 'Male', NULL, 'Deped Nueva Vizcaya', 'Information Technology Officer I', NULL, '9162810014', NULL, 1),
(560, '9fd72027-3a4c-4214-bc62-44d113918bf6', '2025-11-24 23:27:53', 'gladysgalat.dtir2@gmail.com', 'GLADYS', 'B.', 'GALAT', NULL, 'Female', NULL, 'DTI REGION 2 - BATANES PROVINCIAL OFFICE', 'ADMINISTRATIVE OFFICER II', NULL, '9763869081', NULL, 1),
(553, '395c0413-9c17-4891-b667-479dceca07c1', '2025-11-24 23:27:53', 'amadelostrino@gmail.com', 'ANNA', 'MARIE A. DELOS', 'TRINO', NULL, 'Female', NULL, 'VICTORIA HIGH SCHOOL', 'SENIOR BOOKKEEPER', NULL, '9364635695', NULL, 1),
(554, 'd8f98e8f-9af3-44eb-8d43-749957baae18', '2025-11-24 23:27:53', 'jake@csu.edu.ph', 'Jake', 'G.', 'Maggay', NULL, 'Male', NULL, 'Cagayan State University - Lasam Campus', 'College Dean (IT Education)', NULL, '9056523644', NULL, 1),
(669, 'be1d25cc-14a3-4369-ae1e-2f6c3283c16a', '2025-12-09 03:48:15', 'ricavcasuga@gmail.com', 'Rica', 'Valentino', 'Casuga', NULL, 'Female', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'PROJECT DEVELOPMENT OFFICER II', 'rica.casuga@gmail.com', '09615060035', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/be/be1d25cc-14a3-4369-ae1e-2f6c3283c16a.png', NULL),
(670, '6abdae41-8604-40ee-863e-f4d86b6a9c9c', '2025-12-09 03:48:52', 'aries.guim@dict.gov.ph', 'Aries Anthony', 'Fuggay', 'Guim', NULL, 'Male', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'CMT II', NULL, '09162153514', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/6a/6abdae41-8604-40ee-863e-f4d86b6a9c9c.png', NULL),
(671, 'c109ad63-7621-46aa-b88d-8bb2332bf18a', '2025-12-09 03:56:27', 'rheajoycarascon22@gmail.com', 'Rhea Joy', 'Lagadon', 'Carascon', 'Hera', 'Female', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'Student Intern', 'rheajoycarascon22@gmail.com', '09274162623', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/c1/c109ad63-7621-46aa-b88d-8bb2332bf18a.png', NULL),
(672, '3c352846-bba4-47de-aa81-89a7ab7cea63', '2025-12-09 03:59:07', 'christinebullongan26@gmail.com', 'Monchristine', 'Dillig', 'Bullongan', NULL, 'Female', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'Student', 'christinebullongan26@gmail.com', '09536850949', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/3c/3c352846-bba4-47de-aa81-89a7ab7cea63.png', NULL),
(673, '1be05e12-86d9-44f4-a20e-c3124cc62ae5', '2025-12-09 03:59:44', 'umayamjeremy1@gmail.com', 'Jeremy', 'Mecate', 'Umayam', 'Ming', 'Male', 'National Government Agency', 'DICT', 'Student Intern', 'umayamjeremy1@gmail.com', '09703050582', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/1b/1be05e12-86d9-44f4-a20e-c3124cc62ae5.png', NULL),
(674, 'c0760fdd-049e-45fb-861a-7be2de2d2954', '2025-12-09 04:00:10', 'marlauberita2@gmail.com', 'Marla Mae', 'Siriban', 'Uberita', 'Mei', 'Female', 'National Government Agency', 'DICT Region 2', 'Student Intern', 'marlauberita2@gmail.com', '09756189173', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/c0/c0760fdd-049e-45fb-861a-7be2de2d2954.png', NULL),
(675, '2d93e921-aac0-4d04-91a2-bf6fc5ad619c', '2025-12-09 04:01:07', 'madambarenz22@gmail.com', 'Renz', 'Madamba', 'Castro', 'Renz', 'Male', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'Student Intern', 'madambarenz22@gmail.com', '09753727181', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/2d/2d93e921-aac0-4d04-91a2-bf6fc5ad619c.png', NULL),
(676, '1b069c02-bc12-4383-a3ed-b7be9dbc8ee8', '2025-12-09 04:01:09', 'ulibasbryan@gmail.com', 'Bryan', 'Usita', 'Ulibas', 'Bry', 'Male', 'National Government Agency', 'DICT', 'Student Intern', 'ulibasbryan@gmail.com', '09359705833', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/1b/1b069c02-bc12-4383-a3ed-b7be9dbc8ee8.png', NULL),
(677, '2916dd3c-a24a-4afd-8b39-41df12d3f327', '2025-12-09 04:01:48', 'gabatinow17@gmail.com', 'Winston', 'Ardiles', 'Gabatino', NULL, 'Male', 'National Government Agency', 'DICT Region 2', 'Student Intern', 'gabatinow17@gmail.com', '09930498612', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/29/2916dd3c-a24a-4afd-8b39-41df12d3f327.png', NULL),
(678, '1ca5a362-ebc7-472c-b53d-72b55305c257', '2025-12-09 04:01:48', 'martinbien1107@gmail.com', 'Louise Bien', 'Jacob', 'Martin', NULL, 'Male', 'National Government Agency', 'DICT Region 2', 'Student Intern', 'martinbien1107@gmail.com', '09051812545', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/1c/1ca5a362-ebc7-472c-b53d-72b55305c257.png', NULL),
(679, 'e71172cd-afc7-4383-a21b-0e85af91ec3b', '2025-12-09 05:12:19', 'albertjrgselga2002@gmail.com', 'Albert Jr,', 'Galicia', 'Selga', 'Kuys', 'Male', 'National Government Agency', 'DICT Region 2', 'STUDENT INTERN', 'albertjrgselga2002@gmail.com', NULL, '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/e7/e71172cd-afc7-4383-a21b-0e85af91ec3b.png', NULL),
(680, 'c272535d-136b-41f9-b4b5-83587d19abd2', '2025-12-09 05:14:11', 'niloamorsolo@gmail.com', 'Romeo Nilo', 'Macababbad', 'Amorsolo III', 'Nilo', 'Male', 'State Universities and Colleges', 'Department of Information and Communications Technology -Region 02', 'Student Intern', 'niloamorsolo@gmail.com', '09656872091', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/c2/c272535d-136b-41f9-b4b5-83587d19abd2.png', NULL),
(681, '542bb9d7-a070-403a-a0c3-1298301f9378', '2025-12-09 05:14:35', 'salva.harrold@gmail.com', 'Marc Harrold', NULL, 'Salva', 'Marc', 'Male', 'State Universities and Colleges', 'Department of Information and Communications Technology -Region 02', 'Student Intern', 'salva.harrold@gmail.com', '09760900410', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/54/542bb9d7-a070-403a-a0c3-1298301f9378.png', NULL),
(682, 'eff4ce2d-420e-415d-86d2-e94977d11c78', '2025-12-09 05:14:54', 'columnareyven0@gmail.com', 'Reyven', 'Samoy', 'Columna', 'Reyven', 'Male', 'National Government Agency', 'DICT Region 2', 'Student Intern', 'columnareyven0@gmail.com', '09629968226', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/ef/eff4ce2d-420e-415d-86d2-e94977d11c78.png', NULL),
(683, '680cf1d6-fac6-4e66-b659-692d444d6941', '2025-12-09 05:15:21', 'isapshs0076@gmail.com', 'Marc Harrold', NULL, 'Salva', 'Marc', 'Male', 'State Universities and Colleges', 'Department of Information and Communications Technology -Region 02', 'Student Intern', 'isapshs0076@gmail.com', '09760900410', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/68/680cf1d6-fac6-4e66-b659-692d444d6941.png', NULL),
(684, 'bb3b9bed-628e-4096-8d9d-635a0c64c23e', '2025-12-09 05:16:28', 'jordantarubal7@gmail.com', 'Jordan', 'N/A', 'Tarubal', 'Dan', 'Male', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'Student intern', 'jordantarubal7@gmail.com', '09953189963', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/bb/bb3b9bed-628e-4096-8d9d-635a0c64c23e.png', NULL),
(685, 'd00ee443-a9f2-450b-b43d-4564c3d79e8c', '2025-12-09 05:17:16', 'biendonelbadua04@gmail.com', 'Bien Donel', 'Domincil', 'Badua', 'bonjur', 'Male', 'National Government Agency', 'DICT Region 2', 'Student Intern', 'biendonelbadua04@gmail.com', '09676452976', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/d0/d00ee443-a9f2-450b-b43d-4564c3d79e8c.png', NULL),
(686, 'e7895111-367c-4140-acc7-bad011a00b0c', '2025-12-09 05:18:38', 'whynezelp@gmail.com', 'Whynezel', 'Tusoy', 'Balag', 'Whyne', 'Male', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'Student Intern', 'whynezelp@gmail.com', '09674575594', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/e7/e7895111-367c-4140-acc7-bad011a00b0c.png', NULL),
(687, 'a65c190b-e1be-4520-9c0a-a20d01396d33', '2025-12-09 05:21:27', 'johnkristoffcarino@gmail.com', 'John Kristoff', 'Celebrado', 'Cariño', 'Kristoff', 'Male', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'Student Intern', 'johnkristoffcarino@gmail.com', '09692064105', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/a6/a65c190b-e1be-4520-9c0a-a20d01396d33.png', NULL),
(688, '00c2773d-75ee-46f7-ad0a-dcdb52d20601', '2025-12-09 05:32:53', 'neilkristopher.guimmayen@dict.gov.ph', 'Neil Kristopher', 'Conel', 'Guimmayen', 'Neil', 'Male', 'National Government Agency', 'DICT Region 2', 'Engineer II', NULL, '09209648994', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/00/00c2773d-75ee-46f7-ad0a-dcdb52d20601.png', NULL),
(689, '3311c5b4-2e44-4bb7-9594-b590bf754b95', '2025-12-09 05:44:22', 'marcandrei816@gmail.com', 'Marc Andrei', 'Antipolo', 'Madarang', 'Andrei', 'Male', 'State Universities and Colleges', 'CAGAYAN STATE UNIVERSITY', 'Student Intern', NULL, '09615318848', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/33/3311c5b4-2e44-4bb7-9594-b590bf754b95.png', NULL),
(690, '84d58bb6-8f1a-4f1a-937f-4291610d46f4', '2025-12-09 05:45:35', 'martinezdylan553@gmail.com', 'Dylan', 'Gragasin', 'Martinez', NULL, 'Male', 'State Universities and Colleges', 'CAGAYAN STATE UNIVERSITY', 'Student Intern', NULL, '09498569426', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/84/84d58bb6-8f1a-4f1a-937f-4291610d46f4.png', NULL),
(691, '9f90770a-caf8-403d-ba2c-e1f266825895', '2025-12-09 05:45:53', 'natanielmarcos11@gmail.com', 'Nataniel', 'Abedes', 'Marcos', 'Taniel', 'Male', 'State Universities and Colleges', 'Department of Information and Communications Technology -Region 02', 'Student Intern', 'natanielmarcos11@gmail.com', '09260864779', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/9f/9f90770a-caf8-403d-ba2c-e1f266825895.png', NULL),
(692, '862fa7d4-6ecf-45be-ae30-e3f189bfcf36', '2025-12-09 05:48:35', 'kurtcaseymaddara8@gmail.com', 'Kurt Casey', 'Umipig', 'Maddara', 'KC', 'Male', 'State Universities and Colleges', 'DICT Region 2', 'Student Intern', 'kurtcaseymaddara8@gmail.com', '09754170629', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/86/862fa7d4-6ecf-45be-ae30-e3f189bfcf36.png', NULL),
(693, 'b1e23f64-6570-4524-aecb-d449117bae43', '2025-12-09 05:49:09', 'kian.pauig31@gmail.com', 'Kian', 'Agustin', 'Pauig', NULL, 'Male', 'State Universities and Colleges', 'DICT Region 2', 'Student Intern', 'kian.pauig31@gmail.com', '09458692612', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/b1/b1e23f64-6570-4524-aecb-d449117bae43.png', NULL),
(694, '51a73ed6-eaaa-430c-a0a5-32e396ddb164', '2025-12-09 05:50:30', 'judithreymundo03@gmail.com', 'Judith', 'Sacamil', 'Reymundo', 'jd', 'Female', 'State Universities and Colleges', 'Department of Information and Communications Technology -Region 02', 'Student Intern', 'judithreymundo03@gmail.com', '09755572395', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/51/51a73ed6-eaaa-430c-a0a5-32e396ddb164.png', NULL),
(695, '85f8eb23-08d1-4b12-a9f5-78ff7d013257', '2025-12-09 05:55:04', 'calojoey014@gmail.com', 'Joey', 'P', 'Calo', 'Joey', 'Male', 'State Universities and Colleges', 'Department of Information and Communications Technology -Region 02', 'Student Intern', 'calojoey014@gmail.com', '09702834246', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/85/85f8eb23-08d1-4b12-a9f5-78ff7d013257.png', NULL),
(696, '709523c9-b831-40fc-90d2-fc6e6424cde0', '2025-12-09 06:00:09', 'carizzadianne@gmail.com', 'Carizza Dianne', 'Alfonso', 'Bergonia', 'carizza', 'Female', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'Student Intern', 'carizzadianne@gmail.com', '09672047104', NULL, NULL),
(697, 'b8e43e0c-7aa8-44fb-8944-4e2bf1de3726', '2025-12-09 06:04:54', 'jeiariston@dict.gov.ph', 'Jei Ariston', 'Castillo', 'Jimenez', 'Jei', 'Male', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'PLO I', NULL, NULL, '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/b8/b8e43e0c-7aa8-44fb-8944-4e2bf1de3726.png', NULL),
(698, '662ec533-714c-4d68-89e5-8bb9e66c3dba', '2025-12-09 06:18:57', 'anogbea3@gmail.com', 'BEA', 'VERSOLA', 'ANOG', 'BEA', 'Female', 'National Government Agency', 'DICT Region 2', 'Student Intern', NULL, '09657003199', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/66/662ec533-714c-4d68-89e5-8bb9e66c3dba.png', NULL),
(699, '70c909fb-62a8-4c53-b60a-a142a70dfc36', '2025-12-09 06:27:19', 'tomatal734@gmail.com', 'Tomas Jr.', 'Valencia', 'Atal', NULL, 'Male', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'Staff', 'tomatal734@gmail.com', NULL, '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/70/70c909fb-62a8-4c53-b60a-a142a70dfc36.png', NULL),
(700, '7d1ebe9d-89a8-4cfb-a0e2-74e7cea624b9', '2025-12-09 06:31:53', 'joeypaulsoriano4@gmail.com', 'Joey Paul', 'Baniel', 'Soriano', 'Joonwei', 'Male', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'Intern', 'joeypaulsoriano4@gmail.com', '09355617423', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/7d/7d1ebe9d-89a8-4cfb-a0e2-74e7cea624b9.png', NULL),
(701, '54f2c2c1-007d-47af-8e84-d805f830ad4b', '2025-12-09 06:32:45', 'gammadcassandra@gmail.com', 'Maria Cassandra Kacey', 'Binasoy', 'Gammad', 'Cassy', 'Female', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'Student Intern', 'gammadcassandra@gmail.com', '09656876743', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/54/54f2c2c1-007d-47af-8e84-d805f830ad4b.png', NULL),
(702, 'c8fb33ab-f269-42fc-ab60-ddfcc0600b4a', '2025-12-09 06:34:14', 'jamiliamq@gmail.com', 'Jamilia Maxene', 'Hantoc', 'Quinagoran', 'Jam', 'Female', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'Intern', 'jamiliamq@gmail.com', '09069325644', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/c8/c8fb33ab-f269-42fc-ab60-ddfcc0600b4a.png', NULL),
(703, 'ce183a44-b347-4b08-93ca-6087d27937b2', '2025-12-09 06:35:46', 'shikynaancheta2021@gmail.com', 'Shikyna', 'Ancheta', 'Fernandez', 'kyna', 'Female', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'Intern', 'shikynaancheta2021@gmail.com', '09553501484', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/ce/ce183a44-b347-4b08-93ca-6087d27937b2.png', NULL),
(704, 'a2e6d978-5cb9-419f-8941-1dfcb654715d', '2025-12-09 06:39:22', 'maylanie.maggay@dict.gov.ph', 'maylanie', 'Ordoño', 'maggay', NULL, 'Female', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'PLA1', 'maylanie.maggay@dict.gov.ph', '09161912687', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/a2/a2e6d978-5cb9-419f-8941-1dfcb654715d.png', NULL),
(705, 'e14424fb-bc26-4c34-bf95-1b0f20c68403', '2025-12-09 06:42:01', 'shikynaancheta2021u@gmail.com', 'Joemarie Vince', 'Gardon', 'Duran', 'vince', 'Male', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'Intern', 'shikynaancheta2021u@gmail.com', '09553501484', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/e1/e14424fb-bc26-4c34-bf95-1b0f20c68403.png', NULL),
(706, '9cb8c0f3-674b-4ee6-9141-2d0bc31dda1d', '2025-12-09 06:45:47', 'mgsolita011@gmai.com', 'Mary Grace', NULL, 'Solita', 'Grace', 'Female', 'National Government Agency', 'DICT Region 2', 'Intern', 'mgsolita011@gmail.com', '09060829113', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/9c/9cb8c0f3-674b-4ee6-9141-2d0bc31dda1d.png', NULL),
(707, '6c589dd4-2eba-4511-a0ae-0c5e2666c94e', '2025-12-09 06:47:21', 'judesantilla30@gmail.com', 'Jude Michael', 'Pantoja', 'Santill', 'Jude', 'Male', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'Intern', 'judesantilla30@gmail.com', '09353834702', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/6c/6c589dd4-2eba-4511-a0ae-0c5e2666c94e.png', NULL);
INSERT INTO `participants` (`id`, `uuid`, `timestamp`, `email`, `first_name`, `middle_name`, `last_name`, `nickname`, `sex`, `sector`, `agency`, `designation`, `office_email`, `contact_no`, `qr_path`, `created_by`) VALUES
(708, '6836440a-1717-40a9-b8d6-48216c294304', '2025-12-09 06:47:29', 'riverajustinenicole1@gmail.com', 'Justine Nicole', 'Cabaya', 'Rivera', 'nicks', 'Female', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'Intern', 'riverajustinenicole1@gmail.com', '09679129314', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/68/6836440a-1717-40a9-b8d6-48216c294304.png', NULL),
(709, 'e1787a4e-1941-4232-9374-a903ac0e76c4', '2025-12-09 06:50:44', 'roel.jimenez@dict.gov.ph', 'Roel', 'Ursua', 'Jimenez', NULL, 'Male', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'PDO II', 'roel.jimenez@dict.gov.ph', '09266144600', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/e1/e1787a4e-1941-4232-9374-a903ac0e76c4.png', NULL),
(710, 'e0aab821-bcc4-4bf1-b9a1-e4bef1595f62', '2025-12-09 07:21:03', 'christopher.capili@dict.gov.ph', 'Christopher Eleeson', 'Largado', 'Capili', NULL, 'Male', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'PDO I', 'christopher.capili@dict.gov.ph', '9064010395', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/e0/e0aab821-bcc4-4bf1-b9a1-e4bef1595f62.png', NULL),
(711, '21d478c5-87df-43d7-b5cc-cd39ba675046', '2025-12-09 07:27:05', 'yancee.rafer1@dict.gov.ph', 'YANCEE KEARVIN KYLE', 'AQUINO', 'RAFER', NULL, NULL, 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'PLO I', 'yancee.rafer1@dict.gov.ph', '09615060035', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/21/21d478c5-87df-43d7-b5cc-cd39ba675046.png', NULL),
(712, 'ac8d49d9-69de-4b36-805b-37a64a5a3b85', '2025-12-09 07:28:35', 'shane.cepeda1@dict.gov.ph', 'CYRILL SHANE', 'TAGUIAM', 'CEPEDA', NULL, 'Male', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'Engineer II', 'shane.cepeda1@dict.gov.ph', '0963456678', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/ac/ac8d49d9-69de-4b36-805b-37a64a5a3b85.png', NULL),
(713, 'a7d1a60b-8cf1-478a-89a1-68f3cfb9a578', '2025-12-09 07:30:09', 'rito.banan1@gmail.com', 'RITO', 'GUMARANG', 'BANAN', NULL, NULL, 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'PLO II', 'rito.banan1@gmail.com', '09613456678', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/a7/a7d1a60b-8cf1-478a-89a1-68f3cfb9a578.png', NULL),
(714, 'ceb40ec3-45a4-45c9-9e52-2f9079976507', '2025-12-09 07:31:10', 'vlad.viktorr@gmail.com', 'VLADIMIR VIKTOR', 'GACUTAN', 'NUVAL', NULL, NULL, 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'PMO I', 'vlad.viktorr@gmail.com', '09615060036', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/ce/ceb40ec3-45a4-45c9-9e52-2f9079976507.png', NULL),
(715, 'dbac6b16-d928-4d50-8878-08417cc0685b', '2025-12-09 07:33:28', 'jei.jimenez@gmail.com', 'Jei Ariston', 'Castillo', 'Jimenez', NULL, NULL, 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'PLO I', 'jei.jimenez@gmail.com', '09675679651', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/db/dbac6b16-d928-4d50-8878-08417cc0685b.png', NULL),
(716, '64bdc227-a7ce-43d7-83f8-94b24ccd6734', '2025-12-09 07:35:41', 'kyle.suyu@gmail.com', 'KYLE RUZZEL', 'CARONAN', 'SUYU', NULL, 'Male', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'PLO II', 'kyle.suyu@gmail.com', '91627578161', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/64/64bdc227-a7ce-43d7-83f8-94b24ccd6734.png', NULL),
(717, '7c13fd5c-6f23-4090-a4d0-f4eb0999d673', '2025-12-09 07:39:27', 'brentramoranorti1@gmail.com', 'Brent Rudly', 'Ramoran', 'Ortiz', NULL, 'Male', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'Intern', 'brentramoranorti1@gmail.com', '09615060035', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/7c/7c13fd5c-6f23-4090-a4d0-f4eb0999d673.png', NULL),
(718, '53a2eb67-f2df-4dc2-9046-2baa9327e1e9', '2025-12-09 07:40:29', 'kryssfranchezka151@gmail.com', 'Kryss Franchezka', 'Morales', 'Labog', NULL, 'Female', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'Intern', 'kryssfranchezka151@gmail.com', '906401012395', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/53/53a2eb67-f2df-4dc2-9046-2baa9327e1e9.png', NULL),
(719, '3a8a210f-09b0-4a60-9e5b-4f7ef3a4b488', '2025-12-09 07:41:30', 'janelav021@gmail.com', 'Janel', 'Apostol', 'Valiente', NULL, 'Female', 'National Government Agency', 'Department of Information and Communications Technology -Region 02', 'Intern', 'janelav021@gmail.com', '096150600326', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/3a/3a8a210f-09b0-4a60-9e5b-4f7ef3a4b488.png', NULL),
(720, 'a3bc2667-107a-46c8-8812-c4ddce262da5', '2025-12-09 07:42:34', 'khaylaperez214@gmail.com', 'Khayla', 'Calimag', 'Perez', NULL, NULL, 'National Government Agency', 'CAGAYAN STATE UNIVERSITY-CARIG CAMPUS', 'Staff', 'khaylaperez214@gmail.com', '09634566780', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/a3/a3bc2667-107a-46c8-8812-c4ddce262da5.png', NULL),
(721, 'e9368fb8-0240-4888-83c5-22fa91663e96', '2025-12-09 07:43:37', 'magmanlackarljhun23@gmail.com', 'Karl Jhun', 'Quizon', 'Magmanlac', NULL, 'Male', 'State Universities and Colleges', 'CAGAYAN STATE UNIVERSITY-CARIG CAMPUS', 'Staff', 'magmanlackarljhun23@gmail.com', '09615060035', '/home/u872883517/domains/digitalbayanihan.site/public_html/storage/qrcodes/e9/e9368fb8-0240-4888-83c5-22fa91663e96.png', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `report_templates`
--

CREATE TABLE `report_templates` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`config`)),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `report_templates`
--

INSERT INTO `report_templates` (`id`, `admin_id`, `name`, `config`, `created_at`) VALUES
(1, 1, 'ISSP Training', '{\"date\": \"\", \"title\": \"\", \"fields\": [], \"format\": \"auto\", \"end_date\": \"\", \"subtitle\": \"\", \"start_date\": \"\"}', '2025-11-16 05:00:04'),
(2, 1, 'YESSIR', '{\"date\": \"\", \"title\": \"\", \"fields\": [], \"format\": \"auto\", \"end_date\": \"\", \"subtitle\": \"\", \"start_date\": \"\"}', '2025-11-16 06:48:38'),
(3, 1, 'WOWNESS1', '{\"date\": \"\", \"title\": \"\", \"fields\": [], \"format\": \"auto\", \"end_date\": \"\", \"subtitle\": \"\", \"start_date\": \"\"}', '2025-11-16 09:51:35'),
(4, 1, 'ISSP V1', '{\"title\":\"\",\"subtitle\":\"\",\"date\":\"\",\"start_date\":\"\",\"end_date\":\"\",\"fields\":[],\"format\":\"auto\"}', '2025-11-21 01:10:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `action_logs`
--
ALTER TABLE `action_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_admin_username` (`username`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attendance_pid_date` (`participant_id`,`attendance_date`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `import_logs`
--
ALTER TABLE `import_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `participants`
--
ALTER TABLE `participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `uq_email` (`email`),
  ADD KEY `idx_name_agency` (`first_name`(50),`last_name`(50),`agency`(100));

--
-- Indexes for table `report_templates`
--
ALTER TABLE `report_templates`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `action_logs`
--
ALTER TABLE `action_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=570;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=401;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `import_logs`
--
ALTER TABLE `import_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `participants`
--
ALTER TABLE `participants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=722;

--
-- AUTO_INCREMENT for table `report_templates`
--
ALTER TABLE `report_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
