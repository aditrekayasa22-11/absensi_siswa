-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2026 at 08:59 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `absensi_siswa`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nik` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jabatan` varchar(100) NOT NULL,
  `no_wa` varchar(20) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','karyawan') NOT NULL DEFAULT 'karyawan',
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nik`, `nama`, `jabatan`, `no_wa`, `email`, `password`, `role`, `aktif`, `created_at`, `updated_at`) VALUES
(1, '999999', 'Administrator', 'Administrator Sistem', '6281234567890', 'admin@presensiku.test', '$2y$10$mWRGWlzsUNZDYTAWBX/RyubGa/DsQ4FsG0hKVn5XAxSca0aCYWglK', 'admin', 1, '2026-10-22 09:56:30', '2026-10-22 09:56:30'),
(2, '123456', 'Budi Santoso', 'Staff IT', '081234567801', 'budi@presensiku.test', '$2y$10$p16FNymtI3.HxdgC5ngqUut6vGfEumXDn2UWENh68ria0gEgsaHwS', 'karyawan', 1, '2026-10-22 09:56:30', '2026-10-22 09:56:30'),
(3, '123457', 'Siti Aminah', 'Staff HRD', '081234567802', 'siti@presensiku.test', '$2y$10$p16FNymtI3.HxdgC5ngqUut6vGfEumXDn2UWENh68ria0gEgsaHwS', 'karyawan', 1, '2026-10-22 09:56:30', '2026-10-22 09:56:30'),
(4, '123458', 'Andi Pratama', 'Staff Finance', '081234567803', 'andi@presensiku.test', '$2y$10$p16FNymtI3.HxdgC5ngqUut6vGfEumXDn2UWENh68ria0gEgsaHwS', 'karyawan', 1, '2026-10-22 09:56:30', '2026-10-22 09:56:30'),
(5, '123459', 'Rina Wulandari', 'Staff Marketing', '081234567804', 'rina@presensiku.test', '$2y$10$p16FNymtI3.HxdgC5ngqUut6vGfEumXDn2UWENh68ria0gEgsaHwS', 'karyawan', 1, '2026-10-22 09:56:30', '2026-10-22 09:56:30'),
(6, '123460', 'Dewa Saputra', 'Staff Operasional', '081234567805', 'dewa@presensiku.test', '$2y$10$p16FNymtI3.HxdgC5ngqUut6vGfEumXDn2UWENh68ria0gEgsaHwS', 'karyawan', 1, '2026-10-22 09:56:30', '2026-10-22 09:56:30'),
(11, '123461', 'Aditya', 'Staff Operasional', '6281234567806', 'aditya@presensiku.test', '$2y$10$mEGJ2qCEBVDdZlq1t8LpRO/LGIX145cMpa0Hr8VJHAHrkKs1ZIa7W', 'karyawan', 1, '2026-10-22 13:47:17', '2026-10-22 13:47:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_nik` (`nik`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_nama` (`nama`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
