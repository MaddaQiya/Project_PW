-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 22, 2025 at 09:54 PM
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
-- Database: `campusswallet`
--

-- --------------------------------------------------------

--
-- Table structure for table `anggaran`
--

CREATE TABLE `anggaran` (
  `id_anggaran` int(11) NOT NULL,
  `id_pengguna` int(11) NOT NULL,
  `id_kategori` int(11) NOT NULL,
  `jumlah` decimal(14,2) NOT NULL,
  `bulan` date NOT NULL COMMENT 'Format: 2025-11-01',
  `batas_peringatan` decimal(5,2) DEFAULT 80.00 COMMENT '0-100',
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `anggaran`
--

INSERT INTO `anggaran` (`id_anggaran`, `id_pengguna`, `id_kategori`, `jumlah`, `bulan`, `batas_peringatan`, `dibuat_pada`) VALUES
(1, 1, 1, 600000.00, '2025-11-01', 80.00, '2025-11-21 08:41:04'),
(2, 1, 5, 200000.00, '2025-11-01', 75.00, '2025-11-21 08:41:04');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int(11) NOT NULL,
  `nama` varchar(80) NOT NULL,
  `jenis` enum('pengeluaran','pemasukan') NOT NULL DEFAULT 'pengeluaran',
  `warna` varchar(7) DEFAULT '#667eea',
  `ikon` varchar(10) DEFAULT '?',
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama`, `jenis`, `warna`, `ikon`, `dibuat_pada`) VALUES
(1, 'Makan & Minum', 'pengeluaran', '#FF7F50', '🍔', '2025-11-21 08:41:04'),
(2, 'Transportasi', 'pengeluaran', '#1E90FF', '🚌', '2025-11-21 08:41:04'),
(3, 'Kos/Sewa', 'pengeluaran', '#FF4500', '🏠', '2025-11-21 08:41:04'),
(4, 'Pendidikan', 'pengeluaran', '#2E8B57', '📚', '2025-11-21 08:41:04'),
(5, 'Hiburan', 'pengeluaran', '#9370DB', '🎮', '2025-11-21 08:41:04'),
(6, 'Belanja', 'pengeluaran', '#FFD700', '🛒', '2025-11-21 08:41:04'),
(7, 'Lain-lain', 'pengeluaran', '#808080', '💼', '2025-11-21 08:41:04'),
(8, 'Gaji/Upah', 'pemasukan', '#3CB371', '💰', '2025-11-21 08:41:04'),
(9, 'Beasiswa', 'pemasukan', '#20B2AA', '🎓', '2025-11-21 08:41:04'),
(10, 'Uang Saku', 'pemasukan', '#32CD32', '💵', '2025-11-21 08:41:04');

-- --------------------------------------------------------

--
-- Table structure for table `pengguna`
--

CREATE TABLE `pengguna` (
  `id_pengguna` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nim` varchar(20) DEFAULT NULL,
  `fakultas` varchar(100) DEFAULT NULL,
  `uang_saku_bulanan` decimal(14,2) DEFAULT 0.00,
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengguna`
--

INSERT INTO `pengguna` (`id_pengguna`, `nama`, `email`, `password`, `nim`, `fakultas`, `uang_saku_bulanan`, `dibuat_pada`) VALUES
(1, 'Ada Lovelace', 'ada@campuswallet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '21IF001', 'Informatika', 2000000.00, '2025-11-21 08:41:04'),
(2, 'Achmad Daud Ashshidieqiya', 'maddaqiya812@gmail.com', '$2y$10$F1LS0mjN0c32wzesBNvlkuBKcBzLoVYuWN7e4pTAnlduyj.c8TuOK', '123240209', 'Fakultas Teknik Industri', 0.00, '2025-11-21 08:56:58'),
(3, 'Ada Ada Aja', 'maddaqiya@gmail.com', '$2y$10$lvlxRkDMGgNyrdU6O6BTPOeX5LO8oRMGnqnWEikNxyr97c4lxQuYK', '123240209', 'Fakultas Teknik Industri', 0.00, '2025-11-21 15:09:08');

-- --------------------------------------------------------

--
-- Table structure for table `target_tabungan`
--

CREATE TABLE `target_tabungan` (
  `id_target` int(11) NOT NULL,
  `id_pengguna` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `jumlah_target` decimal(14,2) NOT NULL,
  `jumlah_terkumpul` decimal(14,2) DEFAULT 0.00,
  `tanggal_target` date DEFAULT NULL,
  `status` enum('aktif','selesai','batal') DEFAULT 'aktif',
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `target_tabungan`
--

INSERT INTO `target_tabungan` (`id_target`, `id_pengguna`, `nama`, `deskripsi`, `jumlah_target`, `jumlah_terkumpul`, `tanggal_target`, `status`, `dibuat_pada`) VALUES
(1, 1, 'Laptop Baru', 'Beli laptop untuk coding', 8000000.00, 8000000.00, '2026-06-01', 'selesai', '2025-11-21 08:41:04'),
(2, 1, 'Iphone 17 Pro Max', 'HP Gweh Aipon HP Lu apah ?', 25749000.00, 1000000.00, '2026-04-22', 'aktif', '2025-11-22 20:43:30');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` int(11) NOT NULL,
  `id_pengguna` int(11) NOT NULL,
  `id_kategori` int(11) NOT NULL,
  `jumlah` decimal(14,2) NOT NULL,
  `jenis_transaksi` enum('pengeluaran','pemasukan') NOT NULL,
  `tanggal_transaksi` date NOT NULL,
  `catatan` varchar(255) DEFAULT NULL,
  `nama_akun` varchar(80) DEFAULT 'Tunai',
  `jenis_akun` enum('tunai','bank','ewallet') DEFAULT 'tunai',
  `id_target` int(11) DEFAULT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id_transaksi`, `id_pengguna`, `id_kategori`, `jumlah`, `jenis_transaksi`, `tanggal_transaksi`, `catatan`, `nama_akun`, `jenis_akun`, `id_target`, `dibuat_pada`) VALUES
(1, 1, 1, 25000.00, 'pengeluaran', '2025-11-01', 'Makan siang', 'Dompet', 'tunai', NULL, '2025-11-21 08:41:04'),
(2, 1, 3, 800000.00, 'pengeluaran', '2025-11-01', 'Bayar kos November', 'BNI', 'bank', NULL, '2025-11-21 08:41:04'),
(3, 1, 9, 10000000.00, 'pemasukan', '2025-11-01', 'Uang saku bulanan', 'BNI', 'bank', NULL, '2025-11-21 08:41:04'),
(4, 1, 2, 15000.00, 'pengeluaran', '2025-11-02', 'Ojol ke kampus', 'GoPay', 'ewallet', NULL, '2025-11-21 08:41:04'),
(6, 1, 2, 1000000.00, 'pengeluaran', '2025-11-22', '', 'BNI', 'bank', 2, '2025-11-22 20:44:54'),
(7, 1, 10, 1000000.00, 'pemasukan', '2025-11-22', '', 'BNI', 'bank', 2, '2025-11-22 20:48:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `anggaran`
--
ALTER TABLE `anggaran`
  ADD PRIMARY KEY (`id_anggaran`),
  ADD UNIQUE KEY `uk_pengguna_kategori_bulan` (`id_pengguna`,`id_kategori`,`bulan`),
  ADD KEY `id_kategori` (`id_kategori`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indexes for table `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`id_pengguna`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `target_tabungan`
--
ALTER TABLE `target_tabungan`
  ADD PRIMARY KEY (`id_target`),
  ADD KEY `id_pengguna` (`id_pengguna`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_pengguna` (`id_pengguna`),
  ADD KEY `id_kategori` (`id_kategori`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `anggaran`
--
ALTER TABLE `anggaran`
  MODIFY `id_anggaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `id_pengguna` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `target_tabungan`
--
ALTER TABLE `target_tabungan`
  MODIFY `id_target` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `anggaran`
--
ALTER TABLE `anggaran`
  ADD CONSTRAINT `anggaran_ibfk_1` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE CASCADE,
  ADD CONSTRAINT `anggaran_ibfk_2` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE CASCADE;

--
-- Constraints for table `target_tabungan`
--
ALTER TABLE `target_tabungan`
  ADD CONSTRAINT `target_tabungan_ibfk_1` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE CASCADE;

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaksi_ibfk_2` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
