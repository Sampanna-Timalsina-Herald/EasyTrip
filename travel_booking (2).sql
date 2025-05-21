-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 20, 2025 at 12:17 PM
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
-- Database: `travel_booking`
--

-- --------------------------------------------------------

--
-- Table structure for table `advice_requests`
--

CREATE TABLE `advice_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `destination` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `user_email` varchar(255) DEFAULT NULL,
  `advice` text DEFAULT NULL,
  `status` enum('pending','completed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `hotel_id` int(11) DEFAULT NULL,
  `bus_id` int(11) DEFAULT NULL,
  `check_in_date` date DEFAULT NULL,
  `check_out_date` date DEFAULT NULL,
  `travel_date` date DEFAULT NULL,
  `guests` int(11) DEFAULT 1,
  `passengers` int(11) DEFAULT 1,
  `selected_rooms` varchar(255) DEFAULT NULL,
  `selected_seats` varchar(255) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `order_id` varchar(100) DEFAULT NULL,
  `payment_details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `hotel_id`, `bus_id`, `check_in_date`, `check_out_date`, `travel_date`, `guests`, `passengers`, `selected_rooms`, `selected_seats`, `total_amount`, `payment_proof`, `status`, `booking_date`, `updated_at`, `payment_method`, `transaction_id`, `order_id`, `payment_details`) VALUES
(1, 4, 1, 1, '2025-04-10', '2025-04-15', '2025-04-10', 2, 2, '101,102', 'A1,A2', 13500.00, 'payment1.jpg', 'confirmed', '2025-03-23 06:48:01', '2025-03-23 06:48:01', NULL, NULL, NULL, NULL),
(2, 5, 2, 2, '2025-05-05', '2025-05-10', '2025-05-05', 3, 3, '201', 'B1', 11200.00, 'payment2.jpg', 'confirmed', '2025-03-23 06:48:01', '2025-03-23 08:55:22', NULL, NULL, NULL, NULL),
(7, 4, 1, 1, '2025-03-23', '2025-03-24', '2025-03-23', 1, 1, NULL, NULL, 6500.00, 'uploads/payment_proofs/1742719745_Untitled (2).jpg', 'pending', '2025-03-23 04:04:05', '2025-03-23 08:49:05', NULL, NULL, NULL, NULL),
(9, 4, 1, 1, '2025-03-25', '2025-03-28', '2025-03-25', 3, 3, NULL, NULL, 49500.00, 'uploads/payment_proofs/1742720593_UML Sequence Diagram.png', 'pending', '2025-03-23 04:18:13', '2025-03-23 09:03:13', NULL, NULL, NULL, NULL),
(11, 4, 1, 1, '2025-03-24', '2025-03-26', '2025-03-24', 2, 2, NULL, NULL, 23000.00, 'uploads/payment_proofs/1742720772_Untitled.png', 'confirmed', '2025-03-23 04:21:12', '2025-03-23 09:06:59', NULL, NULL, NULL, NULL),
(12, 1, 1, 1, '2025-03-24', '2025-03-26', '2025-03-24', 1, 1, NULL, NULL, 11500.00, 'uploads/payment_proofs/1742747289_Untitled.png', 'confirmed', '2025-03-23 11:43:09', '2025-03-23 16:30:01', NULL, NULL, NULL, NULL),
(13, 4, 1, 1, '2025-03-24', '2025-03-26', '2025-03-24', 2, 2, NULL, NULL, 23000.00, 'uploads/payment_proofs/1742751616_Untitled.png', 'confirmed', '2025-03-23 12:55:16', '2025-03-23 17:41:05', NULL, NULL, NULL, NULL),
(15, 4, 2, 2, '2025-03-29', '2025-03-31', '2025-03-29', 2, 2, NULL, NULL, 18400.00, 'uploads/payment_proofs/1743266118_Untitled.png', 'confirmed', '2025-03-29 11:50:18', '2025-03-29 16:37:02', NULL, NULL, NULL, NULL),
(16, 4, 2, 2, '2025-03-31', '2025-04-03', '2025-03-31', 2, 2, NULL, NULL, 26400.00, 'uploads/payment_proofs/1743307247_Use Case Diagram.png', 'pending', '2025-03-30 00:15:47', '2025-03-30 04:00:47', NULL, NULL, NULL, NULL),
(17, 4, 1, 1, '2025-04-03', '2025-04-05', '2025-04-03', 2, 2, NULL, NULL, 23000.00, 'uploads/payment_proofs/1743585252_Untitled (2).jpg', 'pending', '2025-04-02 05:29:12', '2025-04-02 09:14:12', NULL, NULL, NULL, NULL),
(18, 4, 1, 1, '2025-04-04', '2025-04-05', '2025-04-04', 2, 2, NULL, NULL, 13000.00, 'uploads/payment_proofs/1743740405_Untitled.png', 'pending', '2025-04-04 00:35:05', '2025-04-04 04:20:05', NULL, NULL, NULL, NULL),
(19, 4, 1, 1, '2025-04-19', '2025-04-21', '2025-04-19', 1, 1, NULL, NULL, 11500.00, 'uploads/payment_proofs/1744948835_Cahaya Dewi (1).jpg', 'pending', '2025-04-18 00:15:35', '2025-04-18 04:00:35', NULL, NULL, NULL, NULL),
(30, 13, 1, 1, '2025-05-18', '2025-05-19', '2025-05-18', 2, 2, NULL, NULL, 14300.00, 'uploads/payment_proofs/payment_13_6e06c907.png', 'pending', '2025-05-18 04:26:20', '2025-05-18 08:11:20', 'manual', 'TXN_6829962891115', 'TXN_6829962891115', NULL),
(31, 13, 4, 1, '2025-05-18', '2025-05-19', '2025-05-18', 2, 2, NULL, NULL, 6600.00, 'uploads/payment_proofs/payment_13_bb786faa.png', 'confirmed', '2025-05-18 04:38:13', '2025-05-20 09:53:37', 'manual', 'TXN_682998f166db4', 'TXN_682998f166db4', NULL),
(32, 13, NULL, NULL, NULL, NULL, NULL, 1, 1, '', '', 6600.00, NULL, 'confirmed', '2025-05-18 05:50:37', '2025-05-18 05:50:37', 'esewa', 'TXN_6829a604363b8?data=eyJ0cmFuc2FjdGlvbl9jb2RlIjoiMDAwQU5VNyIsInN0YXR1cyI6IkNPTVBMRVRFIiwidG90YWxfY', 'TXN_6829a604363b8', '{\"method\":\"esewa\",\"ref_id\":\"TXN_6829a604363b8?data=eyJ0cmFuc2FjdGlvbl9jb2RlIjoiMDAwQU5VNyIsInN0YXR1cyI6IkNPTVBMRVRFIiwidG90YWxfYW1vdW50IjoiNiw2MDAuMCIsInRyYW5zYWN0aW9uX3V1aWQiOiJUWE5fNjgyOWE2MDQzNjNiOCIsInByb2R1Y3RfY29kZSI6IkVQQVlURVNUIiwic2lnbmVkX2ZpZWxkX25hbWVzIjoidHJhbnNhY3Rpb25fY29kZSxzdGF0dXMsdG90YWxfYW1vdW50LHRyYW5zYWN0aW9uX3V1aWQscHJvZHVjdF9jb2RlLHNpZ25lZF9maWVsZF9uYW1lcyIsInNpZ25hdHVyZSI6IlUxcE9jNTAzZXJNTjl6UWFaRHpsd2NuTTlDNm40dW4xTFU4TFBSL1BJQVU9In0=\",\"amount\":\"6600\",\"note\":\"Created from callback without session data\"}'),
(33, 13, NULL, NULL, NULL, NULL, NULL, 1, 1, '', '', 6600.00, NULL, 'confirmed', '2025-05-20 05:48:13', '2025-05-20 05:48:13', 'esewa', 'TXN_682c4c3071f86?data=eyJ0cmFuc2FjdGlvbl9jb2RlIjoiMDAwQU9PWSIsInN0YXR1cyI6IkNPTVBMRVRFIiwidG90YWxfY', 'TXN_682c4c3071f86', '{\"method\":\"esewa\",\"ref_id\":\"TXN_682c4c3071f86?data=eyJ0cmFuc2FjdGlvbl9jb2RlIjoiMDAwQU9PWSIsInN0YXR1cyI6IkNPTVBMRVRFIiwidG90YWxfYW1vdW50IjoiNiw2MDAuMCIsInRyYW5zYWN0aW9uX3V1aWQiOiJUWE5fNjgyYzRjMzA3MWY4NiIsInByb2R1Y3RfY29kZSI6IkVQQVlURVNUIiwic2lnbmVkX2ZpZWxkX25hbWVzIjoidHJhbnNhY3Rpb25fY29kZSxzdGF0dXMsdG90YWxfYW1vdW50LHRyYW5zYWN0aW9uX3V1aWQscHJvZHVjdF9jb2RlLHNpZ25lZF9maWVsZF9uYW1lcyIsInNpZ25hdHVyZSI6Ik1XQkRINTBGOFppZHZCNFRLUDVNWmVETGVLWDdrbXFPMEtYYjFYS2loTEU9In0=\",\"amount\":\"6600\",\"note\":\"Created from callback without session data\"}'),
(34, 13, NULL, NULL, NULL, NULL, NULL, 1, 1, '', '', 6600.00, NULL, 'confirmed', '2025-05-20 06:06:09', '2025-05-20 06:06:09', 'esewa', 'TXN_682c5065964bf?data=eyJ0cmFuc2FjdGlvbl9jb2RlIjoiMDAwQU9QTCIsInN0YXR1cyI6IkNPTVBMRVRFIiwidG90YWxfY', 'TXN_682c5065964bf', '{\"method\":\"esewa\",\"ref_id\":\"TXN_682c5065964bf?data=eyJ0cmFuc2FjdGlvbl9jb2RlIjoiMDAwQU9QTCIsInN0YXR1cyI6IkNPTVBMRVRFIiwidG90YWxfYW1vdW50IjoiNiw2MDAuMCIsInRyYW5zYWN0aW9uX3V1aWQiOiJUWE5fNjgyYzUwNjU5NjRiZiIsInByb2R1Y3RfY29kZSI6IkVQQVlURVNUIiwic2lnbmVkX2ZpZWxkX25hbWVzIjoidHJhbnNhY3Rpb25fY29kZSxzdGF0dXMsdG90YWxfYW1vdW50LHRyYW5zYWN0aW9uX3V1aWQscHJvZHVjdF9jb2RlLHNpZ25lZF9maWVsZF9uYW1lcyIsInNpZ25hdHVyZSI6InlnWUtpdzlFVm1iUVUwMmdtL2YvYUxzTWREKzdRTTFEOUE0TVJZeUZTM009In0=\",\"amount\":\"6600\",\"note\":\"Created from callback without session data\"}');

-- --------------------------------------------------------

--
-- Table structure for table `buses`
--

CREATE TABLE `buses` (
  `id` int(11) NOT NULL,
  `operator_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `departure_location` varchar(100) NOT NULL,
  `arrival_location` varchar(100) NOT NULL,
  `departure_time` time NOT NULL,
  `arrival_time` time NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `total_seats` int(11) DEFAULT 0,
  `available_seats` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `bus_type` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buses`
--

INSERT INTO `buses` (`id`, `operator_id`, `name`, `description`, `departure_location`, `arrival_location`, `departure_time`, `arrival_time`, `image_url`, `price`, `total_seats`, `available_seats`, `status`, `created_at`, `updated_at`, `bus_type`) VALUES
(1, 2, 'Pokhara Express', 'Comfortable bus service from Kathmandu to Pokhara.', 'Kathmandu', 'Pokhara', '07:00:00', '14:00:00', 'pokhara_bus.jpg', 1500.00, 40, 35, 'active', '2025-03-23 06:48:01', '2025-05-20 05:23:30', NULL),
(2, 2, 'Chitwan Safari Bus', 'Best way to reach Chitwan from Kathmandu.', 'Kathmandu', 'Chitwan', '06:00:00', '10:00:00', 'chitwan_bus.jpg', 1200.00, 35, 30, 'active', '2025-03-23 06:48:01', '2025-05-20 05:24:27', NULL),
(3, 3, 'Lumbini Tourist Coach', 'Luxury coach for Kathmandu to Lumbini.', 'Kathmandu', 'Lumbini', '08:00:00', '17:00:00', 'lumbini_bus.jpg', 1800.00, 30, 25, 'active', '2025-03-23 06:48:01', '2025-03-23 06:48:01', NULL),
(5, 2, 'Lumbini Coach', '', 'Kathmandu', 'Lumbini', '09:00:00', '19:04:00', NULL, 1499.97, 25, 25, 'active', '2025-05-20 05:22:56', '2025-05-20 05:22:56', 'regular');

-- --------------------------------------------------------

--
-- Table structure for table `bus_amenities`
--

CREATE TABLE `bus_amenities` (
  `id` int(11) NOT NULL,
  `bus_id` int(11) DEFAULT NULL,
  `amenity` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bus_amenities`
--

INSERT INTO `bus_amenities` (`id`, `bus_id`, `amenity`) VALUES
(1, 5, 'wifi'),
(2, 5, 'reclining_seats');

-- --------------------------------------------------------

--
-- Table structure for table `bus_seats`
--

CREATE TABLE `bus_seats` (
  `id` int(11) NOT NULL,
  `bus_id` int(11) NOT NULL,
  `seat_number` varchar(10) NOT NULL,
  `seat_type` enum('regular','premium','sleeper') DEFAULT 'regular',
  `status` enum('available','booked','maintenance') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bus_seats`
--

INSERT INTO `bus_seats` (`id`, `bus_id`, `seat_number`, `seat_type`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'A1', 'regular', 'available', '2025-03-23 06:48:01', '2025-03-23 06:48:01'),
(2, 1, 'A2', 'premium', 'available', '2025-03-23 06:48:01', '2025-03-23 06:48:01'),
(3, 1, 'A3', 'sleeper', 'booked', '2025-03-23 06:48:01', '2025-03-23 06:48:01'),
(4, 2, 'B1', 'regular', 'available', '2025-03-23 06:48:01', '2025-03-23 06:48:01'),
(5, 3, 'C1', 'sleeper', 'available', '2025-03-23 06:48:01', '2025-03-23 06:48:01'),
(6, 5, '01', 'premium', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(7, 5, '02', 'premium', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(8, 5, '03', 'premium', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(9, 5, '04', 'premium', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(10, 5, '05', 'premium', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(11, 5, '06', 'regular', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(12, 5, '07', 'regular', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(13, 5, '08', 'regular', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(14, 5, '09', 'regular', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(15, 5, '10', 'regular', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(16, 5, '11', 'regular', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(17, 5, '12', 'regular', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(18, 5, '13', 'regular', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(19, 5, '14', 'regular', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(20, 5, '15', 'regular', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(21, 5, '16', 'regular', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(22, 5, '17', 'regular', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(23, 5, '18', 'regular', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(24, 5, '19', 'regular', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(25, 5, '20', 'regular', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(26, 5, '21', 'premium', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(27, 5, '22', 'premium', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(28, 5, '23', 'premium', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(29, 5, '24', 'premium', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56'),
(30, 5, '25', 'premium', 'available', '2025-05-20 05:22:56', '2025-05-20 05:22:56');

-- --------------------------------------------------------

--
-- Table structure for table `hotels`
--

CREATE TABLE `hotels` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(100) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `total_rooms` int(11) DEFAULT 0,
  `available_rooms` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hotels`
--

INSERT INTO `hotels` (`id`, `owner_id`, `name`, `description`, `location`, `image_url`, `price_per_night`, `total_rooms`, `available_rooms`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'Pokhara Lake View Hotel', 'A luxury hotel with a great view of Phewa Lake.', 'Pokhara', '../uploads/1747064087_hotel-1.webp', 5000.00, 20, 15, 'active', '2025-03-23 06:48:01', '2025-05-12 15:34:50'),
(2, 2, 'Chitwan Safari Resort', 'A nature resort near Chitwan National Park.', 'Chitwan', '../uploads/1747064112_hotel-2.webp', 4000.00, 15, 10, 'active', '2025-03-23 06:48:01', '2025-05-12 15:35:16'),
(3, 2, 'Lumbini Grand Hotel', 'A comfortable stay in the heart of Lumbini.', 'Lumbini', '../uploads/1747064149_hotel-3.webp', 6000.00, 25, 20, 'active', '2025-03-23 06:48:01', '2025-05-12 15:36:00'),
(4, 2, ' Hotel CloudNest', 'Nestled on a hillside with sweeping views of Phewa Lake and the Annapurna range', 'Pokhara', '../uploads/1747064257_hotel-5.webp', 1500.00, 15, 15, 'active', '2025-03-30 14:26:49', '2025-05-12 15:37:39'),
(5, 2, ' Lakeside Aura Hotel', 'Set right by Phewa Lake, this boutique hotel offers cozy rooms with balconies overlooking the water and mountains, perfect for romantic getaways and peaceful escapes.', 'Pokhara', '../uploads/1747064308_hotel-4.webp', 1500.00, 20, 20, 'active', '2025-03-30 14:36:53', '2025-05-12 15:38:30'),
(6, 2, 'Hotel CloudNest', 'Luxury meets nature on a serene hillside with lake and mountain views, rooftop dining, and sunrise yoga sessions.', 'Pokhara', '../uploads/1747064363_hotel-7.webp', 1500.00, 20, 20, 'active', '2025-03-30 14:37:06', '2025-05-12 15:39:25'),
(7, 2, 'Himalayan Horizon Resort', 'High above Sarangkot, this resort features an infinity pool, spa treatments, and guided treks with views of the Annapurna peaks.', 'Pokhara', '../uploads/1747064406_8.webp', 1500.00, 20, 20, 'active', '2025-03-30 14:37:21', '2025-05-12 15:40:51'),
(8, 2, 'Jungle Whisper Lodge', 'Nestled at the edge of the forest, this lodge blends eco-luxury with wildlife adventures and traditional Tharu hospitality.', 'Chitwan', '../uploads/1747064492_9.webp', 1000.00, 15, 15, 'active', '2025-03-30 14:39:37', '2025-05-12 15:41:34'),
(9, 2, 'Rhino River Escape', 'Riverside luxury tents and cabins surrounded by jungle sounds, with daily canoe rides and wildlife safaris included.', 'Chitwan', '../uploads/1747064728_hotel-7.webp', 2000.00, 35, 31, 'active', '2025-05-12 15:42:56', '2025-05-12 15:45:32'),
(10, 2, 'Tharu Village Stay Resort', 'An authentic cultural experience run by the Tharu community, with local cuisine, farming tours, and dance performances.', 'Chitwan', '../uploads/1747064753_10.webp', 2500.00, 23, 21, 'active', '2025-05-12 15:43:26', '2025-05-12 15:45:57'),
(11, 2, 'Bodhi Bliss Inn', 'A minimalist Zen stay designed for spiritual travelers, with incense-lit rooms, soft lighting, and silent breakfast options.', 'Lumbini', '../uploads/1747064771_11.webp', 1500.00, 35, 30, 'active', '2025-05-12 15:44:19', '2025-05-12 15:46:14'),
(12, 2, 'Lotus Light Heritage Hotel', 'Traditional Nepali architecture meets modern comfort in this cultural hotel near sacred Buddhist monasteries.', 'Lumbini', '../uploads/1747064840_hotel-12.png', 1800.00, 43, 24, 'active', '2025-05-12 15:44:54', '2025-05-12 15:47:21');

-- --------------------------------------------------------

--
-- Table structure for table `hotel_rooms`
--

CREATE TABLE `hotel_rooms` (
  `id` int(11) NOT NULL,
  `hotel_id` int(11) NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `room_type` enum('single','double','suite','family') DEFAULT 'single',
  `price_per_night` decimal(10,2) NOT NULL,
  `status` enum('available','occupied','maintenance') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hotel_rooms`
--

INSERT INTO `hotel_rooms` (`id`, `hotel_id`, `room_number`, `room_type`, `price_per_night`, `status`, `created_at`, `updated_at`, `description`) VALUES
(1, 1, '101', 'double', 5000.00, 'occupied', '2025-03-23 06:48:01', '2025-03-30 16:06:07', 'This is a perfect double room.'),
(2, 1, '102', 'double', 7000.00, 'available', '2025-03-23 06:48:01', '2025-03-23 06:48:01', NULL),
(3, 1, '103', 'suite', 10000.00, 'occupied', '2025-03-23 06:48:01', '2025-03-23 06:48:01', NULL),
(4, 2, '201', 'family', 8000.00, 'available', '2025-03-23 06:48:01', '2025-03-23 06:48:01', NULL),
(5, 3, '301', 'double', 6000.00, 'available', '2025-03-23 06:48:01', '2025-03-23 06:48:01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `room_amenities`
--

CREATE TABLE `room_amenities` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `amenity_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_amenities`
--

INSERT INTO `room_amenities` (`id`, `room_id`, `amenity_name`) VALUES
(2, 1, 'air_conditioning'),
(4, 1, 'private_bathroom'),
(3, 1, 'tv'),
(1, 1, 'wifi');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('traveler','admin','hotel_owner','bus_operator','agent') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Sulav Admin', 'admin@example.com', '$2y$10$98Y15.aphgX2D61/cwLKgeCkdTGK6uC1SddOn9KJrdfISn0pfxMfO', NULL, 'admin', '2025-03-23 06:42:33', '2025-04-03 15:53:13'),
(2, 'Hotel Owner', 'sampannat1@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'hotel_owner', '2025-03-23 06:42:33', '2025-05-20 09:52:08'),
(3, 'Bus Operator', 'bus@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'bus_operator', '2025-03-23 06:42:33', '2025-03-23 06:42:33'),
(4, 'Regular User', 'user@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'traveler', '2025-03-23 06:42:33', '2025-03-27 14:43:59'),
(5, 'Admin User', 'admin@travel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9800000000', 'admin', '2025-03-23 06:48:01', '2025-03-23 06:48:01'),
(6, 'Pokhara Hotel Owner', 'pokhara_hotel@travel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9800000001', 'hotel_owner', '2025-03-23 06:48:01', '2025-03-23 06:48:01'),
(7, 'Chitwan Bus Operator', 'chitwan_bus@travel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9800000002', 'bus_operator', '2025-03-23 06:48:01', '2025-03-23 06:48:01'),
(8, 'Kathmandu User', 'ktm_user@travel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9800000003', 'agent', '2025-03-23 06:48:01', '2025-03-29 14:40:16'),
(9, 'Lumbini User', 'lumbini_user@travel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9800000004', 'hotel_owner', '2025-03-23 06:48:01', '2025-03-29 08:02:29'),
(13, 'Sampanna Timalsina', 'sampannactwn@gmail.com', '$2y$10$TAMGUajMNo9AJc7HBfVJku1tybgvZN8w9Gxv2HlvkhogkrKNNfNMm', NULL, 'traveler', '2025-05-18 07:58:45', '2025-05-18 07:58:45'),
(14, 'Rounak Dhakal', 'yatrasathi0@gmail.com', '$2y$10$kWbb8lmZscEyCTZLHsuKzu7elFGExVSwYD.ouuhwoKoJvXxZ48ZGK', NULL, 'agent', '2025-05-18 07:59:56', '2025-05-18 08:03:23'),
(15, 'Sujan Gautam', 'np03cs4a230406@heraldcollege.edu.np', '$2y$10$kI.CTaGDxU4T3fT2kg1RMuGNxPAVAAaY76pXEsz0UtpZDJBYNxKFa', NULL, 'admin', '2025-05-18 08:01:04', '2025-05-18 08:03:06');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `advice_requests`
--
ALTER TABLE `advice_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `hotel_id` (`hotel_id`),
  ADD KEY `bus_id` (`bus_id`);

--
-- Indexes for table `buses`
--
ALTER TABLE `buses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `operator_id` (`operator_id`);

--
-- Indexes for table `bus_amenities`
--
ALTER TABLE `bus_amenities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bus_id` (`bus_id`);

--
-- Indexes for table `bus_seats`
--
ALTER TABLE `bus_seats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bus_id` (`bus_id`);

--
-- Indexes for table `hotels`
--
ALTER TABLE `hotels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `hotel_rooms`
--
ALTER TABLE `hotel_rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hotel_id` (`hotel_id`);

--
-- Indexes for table `room_amenities`
--
ALTER TABLE `room_amenities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_room_amenity` (`room_id`,`amenity_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `advice_requests`
--
ALTER TABLE `advice_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `buses`
--
ALTER TABLE `buses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `bus_amenities`
--
ALTER TABLE `bus_amenities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bus_seats`
--
ALTER TABLE `bus_seats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `hotels`
--
ALTER TABLE `hotels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `hotel_rooms`
--
ALTER TABLE `hotel_rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `room_amenities`
--
ALTER TABLE `room_amenities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `advice_requests`
--
ALTER TABLE `advice_requests`
  ADD CONSTRAINT `advice_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `buses`
--
ALTER TABLE `buses`
  ADD CONSTRAINT `buses_ibfk_1` FOREIGN KEY (`operator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bus_amenities`
--
ALTER TABLE `bus_amenities`
  ADD CONSTRAINT `bus_amenities_ibfk_1` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`);

--
-- Constraints for table `bus_seats`
--
ALTER TABLE `bus_seats`
  ADD CONSTRAINT `bus_seats_ibfk_1` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hotels`
--
ALTER TABLE `hotels`
  ADD CONSTRAINT `hotels_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `hotel_rooms`
--
ALTER TABLE `hotel_rooms`
  ADD CONSTRAINT `hotel_rooms_ibfk_1` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_amenities`
--
ALTER TABLE `room_amenities`
  ADD CONSTRAINT `room_amenities_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `hotel_rooms` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
