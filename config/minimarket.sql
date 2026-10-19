-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 19 Okt 2026 pada 05.53
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `minimarket`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Makanan Ringan', 'Camilan, keripik, biskuit, wafer'),
(2, 'Minuman', 'Air mineral, teh, kopi, soda, jus'),
(3, 'Rokok', 'Semua merk rokok'),
(4, 'Kebutuhan Rumah Tangga', 'Sabun, deterjen, pembersih'),
(5, 'Makanan Instan', 'Mie instan, bumbu, sarden'),
(6, 'Alat Tulis', 'Pulpen, buku, pensil'),
(7, 'Perlengkapan Mandi', 'Sabun mandi, shampo, pasta gigi'),
(8, 'Produk Bayi', 'Popok, susu, makanan bayi');

-- --------------------------------------------------------

--
-- Struktur dari tabel `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `loyalty_points` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `loyalty_points`, `created_at`) VALUES
(1, 'Ibu Ani Suryani', '0811-1111-2222', 'ani.suryani@gmail.com', 'Perum Griya Asri Blok C No. 12', 250, '2026-09-18 04:44:22'),
(2, 'Bapak Jokowi', '0812-2222-3333', 'joko.w@gmail.com', 'Jl. Anggrek No. 8', 175, '2026-09-18 04:44:22'),
(3, 'Ibu Wati Sukma', '0813-3333-4444', 'wati.sukma@gmail.com', 'Jl. Mawar No. 15', 320, '2026-09-18 04:44:22'),
(4, 'Pak Hadi Prayitno', '0814-4444-5555', 'hadi.p@gmail.com', 'Jl. Kenanga No. 3', 90, '2026-09-18 04:44:22'),
(5, 'Bu Rina Melati', '0815-5555-6666', 'rina.m@gmail.com', 'Jl. Melati No. 21', 410, '2026-09-18 04:44:22'),
(6, 'Pak Dedi Kurniawan', '0816-6666-7777', 'dedi.k@gmail.com', 'Jl. Dahlia No. 7', 130, '2026-09-18 04:44:22'),
(7, 'Bu Sri Wahyuni', '0817-7777-8888', 'sri.w@gmail.com', 'Jl. Flamboyan No. 9', 65, '2026-09-18 04:44:22'),
(8, 'Pak Anto Setiawan', '0818-8888-9999', 'anto.s@gmail.com', 'Jl. Cempaka No. 14', 200, '2026-09-18 04:44:22'),
(9, 'Bu Dewi Lestari', '0819-9999-0000', 'dewi.l@gmail.com', 'Jl. Kamboja No. 5', 145, '2026-09-18 04:44:22'),
(10, 'Pak Budi Hartono', '0821-1111-2222', 'budi.h@gmail.com', 'Jl. Sedap Malam No. 18', 88, '2026-09-18 04:44:22'),
(11, 'Bu Nur Aini', '0821-3333-4444', 'nur.aini@gmail.com', 'Jl. Teratai No. 22', 305, '2026-09-18 04:44:22'),
(12, 'Pak Bambang Sutrisno', '0821-5555-6666', 'bambang.s@gmail.com', 'Jl. Seroja No. 11', 220, '2026-09-18 04:44:22');

-- --------------------------------------------------------

--
-- Struktur dari tabel `loyalty_point_history`
--

CREATE TABLE `loyalty_point_history` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `type` enum('earn','redeem','adjust') NOT NULL,
  `points` int(11) NOT NULL,
  `points_before` int(11) NOT NULL DEFAULT 0,
  `points_after` int(11) NOT NULL DEFAULT 0,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `loyalty_point_history`
--

INSERT INTO `loyalty_point_history` (`id`, `customer_id`, `transaction_id`, `type`, `points`, `points_before`, `points_after`, `description`, `created_at`) VALUES
(1, 1, 1, 'earn', 21, 229, 250, 'Belanja hari ini', '2026-09-18 04:44:23'),
(2, 3, 3, 'earn', 46, 274, 320, 'Belanja hari ini', '2026-09-18 04:44:23'),
(3, 2, 5, 'earn', 38, 137, 175, 'Belanja hari ini', '2026-09-18 04:44:23'),
(4, 5, 7, 'earn', 70, 340, 410, 'Belanja hari ini', '2026-09-18 04:44:23'),
(5, 4, 9, 'earn', 31, 59, 90, 'Belanja hari ini', '2026-09-18 04:44:23'),
(6, 1, 12, 'earn', 42, 187, 229, 'Belanja 1 hari lalu', '2026-09-18 04:44:23'),
(7, 3, 14, 'earn', 63, 211, 274, 'Belanja 1 hari lalu', '2026-09-18 04:44:23'),
(8, 5, 16, 'earn', 48, 292, 340, 'Belanja 1 hari lalu', '2026-09-18 04:44:23'),
(9, 2, 18, 'earn', 52, 85, 137, 'Belanja 2 hari lalu', '2026-09-18 04:44:23'),
(10, 7, 20, 'earn', 67, 0, 67, 'Belanja 2 hari lalu', '2026-09-18 04:44:23'),
(11, 6, 23, 'earn', 44, 86, 130, 'Belanja 3 hari lalu', '2026-09-18 04:44:23'),
(12, 8, 25, 'earn', 38, 162, 200, 'Belanja 3 hari lalu', '2026-09-18 04:44:23'),
(13, 9, 28, 'earn', 71, 74, 145, 'Belanja 4 hari lalu', '2026-09-18 04:44:23'),
(14, 10, 30, 'earn', 46, 42, 88, 'Belanja 4 hari lalu', '2026-09-18 04:44:23'),
(15, 11, 32, 'earn', 59, 246, 305, 'Belanja 5 hari lalu', '2026-09-18 04:44:23'),
(16, 12, 34, 'earn', 41, 179, 220, 'Belanja 5 hari lalu', '2026-09-18 04:44:23'),
(17, 1, 36, 'earn', 52, 135, 187, 'Belanja 6 hari lalu', '2026-09-18 04:44:23'),
(18, 5, 38, 'earn', 68, 224, 292, 'Belanja 6 hari lalu', '2026-09-18 04:44:23'),
(19, 3, 40, 'earn', 47, 164, 211, 'Belanja 7 hari lalu', '2026-09-18 04:44:23'),
(20, 4, 42, 'earn', 61, 24, 85, 'Belanja 8 hari lalu', '2026-09-18 04:44:23'),
(21, 6, 44, 'earn', 54, 32, 86, 'Belanja 9 hari lalu', '2026-09-18 04:44:23'),
(22, 8, 46, 'earn', 42, 120, 162, 'Belanja 10 hari lalu', '2026-09-18 04:44:23'),
(23, 9, 48, 'earn', 73, 1, 74, 'Belanja 11 hari lalu', '2026-09-18 04:44:23'),
(24, 1, 50, 'earn', 19, 116, 135, 'Belanja 12 hari lalu', '2026-09-18 04:44:23'),
(25, 2, 53, 'earn', 58, 27, 85, 'Belanja 13 hari lalu', '2026-09-18 04:44:23'),
(26, 11, 55, 'earn', 45, 201, 246, 'Belanja 14 hari lalu', '2026-09-18 04:44:23');

-- --------------------------------------------------------

--
-- Struktur dari tabel `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `purchase_price` decimal(15,2) NOT NULL,
  `selling_price` decimal(15,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `min_stock` int(11) NOT NULL DEFAULT 5,
  `unit` varchar(20) DEFAULT 'Pcs',
  `photo` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `products`
--

INSERT INTO `products` (`id`, `barcode`, `name`, `category_id`, `supplier_id`, `purchase_price`, `selling_price`, `stock`, `min_stock`, `unit`, `photo`, `created_at`, `updated_at`) VALUES
(1, '8991001101700', 'Indomie Goreng', 5, 1, 2500.00, 3500.00, 145, 20, 'Pcs', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(2, '8991001101710', 'Indomie Soto', 5, 1, 2500.00, 3500.00, 120, 20, 'Pcs', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(3, '8991001101720', 'Indomie Ayam Bawang', 5, 1, 2500.00, 3500.00, 98, 20, 'Pcs', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(4, '8998866200013', 'Mie Sedaap Goreng', 5, 2, 2600.00, 3600.00, 85, 15, 'Pcs', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(5, '8998866200020', 'Mie Sedaap Soto', 5, 2, 2600.00, 3600.00, 8, 15, 'Pcs', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(6, '8998866200037', 'Sarimi Isi 2 Ayam Bawang', 5, 2, 3000.00, 4200.00, 60, 12, 'Bungkus', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(7, '8991001105005', 'Pop Mie Ayam', 5, 1, 4000.00, 5500.00, 45, 10, 'Cup', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(8, '8992750025000', 'Teh Botol Sosro 350ml', 2, 4, 3500.00, 5000.00, 62, 12, 'Botol', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(9, '8992750080015', 'Teh Kotak Sosro 300ml', 2, 4, 4000.00, 5500.00, 45, 10, 'Kotak', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(10, '8993175101144', 'Aqua 600ml', 2, 4, 2500.00, 4000.00, 100, 20, 'Botol', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(11, '8993175101151', 'Aqua 1500ml', 2, 4, 5000.00, 7000.00, 55, 10, 'Botol', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(12, '8991001103000', 'Kopi Kapal Api Sachet', 2, 1, 1500.00, 2500.00, 180, 30, 'Sachet', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(13, '8999909035111', 'Kopi Good Day Cappuccino', 2, 1, 1800.00, 3000.00, 95, 20, 'Sachet', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(14, '8992775101017', 'Frisian Flag Susu Kental Manis', 2, 6, 9000.00, 12000.00, 40, 10, 'Kaleng', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(15, '8992696404416', 'Pocari Sweat 500ml', 2, 4, 5000.00, 7500.00, 35, 10, 'Botol', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(16, '8992752100002', 'Fanta Strawberry 390ml', 2, 4, 3500.00, 5000.00, 42, 10, 'Botol', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(17, '8999999123456', 'Rokok Surya 12', 3, 2, 22000.00, 25000.00, 42, 10, 'Bungkus', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(18, '8999999123463', 'Rokok Gudang Garam Surya 16', 3, 2, 27000.00, 31000.00, 28, 8, 'Bungkus', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(19, '8999999123470', 'Rokok Djarum Super 12', 3, 2, 23000.00, 26500.00, 35, 10, 'Bungkus', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(20, '8999999123487', 'Rokok Marlboro Merah', 3, 4, 32000.00, 37000.00, 20, 5, 'Bungkus', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(21, '8999999123494', 'Rokok Sampoerna Mild 16', 3, 2, 28000.00, 32000.00, 25, 8, 'Bungkus', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(22, '8992388130013', 'Chitato Sapi Panggang 68g', 1, 3, 8000.00, 11000.00, 40, 10, 'Bungkus', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(23, '8992388130020', 'Lays Rumput Laut 68g', 1, 3, 8500.00, 11500.00, 33, 10, 'Bungkus', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(24, '8991102300018', 'Oreo Original 133g', 1, 3, 8000.00, 10500.00, 50, 10, 'Bungkus', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(25, '8991102300025', 'Better Vanilla 100g', 1, 3, 7000.00, 9500.00, 42, 10, 'Bungkus', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(26, '8992696404614', 'Tango Wafer Vanilla', 1, 3, 7500.00, 10000.00, 60, 12, 'Bungkus', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(27, '8991001102003', 'Beng Beng Cokelat', 1, 3, 2000.00, 3000.00, 150, 30, 'Pcs', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(28, '8996001310129', 'Kacang Garuda 80g', 1, 3, 5000.00, 7500.00, 55, 15, 'Bungkus', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(29, '8999999000111', 'Sabun Lifebuoy Batang', 7, 5, 3500.00, 5000.00, 75, 15, 'Pcs', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(30, '8999999000128', 'Sabun Lux Cair 250ml', 7, 5, 12000.00, 16000.00, 30, 8, 'Botol', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(31, '8999999000135', 'Shampo Sunsilk 170ml', 7, 5, 15000.00, 19500.00, 25, 8, 'Botol', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(32, '8999999000142', 'Pasta Gigi Pepsodent 190g', 7, 5, 11000.00, 14500.00, 40, 10, 'Pcs', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(33, '8999999000159', 'Sabun Cuci Muka Garnier', 7, 5, 18000.00, 24000.00, 18, 6, 'Pcs', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(34, '8999999000203', 'Rinso Deterjen 770g', 4, 5, 18000.00, 23000.00, 22, 8, 'Bungkus', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(35, '8999999000210', 'Sunlight Jeruk Nipis 400ml', 4, 5, 9000.00, 12500.00, 35, 10, 'Botol', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(36, '8999999000227', 'So Klin Pewangi 800ml', 4, 5, 14000.00, 18500.00, 18, 8, 'Botol', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(37, '8999999000234', 'Baygon Aerosol 200ml', 4, 5, 20000.00, 26000.00, 15, 5, 'Kaleng', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(38, '8999999000302', 'Buku Tulis Sidu 38 Lembar', 6, 4, 3500.00, 5000.00, 80, 20, 'Pcs', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(39, '8999999000319', 'Pulpen Standar AE7', 6, 4, 2000.00, 3000.00, 120, 25, 'Pcs', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22'),
(40, '8999999000326', 'Pensil 2B Faber Castell', 6, 4, 2500.00, 4000.00, 65, 15, 'Pcs', NULL, '2026-09-18 04:44:22', '2026-09-18 04:44:22');

-- --------------------------------------------------------

--
-- Struktur dari tabel `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'Admin', 'Akses penuh ke semua modul'),
(2, 'Kasir', 'Transaksi penjualan & laporan pribadi');

-- --------------------------------------------------------

--
-- Struktur dari tabel `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'app_name', 'Toko Aneka Jaya', '2026-09-18 04:44:21'),
(2, 'theme', 'light', '2026-09-18 04:44:21'),
(3, 'store_address', 'Jl. Raya Cibubur No. 88, Kel. Cibubur, Kec. Ciracas, Jakarta Timur 13720', '2026-09-18 04:44:21'),
(4, 'store_phone', '0812-8765-4321', '2026-09-18 04:44:21'),
(5, 'store_tagline', 'Lengkap • Murah • Terpercaya', '2026-09-18 04:44:21');

-- --------------------------------------------------------

--
-- Struktur dari tabel `stock_mutations`
--

CREATE TABLE `stock_mutations` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('in','out','adjustment') NOT NULL,
  `quantity` int(11) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `stock_mutations`
--

INSERT INTO `stock_mutations` (`id`, `product_id`, `user_id`, `type`, `quantity`, `reference`, `notes`, `created_at`) VALUES
(1, 1, 1, 'in', 200, 'PO-001', 'Restock Indomie Goreng dari PT Indofood', '2026-08-29 04:44:23'),
(2, 2, 1, 'in', 150, 'PO-001', 'Restock Indomie Soto', '2026-08-29 04:44:23'),
(3, 3, 1, 'in', 100, 'PO-001', 'Restock Indomie Ayam Bawang', '2026-08-29 04:44:23'),
(4, 17, 1, 'in', 50, 'PO-002', 'Restock Rokok Surya dari Wings', '2026-09-03 04:44:23'),
(5, 20, 1, 'in', 30, 'PO-002', 'Restock Marlboro', '2026-09-03 04:44:23'),
(6, 29, 1, 'in', 100, 'PO-003', 'Restock Sabun Lifebuoy dari Unilever', '2026-09-06 04:44:23'),
(7, 34, 1, 'in', 40, 'PO-003', 'Restock Rinso dari Unilever', '2026-09-06 04:44:23'),
(8, 38, 1, 'in', 100, 'PO-004', 'Restock ATK dari Distributor Sumber Rejeki', '2026-09-08 04:44:23');

-- --------------------------------------------------------

--
-- Struktur dari tabel `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`, `created_at`) VALUES
(1, 'PT Indofood Sukses Makmur', 'Bapak Hendra', '0812-1111-2222', 'sales@indofood.co.id', 'Kawasan Industri Cikarang, Bekasi', '2026-09-18 04:44:22'),
(2, 'PT Wings Food', 'Ibu Sari', '0813-3333-4444', 'order@wings.co.id', 'Jababeka, Cikarang', '2026-09-18 04:44:22'),
(3, 'PT Mayora Indah', 'Bapak Dedi', '0855-5555-6666', 'cs@mayora.co.id', 'Tangerang, Banten', '2026-09-18 04:44:22'),
(4, 'Distributor Sumber Rejeki', 'Bapak Agus', '0857-7777-8888', 'sumberrejeki@gmail.com', 'Pasar Induk Caringin, Bandung', '2026-09-18 04:44:22'),
(5, 'PT Unilever Indonesia', 'Ibu Maya', '0812-9999-0000', 'dist@unilever.co.id', 'Tangerang, Banten', '2026-09-18 04:44:22'),
(6, 'PT Kalbe Nutritionals', 'Bapak Rudi', '0811-2222-3333', 'sales@kalbe.co.id', 'Cikarang, Bekasi', '2026-09-18 04:44:22');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name_snapshot` varchar(100) DEFAULT NULL,
  `transaction_date` datetime DEFAULT current_timestamp(),
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `final_amount` decimal(15,2) NOT NULL,
  `total_profit` decimal(15,2) DEFAULT 0.00,
  `points_earned` int(11) DEFAULT 0,
  `points_before` int(11) DEFAULT 0,
  `points_after` int(11) DEFAULT 0,
  `payment_method` enum('cash','qris','transfer','debit') NOT NULL DEFAULT 'cash',
  `cash_received` decimal(15,2) DEFAULT 0.00,
  `change_amount` decimal(15,2) DEFAULT 0.00,
  `payment_status` enum('paid','unpaid','pending') DEFAULT 'paid',
  `status` enum('completed','suspended','cancelled') DEFAULT 'completed',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transactions`
--

INSERT INTO `transactions` (`id`, `invoice_number`, `cashier_id`, `customer_id`, `customer_name_snapshot`, `transaction_date`, `total_amount`, `discount_amount`, `tax_amount`, `final_amount`, `total_profit`, `points_earned`, `points_before`, `points_after`, `payment_method`, `cash_received`, `change_amount`, `payment_status`, `status`, `notes`) VALUES
(1, 'INV-20260918-0001', 2, 1, 'Ibu Ani Suryani', '2026-09-18 08:15:22', 21500.00, 0.00, 0.00, 21500.00, 6700.00, 21, 0, 0, 'cash', 25000.00, 3500.00, 'paid', 'completed', NULL),
(2, 'INV-20260918-0002', 2, NULL, NULL, '2026-09-18 08:42:11', 15000.00, 0.00, 0.00, 15000.00, 4500.00, 0, 0, 0, 'qris', 0.00, 0.00, 'paid', 'completed', NULL),
(3, 'INV-20260918-0003', 2, 3, 'Ibu Wati Sukma', '2026-09-18 09:20:45', 48500.00, 2000.00, 0.00, 46500.00, 15200.00, 46, 0, 0, 'cash', 50000.00, 3500.00, 'paid', 'completed', NULL),
(4, 'INV-20260918-0004', 2, NULL, NULL, '2026-09-18 10:05:33', 22000.00, 0.00, 0.00, 22000.00, 6300.00, 0, 0, 0, 'cash', 25000.00, 3000.00, 'paid', 'completed', NULL),
(5, 'INV-20260918-0005', 2, 2, 'Bapak Joko Widodo', '2026-09-18 11:15:19', 38000.00, 0.00, 0.00, 38000.00, 11400.00, 38, 0, 0, 'qris', 0.00, 0.00, 'paid', 'completed', NULL),
(6, 'INV-20260918-0006', 2, NULL, NULL, '2026-09-18 12:30:07', 10500.00, 0.00, 0.00, 10500.00, 2500.00, 0, 0, 0, 'cash', 11000.00, 500.00, 'paid', 'completed', NULL),
(7, 'INV-20260918-0007', 3, 5, 'Bu Rina Melati', '2026-09-18 13:12:41', 74000.00, 4000.00, 0.00, 70000.00, 19500.00, 70, 0, 0, 'debit', 0.00, 0.00, 'paid', 'completed', NULL),
(8, 'INV-20260918-0008', 3, NULL, NULL, '2026-09-18 14:22:58', 18500.00, 0.00, 0.00, 18500.00, 4800.00, 0, 0, 0, 'cash', 20000.00, 1500.00, 'paid', 'completed', NULL),
(9, 'INV-20260918-0009', 2, 4, 'Pak Hadi Prayitno', '2026-09-18 15:45:12', 31000.00, 0.00, 0.00, 31000.00, 9600.00, 31, 0, 0, 'qris', 0.00, 0.00, 'paid', 'completed', NULL),
(10, 'INV-20260918-0010', 3, NULL, NULL, '2026-09-18 17:30:33', 55000.00, 5000.00, 0.00, 50000.00, 14500.00, 0, 0, 0, 'cash', 55000.00, 5000.00, 'paid', 'completed', NULL),
(11, 'INV-20260917-0001', 2, 4, 'Pak Hadi Prayitno', '2026-09-17 08:30:00', 25000.00, 0.00, 0.00, 25000.00, 7200.00, 25, 0, 0, 'cash', 25000.00, 0.00, 'paid', 'completed', NULL),
(12, 'INV-20260917-0002', 2, 1, 'Ibu Ani Suryani', '2026-09-17 10:15:00', 42000.00, 0.00, 0.00, 42000.00, 12600.00, 42, 0, 0, 'qris', 0.00, 0.00, 'paid', 'completed', NULL),
(13, 'INV-20260917-0003', 2, NULL, NULL, '2026-09-17 12:20:00', 18500.00, 0.00, 0.00, 18500.00, 4800.00, 0, 0, 0, 'cash', 20000.00, 1500.00, 'paid', 'completed', NULL),
(14, 'INV-20260917-0004', 3, 3, 'Ibu Wati Sukma', '2026-09-17 14:45:00', 63000.00, 0.00, 0.00, 63000.00, 18000.00, 63, 0, 0, 'debit', 0.00, 0.00, 'paid', 'completed', NULL),
(15, 'INV-20260917-0005', 3, NULL, NULL, '2026-09-17 17:00:00', 33000.00, 0.00, 0.00, 33000.00, 9600.00, 0, 0, 0, 'cash', 35000.00, 2000.00, 'paid', 'completed', NULL),
(16, 'INV-20260917-0006', 2, 5, 'Bu Rina Melati', '2026-09-17 19:30:00', 48000.00, 0.00, 0.00, 48000.00, 14400.00, 48, 0, 0, 'qris', 0.00, 0.00, 'paid', 'completed', NULL),
(17, 'INV-20260916-0001', 2, NULL, NULL, '2026-09-16 08:45:00', 28500.00, 0.00, 0.00, 28500.00, 8100.00, 0, 0, 0, 'cash', 30000.00, 1500.00, 'paid', 'completed', NULL),
(18, 'INV-20260916-0002', 2, 2, 'Bapak Joko Widodo', '2026-09-16 11:30:00', 52000.00, 0.00, 0.00, 52000.00, 15600.00, 52, 0, 0, 'transfer', 0.00, 0.00, 'paid', 'completed', NULL),
(19, 'INV-20260916-0003', 2, NULL, NULL, '2026-09-16 13:15:00', 21000.00, 0.00, 0.00, 21000.00, 6000.00, 0, 0, 0, 'cash', 25000.00, 4000.00, 'paid', 'completed', NULL),
(20, 'INV-20260916-0004', 3, 7, 'Bu Sri Wahyuni', '2026-09-16 16:00:00', 67000.00, 0.00, 0.00, 67000.00, 19500.00, 67, 0, 0, 'qris', 0.00, 0.00, 'paid', 'completed', NULL),
(21, 'INV-20260916-0005', 3, NULL, NULL, '2026-09-16 18:30:00', 35000.00, 0.00, 0.00, 35000.00, 10200.00, 0, 0, 0, 'cash', 50000.00, 15000.00, 'paid', 'completed', NULL),
(22, 'INV-20260915-0001', 2, NULL, NULL, '2026-09-15 09:00:00', 32000.00, 0.00, 0.00, 32000.00, 9300.00, 0, 0, 0, 'cash', 35000.00, 3000.00, 'paid', 'completed', NULL),
(23, 'INV-20260915-0002', 2, 6, 'Pak Dedi Kurniawan', '2026-09-15 12:30:00', 44500.00, 0.00, 0.00, 44500.00, 13200.00, 44, 0, 0, 'qris', 0.00, 0.00, 'paid', 'completed', NULL),
(24, 'INV-20260915-0003', 2, NULL, NULL, '2026-09-15 15:45:00', 55000.00, 0.00, 0.00, 55000.00, 15900.00, 0, 0, 0, 'cash', 60000.00, 5000.00, 'paid', 'completed', NULL),
(25, 'INV-20260915-0004', 3, 8, 'Pak Anto Setiawan', '2026-09-15 18:20:00', 38000.00, 0.00, 0.00, 38000.00, 11100.00, 38, 0, 0, 'debit', 0.00, 0.00, 'paid', 'completed', NULL),
(26, 'INV-20260915-0005', 3, NULL, NULL, '2026-09-15 20:00:00', 28000.00, 0.00, 0.00, 28000.00, 8400.00, 0, 0, 0, 'cash', 30000.00, 2000.00, 'paid', 'completed', NULL),
(27, 'INV-20260914-0001', 2, NULL, NULL, '2026-09-14 10:00:00', 22000.00, 0.00, 0.00, 22000.00, 6300.00, 0, 0, 0, 'cash', 25000.00, 3000.00, 'paid', 'completed', NULL),
(28, 'INV-20260914-0002', 2, 9, 'Bu Dewi Lestari', '2026-09-14 13:00:00', 71000.00, 0.00, 0.00, 71000.00, 21000.00, 71, 0, 0, 'qris', 0.00, 0.00, 'paid', 'completed', NULL),
(29, 'INV-20260914-0003', 2, NULL, NULL, '2026-09-14 17:30:00', 35000.00, 0.00, 0.00, 35000.00, 10200.00, 0, 0, 0, 'cash', 50000.00, 15000.00, 'paid', 'completed', NULL),
(30, 'INV-20260914-0004', 3, 10, 'Pak Budi Hartono', '2026-09-14 19:45:00', 46000.00, 0.00, 0.00, 46000.00, 13800.00, 46, 0, 0, 'transfer', 0.00, 0.00, 'paid', 'completed', NULL),
(31, 'INV-20260913-0001', 2, NULL, NULL, '2026-09-13 08:50:00', 18500.00, 0.00, 0.00, 18500.00, 4800.00, 0, 0, 0, 'cash', 20000.00, 1500.00, 'paid', 'completed', NULL),
(32, 'INV-20260913-0002', 2, 11, 'Bu Nur Aini', '2026-09-13 11:15:00', 59000.00, 0.00, 0.00, 59000.00, 17400.00, 59, 0, 0, 'qris', 0.00, 0.00, 'paid', 'completed', NULL),
(33, 'INV-20260913-0003', 3, NULL, NULL, '2026-09-13 14:30:00', 24500.00, 0.00, 0.00, 24500.00, 6900.00, 0, 0, 0, 'cash', 25000.00, 500.00, 'paid', 'completed', NULL),
(34, 'INV-20260913-0004', 3, 12, 'Pak Bambang Sutrisno', '2026-09-13 18:00:00', 41000.00, 0.00, 0.00, 41000.00, 12300.00, 41, 0, 0, 'debit', 0.00, 0.00, 'paid', 'completed', NULL),
(35, 'INV-20260912-0001', 2, NULL, NULL, '2026-09-12 09:30:00', 30000.00, 0.00, 0.00, 30000.00, 8700.00, 0, 0, 0, 'cash', 30000.00, 0.00, 'paid', 'completed', NULL),
(36, 'INV-20260912-0002', 2, 1, 'Ibu Ani Suryani', '2026-09-12 12:45:00', 52500.00, 0.00, 0.00, 52500.00, 15300.00, 52, 0, 0, 'qris', 0.00, 0.00, 'paid', 'completed', NULL),
(37, 'INV-20260912-0003', 2, NULL, NULL, '2026-09-12 16:15:00', 28000.00, 0.00, 0.00, 28000.00, 8400.00, 0, 0, 0, 'cash', 30000.00, 2000.00, 'paid', 'completed', NULL),
(38, 'INV-20260912-0004', 3, 5, 'Bu Rina Melati', '2026-09-12 19:00:00', 68000.00, 0.00, 0.00, 68000.00, 20100.00, 68, 0, 0, 'transfer', 0.00, 0.00, 'paid', 'completed', NULL),
(39, 'INV-20260911-0001', 2, NULL, NULL, '2026-09-11 10:00:00', 35000.00, 0.00, 0.00, 35000.00, 10200.00, 0, 0, 0, 'cash', 40000.00, 5000.00, 'paid', 'completed', NULL),
(40, 'INV-20260911-0002', 2, 3, 'Ibu Wati Sukma', '2026-09-11 14:00:00', 47000.00, 0.00, 0.00, 47000.00, 13800.00, 47, 0, 0, 'qris', 0.00, 0.00, 'paid', 'completed', NULL),
(41, 'INV-20260910-0001', 2, NULL, NULL, '2026-09-10 09:00:00', 22500.00, 0.00, 0.00, 22500.00, 6300.00, 0, 0, 0, 'cash', 25000.00, 2500.00, 'paid', 'completed', NULL),
(42, 'INV-20260910-0002', 3, 4, 'Pak Hadi Prayitno', '2026-09-10 15:00:00', 61000.00, 0.00, 0.00, 61000.00, 18300.00, 61, 0, 0, 'debit', 0.00, 0.00, 'paid', 'completed', NULL),
(43, 'INV-20260909-0001', 2, NULL, NULL, '2026-09-09 11:00:00', 18500.00, 0.00, 0.00, 18500.00, 4800.00, 0, 0, 0, 'cash', 20000.00, 1500.00, 'paid', 'completed', NULL),
(44, 'INV-20260909-0002', 2, 6, 'Pak Dedi Kurniawan', '2026-09-09 16:45:00', 54000.00, 0.00, 0.00, 54000.00, 15900.00, 54, 0, 0, 'qris', 0.00, 0.00, 'paid', 'completed', NULL),
(45, 'INV-20260908-0001', 3, NULL, NULL, '2026-09-08 08:30:00', 30000.00, 0.00, 0.00, 30000.00, 9000.00, 0, 0, 0, 'cash', 50000.00, 20000.00, 'paid', 'completed', NULL),
(46, 'INV-20260908-0002', 3, 8, 'Pak Anto Setiawan', '2026-09-08 15:15:00', 42500.00, 0.00, 0.00, 42500.00, 12300.00, 42, 0, 0, 'transfer', 0.00, 0.00, 'paid', 'completed', NULL),
(47, 'INV-20260907-0001', 2, NULL, NULL, '2026-09-07 12:00:00', 25500.00, 0.00, 0.00, 25500.00, 7200.00, 0, 0, 0, 'cash', 30000.00, 4500.00, 'paid', 'completed', NULL),
(48, 'INV-20260907-0002', 2, 9, 'Bu Dewi Lestari', '2026-09-07 18:30:00', 73000.00, 0.00, 0.00, 73000.00, 21600.00, 73, 0, 0, 'qris', 0.00, 0.00, 'paid', 'completed', NULL),
(49, 'INV-20260907-0003', 2, NULL, NULL, '2026-09-07 20:00:00', 22000.00, 0.00, 0.00, 22000.00, 6300.00, 0, 0, 0, 'cash', 25000.00, 3000.00, 'paid', 'completed', NULL),
(50, 'INV-20260906-0001', 3, 1, 'Ibu Ani Suryani', '2026-09-06 09:45:00', 19500.00, 0.00, 0.00, 19500.00, 5100.00, 19, 0, 0, 'cash', 20000.00, 500.00, 'paid', 'completed', NULL),
(51, 'INV-20260906-0002', 3, NULL, NULL, '2026-09-06 14:15:00', 48000.00, 0.00, 0.00, 48000.00, 14400.00, 0, 0, 0, 'debit', 0.00, 0.00, 'paid', 'completed', NULL),
(52, 'INV-20260905-0001', 2, NULL, NULL, '2026-09-05 11:30:00', 26500.00, 0.00, 0.00, 26500.00, 7500.00, 0, 0, 0, 'cash', 30000.00, 3500.00, 'paid', 'completed', NULL),
(53, 'INV-20260905-0002', 2, 2, 'Bapak Joko Widodo', '2026-09-05 17:00:00', 58000.00, 0.00, 0.00, 58000.00, 17400.00, 58, 0, 0, 'qris', 0.00, 0.00, 'paid', 'completed', NULL),
(54, 'INV-20260904-0001', 2, NULL, NULL, '2026-09-04 10:30:00', 33000.00, 0.00, 0.00, 33000.00, 9600.00, 0, 0, 0, 'cash', 35000.00, 2000.00, 'paid', 'completed', NULL),
(55, 'INV-20260904-0002', 3, 11, 'Bu Nur Aini', '2026-09-04 16:00:00', 45000.00, 0.00, 0.00, 45000.00, 13500.00, 45, 0, 0, 'transfer', 0.00, 0.00, 'paid', 'completed', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaction_items`
--

CREATE TABLE `transaction_items` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `selling_price` decimal(15,2) NOT NULL,
  `purchase_price_at_time` decimal(15,2) DEFAULT 0.00,
  `discount_per_item` decimal(15,2) DEFAULT 0.00,
  `subtotal` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaction_items`
--

INSERT INTO `transaction_items` (`id`, `transaction_id`, `product_id`, `quantity`, `selling_price`, `purchase_price_at_time`, `discount_per_item`, `subtotal`) VALUES
(1, 1, 1, 2, 3500.00, 2500.00, 0.00, 7000.00),
(2, 1, 8, 1, 5000.00, 3500.00, 0.00, 5000.00),
(3, 1, 13, 3, 3000.00, 1800.00, 0.00, 9000.00),
(4, 2, 10, 2, 4000.00, 2500.00, 0.00, 8000.00),
(5, 2, 11, 1, 7000.00, 5000.00, 0.00, 7000.00),
(6, 3, 17, 1, 25000.00, 22000.00, 0.00, 25000.00),
(7, 3, 22, 2, 11000.00, 8000.00, 0.00, 22000.00),
(8, 3, 12, 1, 2500.00, 1500.00, 0.00, 2500.00),
(9, 4, 29, 2, 5000.00, 3500.00, 0.00, 10000.00),
(10, 4, 32, 1, 14500.00, 11000.00, 0.00, 14500.00),
(11, 5, 1, 4, 3500.00, 2500.00, 0.00, 14000.00),
(12, 5, 4, 4, 3600.00, 2600.00, 0.00, 14400.00),
(13, 5, 27, 4, 3000.00, 2000.00, 0.00, 12000.00),
(14, 6, 24, 1, 10500.00, 8000.00, 0.00, 10500.00),
(15, 7, 18, 1, 31000.00, 27000.00, 0.00, 31000.00),
(16, 7, 30, 2, 16000.00, 12000.00, 0.00, 32000.00),
(17, 7, 33, 1, 24000.00, 18000.00, 0.00, 24000.00),
(18, 8, 2, 3, 3500.00, 2500.00, 0.00, 10500.00),
(19, 8, 8, 1, 5000.00, 3500.00, 0.00, 5000.00),
(20, 8, 39, 1, 3000.00, 2000.00, 0.00, 3000.00),
(21, 9, 19, 1, 26500.00, 23000.00, 0.00, 26500.00),
(22, 9, 27, 1, 3000.00, 2000.00, 0.00, 3000.00),
(23, 10, 9, 3, 5500.00, 4000.00, 0.00, 16500.00),
(24, 10, 23, 2, 11500.00, 8500.00, 0.00, 23000.00),
(25, 10, 26, 1, 10000.00, 7500.00, 0.00, 10000.00),
(26, 11, 1, 5, 3500.00, 2500.00, 0.00, 17500.00),
(27, 11, 12, 3, 2500.00, 1500.00, 0.00, 7500.00),
(28, 12, 17, 1, 25000.00, 22000.00, 0.00, 25000.00),
(29, 12, 8, 2, 5000.00, 3500.00, 0.00, 10000.00),
(30, 12, 10, 2, 4000.00, 2500.00, 0.00, 8000.00),
(31, 13, 25, 1, 9500.00, 7000.00, 0.00, 9500.00),
(32, 13, 12, 3, 2500.00, 1500.00, 0.00, 7500.00),
(33, 14, 20, 1, 37000.00, 32000.00, 0.00, 37000.00),
(34, 14, 22, 1, 11000.00, 8000.00, 0.00, 11000.00),
(35, 14, 30, 1, 16000.00, 12000.00, 0.00, 16000.00),
(36, 15, 6, 3, 4200.00, 3000.00, 0.00, 12600.00),
(37, 15, 10, 2, 4000.00, 2500.00, 0.00, 8000.00),
(38, 15, 27, 4, 3000.00, 2000.00, 0.00, 12000.00),
(39, 16, 18, 1, 31000.00, 27000.00, 0.00, 31000.00),
(40, 16, 26, 1, 10000.00, 7500.00, 0.00, 10000.00),
(41, 16, 13, 2, 3000.00, 1800.00, 0.00, 6000.00),
(42, 17, 1, 3, 3500.00, 2500.00, 0.00, 10500.00),
(43, 17, 4, 3, 3600.00, 2600.00, 0.00, 10800.00),
(44, 17, 12, 3, 2500.00, 1500.00, 0.00, 7500.00),
(45, 18, 20, 1, 37000.00, 32000.00, 0.00, 37000.00),
(46, 18, 34, 1, 23000.00, 18000.00, 0.00, 23000.00),
(47, 19, 8, 2, 5000.00, 3500.00, 0.00, 10000.00),
(48, 19, 22, 1, 11000.00, 8000.00, 0.00, 11000.00),
(49, 20, 19, 1, 26500.00, 23000.00, 0.00, 26500.00),
(50, 20, 23, 2, 11500.00, 8500.00, 0.00, 23000.00),
(51, 20, 36, 1, 18500.00, 14000.00, 0.00, 18500.00),
(52, 21, 17, 1, 25000.00, 22000.00, 0.00, 25000.00),
(53, 21, 10, 1, 4000.00, 2500.00, 0.00, 4000.00),
(54, 21, 27, 2, 3000.00, 2000.00, 0.00, 6000.00),
(55, 22, 17, 1, 25000.00, 22000.00, 0.00, 25000.00),
(56, 22, 10, 1, 4000.00, 2500.00, 0.00, 4000.00),
(57, 22, 39, 1, 3000.00, 2000.00, 0.00, 3000.00),
(58, 23, 1, 5, 3500.00, 2500.00, 0.00, 17500.00),
(59, 23, 8, 2, 5000.00, 3500.00, 0.00, 10000.00),
(60, 23, 24, 1, 10500.00, 8000.00, 0.00, 10500.00),
(61, 23, 12, 2, 2500.00, 1500.00, 0.00, 5000.00),
(62, 24, 20, 1, 37000.00, 32000.00, 0.00, 37000.00),
(63, 24, 29, 2, 5000.00, 3500.00, 0.00, 10000.00),
(64, 24, 40, 2, 4000.00, 2500.00, 0.00, 8000.00),
(65, 25, 4, 3, 3600.00, 2600.00, 0.00, 10800.00),
(66, 25, 30, 1, 16000.00, 12000.00, 0.00, 16000.00),
(67, 25, 32, 1, 14500.00, 11000.00, 0.00, 14500.00),
(68, 26, 13, 5, 3000.00, 1800.00, 0.00, 15000.00),
(69, 26, 26, 1, 10000.00, 7500.00, 0.00, 10000.00),
(70, 26, 39, 1, 3000.00, 2000.00, 0.00, 3000.00),
(71, 27, 25, 2, 9500.00, 7000.00, 0.00, 19000.00),
(72, 27, 39, 1, 3000.00, 2000.00, 0.00, 3000.00),
(73, 28, 18, 1, 31000.00, 27000.00, 0.00, 31000.00),
(74, 28, 23, 2, 11500.00, 8500.00, 0.00, 23000.00),
(75, 28, 30, 1, 16000.00, 12000.00, 0.00, 16000.00),
(76, 29, 17, 1, 25000.00, 22000.00, 0.00, 25000.00),
(77, 29, 10, 2, 4000.00, 2500.00, 0.00, 8000.00),
(78, 29, 39, 1, 3000.00, 2000.00, 0.00, 3000.00),
(79, 30, 1, 4, 3500.00, 2500.00, 0.00, 14000.00),
(80, 30, 8, 3, 5000.00, 3500.00, 0.00, 15000.00),
(81, 30, 29, 3, 5000.00, 3500.00, 0.00, 15000.00),
(82, 31, 12, 5, 2500.00, 1500.00, 0.00, 12500.00),
(83, 31, 39, 2, 3000.00, 2000.00, 0.00, 6000.00),
(84, 32, 20, 1, 37000.00, 32000.00, 0.00, 37000.00),
(85, 32, 26, 1, 10000.00, 7500.00, 0.00, 10000.00),
(86, 32, 30, 1, 16000.00, 12000.00, 0.00, 16000.00),
(87, 33, 2, 5, 3500.00, 2500.00, 0.00, 17500.00),
(88, 33, 11, 1, 7000.00, 5000.00, 0.00, 7000.00),
(89, 34, 19, 1, 26500.00, 23000.00, 0.00, 26500.00),
(90, 34, 24, 1, 10500.00, 8000.00, 0.00, 10500.00),
(91, 34, 40, 1, 4000.00, 2500.00, 0.00, 4000.00),
(92, 35, 17, 1, 25000.00, 22000.00, 0.00, 25000.00),
(93, 35, 8, 1, 5000.00, 3500.00, 0.00, 5000.00),
(94, 36, 18, 1, 31000.00, 27000.00, 0.00, 31000.00),
(95, 36, 23, 1, 11500.00, 8500.00, 0.00, 11500.00),
(96, 36, 35, 1, 12500.00, 9000.00, 0.00, 12500.00),
(97, 37, 22, 2, 11000.00, 8000.00, 0.00, 22000.00),
(98, 37, 12, 2, 2500.00, 1500.00, 0.00, 5000.00),
(99, 38, 20, 1, 37000.00, 32000.00, 0.00, 37000.00),
(100, 38, 30, 1, 16000.00, 12000.00, 0.00, 16000.00),
(101, 38, 29, 2, 5000.00, 3500.00, 0.00, 10000.00),
(102, 39, 1, 5, 3500.00, 2500.00, 0.00, 17500.00),
(103, 39, 8, 2, 5000.00, 3500.00, 0.00, 10000.00),
(104, 39, 12, 3, 2500.00, 1500.00, 0.00, 7500.00),
(105, 40, 20, 1, 37000.00, 32000.00, 0.00, 37000.00),
(106, 40, 13, 3, 3000.00, 1800.00, 0.00, 9000.00),
(107, 41, 2, 3, 3500.00, 2500.00, 0.00, 10500.00),
(108, 41, 12, 3, 2500.00, 1500.00, 0.00, 7500.00),
(109, 41, 39, 1, 3000.00, 2000.00, 0.00, 3000.00),
(110, 42, 18, 1, 31000.00, 27000.00, 0.00, 31000.00),
(111, 42, 23, 2, 11500.00, 8500.00, 0.00, 23000.00),
(112, 42, 30, 1, 16000.00, 12000.00, 0.00, 16000.00),
(113, 43, 24, 1, 10500.00, 8000.00, 0.00, 10500.00),
(114, 43, 11, 1, 7000.00, 5000.00, 0.00, 7000.00),
(115, 44, 17, 1, 25000.00, 22000.00, 0.00, 25000.00),
(116, 44, 22, 2, 11000.00, 8000.00, 0.00, 22000.00),
(117, 44, 10, 2, 4000.00, 2500.00, 0.00, 8000.00),
(118, 45, 19, 1, 26500.00, 23000.00, 0.00, 26500.00),
(119, 45, 39, 1, 3000.00, 2000.00, 0.00, 3000.00),
(120, 46, 18, 1, 31000.00, 27000.00, 0.00, 31000.00),
(121, 46, 35, 1, 12500.00, 9000.00, 0.00, 12500.00),
(122, 47, 1, 4, 3500.00, 2500.00, 0.00, 14000.00),
(123, 47, 12, 3, 2500.00, 1500.00, 0.00, 7500.00),
(124, 47, 10, 1, 4000.00, 2500.00, 0.00, 4000.00),
(125, 48, 20, 1, 37000.00, 32000.00, 0.00, 37000.00),
(126, 48, 24, 1, 10500.00, 8000.00, 0.00, 10500.00),
(127, 48, 30, 2, 16000.00, 12000.00, 0.00, 32000.00),
(128, 49, 22, 1, 11000.00, 8000.00, 0.00, 11000.00),
(129, 49, 26, 1, 10000.00, 7500.00, 0.00, 10000.00),
(130, 50, 1, 2, 3500.00, 2500.00, 0.00, 7000.00),
(131, 50, 12, 3, 2500.00, 1500.00, 0.00, 7500.00),
(132, 50, 39, 1, 3000.00, 2000.00, 0.00, 3000.00),
(133, 51, 20, 1, 37000.00, 32000.00, 0.00, 37000.00),
(134, 51, 13, 3, 3000.00, 1800.00, 0.00, 9000.00),
(135, 52, 19, 1, 26500.00, 23000.00, 0.00, 26500.00),
(136, 53, 18, 1, 31000.00, 27000.00, 0.00, 31000.00),
(137, 53, 23, 2, 11500.00, 8500.00, 0.00, 23000.00),
(138, 53, 12, 2, 2500.00, 1500.00, 0.00, 5000.00),
(139, 54, 1, 5, 3500.00, 2500.00, 0.00, 17500.00),
(140, 54, 30, 1, 16000.00, 12000.00, 0.00, 16000.00),
(141, 55, 20, 1, 37000.00, 32000.00, 0.00, 37000.00),
(142, 55, 10, 2, 4000.00, 2500.00, 0.00, 8000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role_id` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role_id`, `is_active`, `created_at`) VALUES
(1, 'admin', '$2y$10$uRklr9x5aAa6u5ssztUutOp.RasEqCm1bDZnzjLYHM9hzwukBMse6', 'Andi Wijaya', 1, 1, '2026-09-18 04:44:21'),
(2, 'kasir1', '$2y$10$XWwgAJ46zaHlRkPjMWAlrOMZyE1m.3taf9GfzvFFIQvy7jIyKO4fm', 'Siti Nurhaliza', 2, 1, '2026-09-18 04:44:21'),
(3, 'kasir2', '$2y$10$qLbaPJUaSRnWUxunY.5yXewR/Uf8fVMgVdxcurPQ61pJvaa7DHAqy', 'Rina Marlina', 2, 1, '2026-09-18 04:44:21');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `loyalty_point_history`
--
ALTER TABLE `loyalty_point_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indeks untuk tabel `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `idx_products_barcode` (`barcode`),
  ADD KEY `idx_products_name` (`name`);

--
-- Indeks untuk tabel `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indeks untuk tabel `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indeks untuk tabel `stock_mutations`
--
ALTER TABLE `stock_mutations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeks untuk tabel `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `cashier_id` (`cashier_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `transaction_date` (`transaction_date`),
  ADD KEY `idx_transactions_cashier` (`cashier_id`),
  ADD KEY `idx_transactions_date` (`transaction_date`);

--
-- Indeks untuk tabel `transaction_items`
--
ALTER TABLE `transaction_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_transaction_items_product` (`product_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `loyalty_point_history`
--
ALTER TABLE `loyalty_point_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT untuk tabel `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT untuk tabel `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `stock_mutations`
--
ALTER TABLE `stock_mutations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT untuk tabel `transaction_items`
--
ALTER TABLE `transaction_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=143;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
