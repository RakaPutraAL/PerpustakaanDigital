-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 11, 2026 at 10:30 PM
-- Server version: 8.0.30
-- PHP Version: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `perpustakaan`
--

-- --------------------------------------------------------

--
-- Table structure for table `buku`
--

CREATE TABLE `buku` (
  `id` int NOT NULL,
  `judul` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `penulis` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `penerbit` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tahun` int NOT NULL,
  `stok` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buku`
--

INSERT INTO `buku` (`id`, `judul`, `penulis`, `penerbit`, `tahun`, `stok`, `created_at`) VALUES
(1, 'malin kundang', 'Mailinda Safitri', 'PT Balai Pustaka (Persero)', 2011, 6, '2026-02-11 13:04:38'),
(2, 'Madilog', 'Tan Malaka', 'IRCISOD', 2022, 7, '2026-02-11 13:04:38'),
(3, 'harry poter', 'J. K. Rowling', 'Gramedia Pustaka Utama', 2008, 7, '2026-02-11 13:04:38'),
(4, 'Si Kancil', 'M.B. Rahimsyah A.R.', 'Lingkar Media', 1990, 14, '2026-02-11 13:04:38'),
(5, 'Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', 2005, 8, '2026-02-11 13:04:38'),
(6, 'Kimi No Nawa', 'Makoto Sinkai', 'Gramedia', 2016, 5, '2026-02-11 13:04:38'),
(7, 'Mie Ayam', 'Bryan Krisna', 'Gramedia', 2022, 7, '2026-02-11 13:04:38'),
(8, 'Bumi Manusia', 'Pramoedya Ananta Toer', 'Hasta Mitra', 1980, 5, '2026-02-11 13:04:38'),
(9, 'Dunia Sophie', 'Jostein Gaarder', 'Mizan', 1995, 10, '2026-02-11 13:04:38'),
(10, 'Bumi', 'Tere Liye', 'Gramedia', 2014, 11, '2026-02-11 13:04:38'),
(11, 'Negeri 5 Menara', 'Ahmad Fuadi', 'Gramedia', 2009, 6, '2026-02-11 13:04:38'),
(12, 'Sejarah Dunia Lengkap', 'J.M. Roberts', 'Erlangga', 2010, 1, '2026-02-11 13:04:38'),
(13, 'Belajar Python Dasar', 'Andi Setiawan', 'Informatika', 2020, 7, '2026-02-11 13:04:38'),
(14, 'The Hobbit', 'J.R.R Tolkien', 'Gramedia', 1937, 13, '2026-02-11 13:04:38'),
(15, 'transformasi digital', 'Erwin Erwin, Afdhal Chatra P, Asmara Wildani Pasaribu, Nurillah Jamil Achmawati Novel, Sepriano, Abd', 'PTT. Sonpedia Publishing Indonesia', 2023, 2, '2026-02-11 13:04:38');

-- --------------------------------------------------------

--
-- Table structure for table `detail_buku`
--

CREATE TABLE `detail_buku` (
  `id_buku` int NOT NULL,
  `kode_buku` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deskripsi` text COLLATE utf8mb4_general_ci,
  `kategori` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gambar` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_buku`
--

INSERT INTO `detail_buku` (`id_buku`, `kode_buku`, `deskripsi`, `kategori`, `gambar`, `created_at`) VALUES
(1, 'MK-', 'Cerita Malin Kundang berasal dari Sumatra Barat. Mengisahkan tentang seorang anak yang durhaka terhadap ibunya. Cerita ini mengandung nilai-nilai luhur berupa budi pekerti. (Balai Pustaka)', 'Fiksi', 'malin.jpg', '2026-02-11 13:04:38'),
(2, 'MG-', 'Pada perang Jepang-Tiongkok, tepatnya di Shanghai penghabisan tahun 1931, tiga hari lamanya saya terkepung di belakang jalan bernama North Sichuan Road, tempat peperangan pertama kali meletus. Dari North Sichuan Road tadi, Jepang menembak ke arah Po Shan Road dan tentara Tiongkok dari arah sebaliknya. Di antaranya, persisnya di kampung Wang Pan Cho, saya dengan pustaka saya terpaku. Sesudah dua atau tiga hari berselang, tentara Jepang baru memberi izin kepada kampung tempat saya tinggal untuk be', 'Fiksi', 'madilog.jpg', '2026-02-11 13:04:38'),
(3, 'HD-', 'Socioeconomic crisis in Indonesia.', 'Lainnya', 'harry.jpg', '2026-02-11 13:04:38'),
(4, 'SK-', 'dari khasanah sastra lama, yang dahulu disebut pelanduk jenaka sekarang lebih dikenal di kancil, hewan kecil berasal dari hutan, perjuangan hidup di belantara yang kejam membuatnya berakal cerdik sehingga mampu bertahan berhadapan dengan anjing, harimau, buaya, ular, babi hutan dan satwa lain yang lebih besar, buas dan brutal!', 'Fiksi', 'kancil.jpg', '2026-02-11 13:04:38'),
(5, 'LP-', 'Novel tentang perjuangan anak-anak Belitung', 'Fiksi', 'laskar.jpg', '2026-02-11 13:04:38'),
(6, 'KNN-', 'Tentang Dua orang gender laki laki perempuan tertukar', 'Fiksi', 'kimi.jpg', '2026-02-11 13:04:38'),
(7, 'MAA-', 'Tentang perjuangan makan mie ayam', 'Drama', 'mieayam.jpg', '2026-02-11 13:04:38'),
(8, 'BM-', 'Tetralogi Buru Jilid 1', 'Sejarah', 'bumi.jpg', '2026-02-11 13:04:38'),
(9, 'DS-', 'Novel pengantar filsafat yang ringan dipahami', 'Filsafat', 'sophie.jpg', '2026-02-11 13:04:38'),
(10, 'BM-', 'Petualangan dunia paralel penuh misteri', 'Novel', 'bumii.jpg', '2026-02-11 13:04:38'),
(11, 'N5M-', 'Kisah perjuangan santri meraih mimpi', 'Novel', 'negeri.jpg', '2026-02-11 13:04:38'),
(12, 'SDL-', 'Ringkasan sejarah dunia dari masa ke masa', 'Sejarah', 'sejarah.jpg', '2026-02-11 13:04:38'),
(13, 'PY-', 'Panduan dasar pemrograman Python', 'Teknologi', 'python.png', '2026-02-11 13:04:38'),
(14, 'TH-', 'The Hobbit, karya legendaris dari penulis ternama J.R.R. Tolkien, telah menjadi salah satu buku fantasi paling ikonik sepanjang masa. Diterbitkan pertama kali pada tahun 1937, novel ini mengisahkan petualangan epik Bilbo Baggins, seorang hobbit yang terjebak dalam misi berbahaya untuk merebut kembali harta karun dari naga yang menakutkan. Dalam review ini, kita akan menyelami kedalaman cerita, eksplorasi dunia fantasi yang kaya, pengembangan karakter yang memikat, tema-tema universal yang diangkat, serta pengaruh abadi dari The Hobbit terhadap genre fantasi.', 'Fiksi', 'hobbit.jpeg', '2026-02-11 13:04:38'),
(15, 'TD-', 'Buku \"Transformasi Digital\" adalah sebuah buku yang memberikan wawasan mendalam tentang perubahan dan adaptasi bisnis dalam era digital. Buku ini ditulis untuk membantu pelaku bisnis, pengusaha, akademisi, dan profesional lainnya memahami pentingnya transformasi digital dan bagaimana menerapkannya dengan sukses. Dalam era yang didominasi oleh perkembangan teknologi yang pesat, transformasi digital menjadi sebuah kebutuhan yang mendesak bagi perusahaan agar tetap relevan dan kompetitif. Buku ini', 'Bisniss', 'transformasi.png', '2026-02-11 13:04:38');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int NOT NULL,
  `id_buku` int DEFAULT NULL,
  `kode_spesifik` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_peminjam` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_kembali` date DEFAULT NULL,
  `status` enum('dipinjam','terlambat','kembali','pending_payment','pending_pinjam','pending_kembali') COLLATE utf8mb4_general_ci DEFAULT 'pending_pinjam',
  `denda` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id`, `id_buku`, `kode_spesifik`, `nama_peminjam`, `tanggal_pinjam`, `tanggal_kembali`, `status`, `denda`) VALUES
(1, 1, 'MK-001', 'leta', '2026-02-01', '2026-02-09', 'dipinjam', 0),
(2, 15, 'TD-003', 'ekaa', '2026-02-11', '2026-02-18', 'dipinjam', 0),
(3, 13, 'PY-002', 'ekaa', '2026-02-01', '2026-02-08', 'pending_payment', 0),
(4, 11, 'N5M-003', 'ekaa', '2026-02-11', '2026-02-18', 'pending_pinjam', 0),
(5, 3, 'HD-006', 'Rosid', '2026-02-11', '2026-02-18', 'dipinjam', 0),
(6, 6, 'KNN-003', 'Rosid', '2026-02-01', '2026-02-03', 'pending_payment', 0),
(7, 11, 'N5M-001', 'Rosid', '2026-02-11', '2026-02-18', 'pending_pinjam', 0),
(8, 4, 'SK-001', 'rendra', '2026-02-11', '2026-02-18', 'pending_kembali', 0),
(9, 5, 'LP-001', 'rendra', '2026-02-11', '2026-02-18', 'pending_pinjam', 0),
(10, 1, 'MK-003', 'rendra', '2026-02-11', '2026-02-11', 'kembali', 0),
(11, 9, 'DS-005', 'fano', '2026-02-01', '2026-02-11', 'kembali', 3000),
(12, 2, 'MG-002', 'fano', '2026-02-11', '2026-02-18', 'pending_kembali', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `level` enum('admin','user') COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `kelas` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_general_ci,
  `tanggal_lahir` date DEFAULT NULL,
  `status` enum('pending','aktif') COLLATE utf8mb4_general_ci DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `level`, `created_at`, `kelas`, `alamat`, `tanggal_lahir`, `status`) VALUES
(1, 'raka', '$2y$10$DhRpYaHb.uCa6Du8m56r3ubTFkk7f8VnwGgmVl6LNqbouUmD6BsdG', 'admin', '2026-01-10 14:20:29', 'GURU', 'kaliwuluh 1', '2008-01-19', 'aktif'),
(2, 'leta', '$2y$10$B8UjoUHXjrdM0FlRbznVduSki82UqO8OJGAzhes3VKx4AHI6HizBi', 'user', '2026-02-11 13:28:49', 'XII RPL 2', 'Kaliwuluh Lor', '2005-01-12', 'aktif'),
(3, 'arya', '$2y$10$SClLoJrdG4Ee35A59S7o.eiYjPnTRKnRnc0Fs4kv.GmbaEQzPErBy', 'user', '2026-02-11 13:29:26', 'XII TKR 1', 'Masaran', '2007-01-12', 'pending'),
(4, 'putra', '$2y$10$L/h9SBxdq6QCyF7JoMQwDuuo.oAlLXJX1ddXNims.kvk15Us7AHhm', 'user', '2026-02-11 13:31:47', 'XII TKJ 2', 'Plupuh', '2008-04-12', 'aktif'),
(5, 'malik', '$2y$10$MOiVFpGXxtgXkn8D/G.AgOdDadFCt.RNI9DrGd6Xo6vRRtSrkcXn6', 'user', '2026-02-11 13:38:44', 'XI TKR 3', 'Jambangan', '2006-09-12', 'aktif'),
(6, 'ekaa', '$2y$10$y7addlMtfWRH9zumkiJhiefVtZs9H6GhrBfiGWxo5p8EUr8Ox3kYe', 'user', '2026-02-11 13:39:13', 'XII TKR 2', 'Sumberejo', '2006-06-12', 'aktif'),
(7, 'rendi', '$2y$10$XqLjRSh.5/4IhOUvly6ggOZH3smoGBXtFQXjNJtjLWNrMpeFmcuTy', 'user', '2026-02-11 13:39:50', 'X RPL 1', 'Depok', '2006-01-01', 'aktif'),
(8, 'rendra', '$2y$10$5MHmiM8XJ2RO5OqqDAE3VO2tH6p0Mt8ufo4qjQ5AZAjok7n2JAacC', 'user', '2026-02-11 13:40:17', 'XI RPL3', 'Jombang', '2005-01-09', 'aktif'),
(9, 'Rania', '$2y$10$38sPBJCh9EoYsx7JhaA4xO8sqV3oq9sKBHauBwTMdnmpxJOmXwaAy', 'user', '2026-02-11 13:40:58', 'XI RPL 2', 'Magetan', '2007-04-30', 'aktif'),
(10, 'Rosid', '$2y$10$zeCg9auWWUXjmRhT0M2N3eSoIPCAptMfuIySM0tGAvcktd99.a4Hq', 'user', '2026-02-11 13:41:30', 'X TKR 1', 'Krebet', '2007-01-09', 'aktif'),
(11, 'fano', '$2y$10$FzMnURKTgeKGFHraygCgseugax.5FBirYM8tE16dTb/64qV2ou8Yy', 'user', '2026-02-11 22:14:17', 'XII TKR 2', 'Masaran', '2007-03-12', 'aktif');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buku`
--
ALTER TABLE `buku`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `detail_buku`
--
ALTER TABLE `detail_buku`
  ADD PRIMARY KEY (`id_buku`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buku`
--
ALTER TABLE `buku`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_buku`
--
ALTER TABLE `detail_buku`
  ADD CONSTRAINT `fk_detail_buku` FOREIGN KEY (`id_buku`) REFERENCES `buku` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
