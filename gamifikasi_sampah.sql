-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 06, 2026 at 06:11 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gamifikasi_sampah`
--

-- --------------------------------------------------------

--
-- Table structure for table `education`
--

CREATE TABLE `education` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `waste_type` varchar(50) NOT NULL,
  `point_category` varchar(50) NOT NULL,
  `bin_color` varchar(30) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `education`
--

INSERT INTO `education` (`id`, `title`, `description`, `waste_type`, `point_category`, `bin_color`, `image_path`, `created_at`) VALUES
(1, 'Pemilahan Sampah Organik Rumah Tangga', 'Sampah organik seperti sisa sayuran, dedaunan, dan buah-buahan dapat diolah menjadi kompos organik yang menyuburkan tanah.', 'Organik', '10 Poin/Kg', 'Hijau', NULL, '2026-06-06 03:45:49'),
(2, 'Mengelola Sampah Plastik & Botol PET', 'Botol plastik sekali pakai (PET) bernilai ekonomi tinggi. Cuci bersih dan pilah agar mudah didaur ulang kembali.', 'Plastik', '50 Poin/Kg', 'Kuning', NULL, '2026-06-06 03:45:49'),
(3, 'Kertas & Karton yang Dapat Didaur Ulang', 'Kardus bekas, koran, dan kertas HVS dapat diproses kembali menjadi bubur kertas. Pastikan kertas kering sebelum disetor.', 'Kertas', '20 Poin/Kg', 'Biru', NULL, '2026-06-06 03:45:49'),
(4, 'Bahaya Sampah Kaca & Penanganannya', 'Botol kaca utuh atau pecah memiliki wadah daur ulang tersendiri. Memilah kaca dapat menghindarkan bahaya bagi petugas kebersihan.', 'Kaca', '30 Poin/Kg', 'Merah', NULL, '2026-06-06 03:45:49'),
(5, 'Pengelolaan Logam, Besi & Kaleng Bekas', 'Besi tua, kaleng susu, dan aluminium bekas memiliki harga jual yang tinggi dan dapat dilebur berulang kali tanpa mengurangi kualitas.', 'Logam', '100 Poin/Kg', 'Abu-abu', NULL, '2026-06-06 03:45:49'),
(6, 'Pembuangan Sampah B3 Rumah Tangga', 'Limbah bahan berbahaya seperti baterai bekas, lampu neon, dan kemasan racun serangga tidak boleh dicampur dan harus ditangani khusus.', 'B3', '150 Poin/Kg', 'Merah', NULL, '2026-06-06 03:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `rewards`
--

CREATE TABLE `rewards` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `points_required` int(11) NOT NULL DEFAULT 0,
  `stock` int(11) NOT NULL DEFAULT 0,
  `status` enum('Tersedia','Habis') NOT NULL DEFAULT 'Tersedia',
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rewards`
--

INSERT INTO `rewards` (`id`, `name`, `description`, `points_required`, `stock`, `status`, `image_path`, `created_at`) VALUES
(1, 'Pulsa Rp10.000', 'Pengisian pulsa seluler sebesar Rp10.000 untuk all operator.', 200, 50, 'Tersedia', NULL, '2026-06-06 03:45:49'),
(2, 'Voucher Belanja Rp50.000', 'Voucher belanja minimarket lokal Gang Tani Pringsewu.', 900, 10, 'Tersedia', NULL, '2026-06-06 03:45:49'),
(3, 'Tumbler Eco-Friendly', 'Botol minum ramah lingkungan untuk mengurangi sampah botol plastik sekali pakai.', 500, 15, 'Tersedia', NULL, '2026-06-06 03:45:49'),
(4, 'Sembako Minyak & Beras', 'Paket sembako berisi 1 Liter minyak goreng dan 2 kg beras.', 800, 8, 'Tersedia', NULL, '2026-06-06 03:45:49'),
(5, 'Kaos TPS3R Gang Tani', 'Kaos merchandise eksklusif relawan TPS3R Gang Tani Pringsewu.', 600, 0, 'Habis', NULL, '2026-06-06 03:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `reward_claims`
--

CREATE TABLE `reward_claims` (
  `id` int(11) NOT NULL,
  `warga_id` int(11) NOT NULL,
  `reward_id` int(11) NOT NULL,
  `points_spent` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `warga_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `weight` decimal(10,2) NOT NULL,
  `points_earned` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `warga_id`, `admin_id`, `category_id`, `weight`, `points_earned`, `notes`, `created_at`) VALUES
(1, 2, 1, 1, 5.20, 520, 'Setoran awal demo sistem.', '2026-05-31 22:34:24'),
(2, 2, 1, 3, 2.00, 300, 'Setoran awal demo sistem.', '2026-06-02 22:34:24'),
(3, 3, 1, 2, 12.50, 625, 'Setoran awal demo sistem.', '2026-06-01 22:34:24'),
(4, 4, 1, 1, 8.00, 800, 'Setoran awal demo sistem.', '2026-06-03 22:34:24'),
(5, 4, 1, 5, 15.00, 300, 'Setoran awal demo sistem.', '2026-06-04 22:34:24'),
(6, 5, 1, 4, 4.50, 360, 'Setoran awal demo sistem.', '2026-06-03 22:34:24'),
(7, 5, 1, 2, 6.00, 300, 'Setoran awal demo sistem.', '2026-06-04 22:34:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','warga') NOT NULL DEFAULT 'warga',
  `nik` varchar(20) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `nik`, `name`, `phone`, `address`, `created_at`) VALUES
(1, 'admin', '$2y$10$LtfXis7svcwAtK7XQY1BKO/9.OpzJjbbK/Qf7Vcm9FhMifo/lgnVO', 'admin', NULL, 'Administrator Bank Sampah', NULL, NULL, '2026-06-06 03:34:24'),
(2, NULL, '$2y$10$qPB1P1oYF2A/c1Cu3RhQDeNYE4CHXg6cs2QQx2S0JoAa4r.fG3QAK', 'warga', '3201010101010001', 'Budi Santoso', '081234567890', 'Jl. Merdeka No. 10, RT 01/RW 02', '2026-06-06 03:34:24'),
(3, NULL, '$2y$10$s5qzaM/5UTpjPr1SU4wsC.sEU87lAxcHSg/KlCoW6SPekA5zM7kBK', 'warga', '3201010101010002', 'Siti Aminah', '082198765432', 'Jl. Mawar No. 5, RT 02/RW 02', '2026-06-06 03:34:24'),
(4, NULL, '$2y$10$XeUe6l247iSV8MxYV.dsVu3lHLv4OaDIWmRkKaFtV6Xl37qVQx/km', 'warga', '3201010101010003', 'Ahmad Fauzi', '085711223344', 'Jl. Melati No. 12, RT 01/RW 03', '2026-06-06 03:34:24'),
(5, NULL, '$2y$10$U9yqytqAl6q2PQAxCimOGejMoyqXAHVfhY/vQRgaf73zauiMo.ebq', 'warga', '3201010101010004', 'Dewi Lestari', '089988776655', 'Jl. Anggrek No. 8, RT 03/RW 03', '2026-06-06 03:34:24');

-- --------------------------------------------------------

--
-- Table structure for table `waste_categories`
--

CREATE TABLE `waste_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `point_per_kg` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `icon` varchar(50) NOT NULL DEFAULT 'package',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `waste_categories`
--

INSERT INTO `waste_categories` (`id`, `name`, `point_per_kg`, `description`, `icon`, `created_at`) VALUES
(1, 'Plastik (PET/HDPE)', 100, 'Botol plastik bekas, kemasan kosmetik, kantong plastik bersih.', 'droplet', '2026-06-06 03:34:24'),
(2, 'Kertas & Karton', 50, 'Kardus bekas, koran, majalah, kertas HVS.', 'file-text', '2026-06-06 03:34:24'),
(3, 'Logam (Besi/Aluminium)', 150, 'Kaleng minuman, peralatan dapur bekas, kawat, besi tua.', 'shield', '2026-06-06 03:34:24'),
(4, 'Kaca', 80, 'Botol kaca, toples kaca, pecahan kaca bersih.', 'glass-water', '2026-06-06 03:34:24'),
(5, 'Organik (Kompos)', 20, 'Sisa makanan, dedaunan, sisa sayuran.', 'leaf', '2026-06-06 03:34:24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `education`
--
ALTER TABLE `education`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rewards`
--
ALTER TABLE `rewards`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reward_claims`
--
ALTER TABLE `reward_claims`
  ADD PRIMARY KEY (`id`),
  ADD KEY `warga_id` (`warga_id`),
  ADD KEY `reward_id` (`reward_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `warga_id` (`warga_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `nik` (`nik`);

--
-- Indexes for table `waste_categories`
--
ALTER TABLE `waste_categories`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `education`
--
ALTER TABLE `education`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `rewards`
--
ALTER TABLE `rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reward_claims`
--
ALTER TABLE `reward_claims`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `waste_categories`
--
ALTER TABLE `waste_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reward_claims`
--
ALTER TABLE `reward_claims`
  ADD CONSTRAINT `reward_claims_ibfk_1` FOREIGN KEY (`warga_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reward_claims_ibfk_2` FOREIGN KEY (`reward_id`) REFERENCES `rewards` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`warga_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `waste_categories` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
