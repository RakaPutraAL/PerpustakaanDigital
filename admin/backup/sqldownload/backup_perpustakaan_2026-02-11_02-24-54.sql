DROP TABLE IF EXISTS `buku`;
CREATE TABLE `buku` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judul` varchar(150) NOT NULL,
  `penulis` varchar(200) DEFAULT NULL,
  `penerbit` varchar(150) DEFAULT NULL,
  `tahun` int(4) NOT NULL,
  `stok` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `buku` VALUES('1', 'malin kundang', 'Mailinda Safitri', 'PT Balai Pustaka (Persero)', '2011', '7', '2026-02-10 16:52:47');
INSERT INTO `buku` VALUES('2', 'Madilog', 'Tan Malaka', 'IRCISOD', '2022', '8', '2026-02-10 16:52:47');
INSERT INTO `buku` VALUES('3', 'harry poter', 'J. K. Rowling', 'Gramedia Pustaka Utama', '2008', '8', '2026-02-10 16:52:47');
INSERT INTO `buku` VALUES('4', 'Si Kancil', 'M.B. Rahimsyah A.R.', 'Lingkar Media', '1990', '15', '2026-02-10 16:52:47');
INSERT INTO `buku` VALUES('5', 'Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', '2005', '8', '2026-02-10 16:52:47');
INSERT INTO `buku` VALUES('6', 'Kimi No Nawa', 'Makoto Sinkai', 'Gramedia', '2016', '6', '2026-02-10 16:52:47');
INSERT INTO `buku` VALUES('7', 'Mie Ayam', 'Bryan Krisna', 'Gramedia', '2022', '7', '2026-02-10 16:52:47');
INSERT INTO `buku` VALUES('8', 'Bumi Manusia', 'Pramoedya Ananta Toer', 'Hasta Mitra', '1980', '5', '2026-02-10 16:52:47');
INSERT INTO `buku` VALUES('9', 'Dunia Sophie', 'Jostein Gaarder', 'Mizan', '1995', '10', '2026-02-10 16:52:47');
INSERT INTO `buku` VALUES('10', 'Bumi', 'Tere Liye', 'Gramedia', '2014', '11', '2026-02-10 16:52:47');
INSERT INTO `buku` VALUES('11', 'Negeri 5 Menara', 'Ahmad Fuadi', 'Gramedia', '2009', '6', '2026-02-10 16:52:47');
INSERT INTO `buku` VALUES('12', 'Sejarah Dunia Lengkap', 'J.M. Roberts', 'Erlangga', '2010', '2', '2026-02-10 16:52:47');
INSERT INTO `buku` VALUES('13', 'Belajar Python Dasar', 'Andi Setiawan', 'Informatika', '2020', '9', '2026-02-10 16:52:47');
INSERT INTO `buku` VALUES('14', 'The Hobbit', 'J.R.R Tolkien', 'Gramedia', '1937', '15', '2026-02-10 16:52:47');
INSERT INTO `buku` VALUES('15', 'transformasi digital', 'Erwin Erwin, Afdhal Chatra P, Asmara Wildani Pasaribu, Nurillah Jamil Achmawati Novel, Sepriano, Abd', 'PTT. Sonpedia Publishing Indonesia', '2023', '4', '2026-02-11 07:50:54');


DROP TABLE IF EXISTS `detail_buku`;
CREATE TABLE `detail_buku` (
  `id_buku` int(11) NOT NULL,
  `kode_buku` varchar(50) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_buku`),
  CONSTRAINT `fk_detail_buku` FOREIGN KEY (`id_buku`) REFERENCES `buku` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `detail_buku` VALUES('1', 'MK-', 'Cerita Malin Kundang berasal dari Sumatra Barat. Mengisahkan tentang seorang anak yang durhaka terhadap ibunya. Cerita ini mengandung nilai-nilai luhur berupa budi pekerti. (Balai Pustaka)', 'Fiksi', 'malin.jpg', '2026-02-10 16:52:47');
INSERT INTO `detail_buku` VALUES('2', 'MG-', 'Pada perang Jepang-Tiongkok, tepatnya di Shanghai penghabisan tahun 1931, tiga hari lamanya saya terkepung di belakang jalan bernama North Sichuan Road, tempat peperangan pertama kali meletus. Dari North Sichuan Road tadi, Jepang menembak ke arah Po Shan Road dan tentara Tiongkok dari arah sebaliknya. Di antaranya, persisnya di kampung Wang Pan Cho, saya dengan pustaka saya terpaku. Sesudah dua atau tiga hari berselang, tentara Jepang baru memberi izin kepada kampung tempat saya tinggal untuk be', 'Fiksi', 'madilog.jpg', '2026-02-10 16:52:47');
INSERT INTO `detail_buku` VALUES('3', 'HD-', 'Socioeconomic crisis in Indonesia.', 'Lainnya', 'harry.jpg', '2026-02-10 16:52:47');
INSERT INTO `detail_buku` VALUES('4', 'SK-', 'dari khasanah sastra lama, yang dahulu disebut pelanduk jenaka sekarang lebih dikenal di kancil, hewan kecil berasal dari hutan, perjuangan hidup di belantara yang kejam membuatnya berakal cerdik sehingga mampu bertahan berhadapan dengan anjing, harimau, buaya, ular, babi hutan dan satwa lain yang lebih besar, buas dan brutal!', 'Fiksi', 'kancil.jpg', '2026-02-10 16:52:47');
INSERT INTO `detail_buku` VALUES('5', 'LP-', 'Novel tentang perjuangan anak-anak Belitung', 'Fiksi', 'laskar.jpg', '2026-02-10 16:52:47');
INSERT INTO `detail_buku` VALUES('6', 'KNN-', 'Tentang Dua orang gender laki laki perempuan tertukar', 'Fiksi', 'kimi.jpg', '2026-02-10 16:52:47');
INSERT INTO `detail_buku` VALUES('7', 'MAA-', 'Tentang perjuangan makan mie ayam', 'Drama', 'mieayam.jpg', '2026-02-10 16:52:47');
INSERT INTO `detail_buku` VALUES('8', 'BM-', 'Tetralogi Buru Jilid 1', 'Sejarah', 'bumi.jpg', '2026-02-10 16:52:47');
INSERT INTO `detail_buku` VALUES('9', 'DS-', 'Novel pengantar filsafat yang ringan dipahami', 'Filsafat', 'sophie.jpg', '2026-02-10 16:52:47');
INSERT INTO `detail_buku` VALUES('10', 'BM-', 'Petualangan dunia paralel penuh misteri', 'Novel', 'bumii.jpg', '2026-02-10 16:52:47');
INSERT INTO `detail_buku` VALUES('11', 'N5M-', 'Kisah perjuangan santri meraih mimpi', 'Novel', 'negeri.jpg', '2026-02-10 16:52:47');
INSERT INTO `detail_buku` VALUES('12', 'SDL-', 'Ringkasan sejarah dunia dari masa ke masa', 'Sejarah', 'sejarah.jpg', '2026-02-10 16:52:47');
INSERT INTO `detail_buku` VALUES('13', 'PY-', 'Panduan dasar pemrograman Python', 'Teknologi', 'python.png', '2026-02-10 16:52:47');
INSERT INTO `detail_buku` VALUES('14', 'TH-', 'The Hobbit, karya legendaris dari penulis ternama J.R.R. Tolkien, telah menjadi salah satu buku fantasi paling ikonik sepanjang masa. Diterbitkan pertama kali pada tahun 1937, novel ini mengisahkan petualangan epik Bilbo Baggins, seorang hobbit yang terjebak dalam misi berbahaya untuk merebut kembali harta karun dari naga yang menakutkan. Dalam review ini, kita akan menyelami kedalaman cerita, eksplorasi dunia fantasi yang kaya, pengembangan karakter yang memikat, tema-tema universal yang diangkat, serta pengaruh abadi dari The Hobbit terhadap genre fantasi.', 'Fiksi', 'hobbit.jpeg', '2026-02-10 16:52:47');
INSERT INTO `detail_buku` VALUES('15', 'TD-', 'Buku \"Transformasi Digital\" adalah sebuah buku yang memberikan wawasan mendalam tentang perubahan dan adaptasi bisnis dalam era digital. Buku ini ditulis untuk membantu pelaku bisnis, pengusaha, akademisi, dan profesional lainnya memahami pentingnya transformasi digital dan bagaimana menerapkannya dengan sukses. Dalam era yang didominasi oleh perkembangan teknologi yang pesat, transformasi digital menjadi sebuah kebutuhan yang mendesak bagi perusahaan agar tetap relevan dan kompetitif. Buku ini', 'Bisniss', 'buku-1770771125-2991.png', '2026-02-11 07:50:54');


DROP TABLE IF EXISTS `transaksi`;
CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_buku` int(11) DEFAULT NULL,
  `kode_spesifik` varchar(50) DEFAULT NULL,
  `nama_peminjam` varchar(100) DEFAULT NULL,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_kembali` date DEFAULT NULL,
  `status` enum('dipinjam','terlambat','kembali','pending_payment') DEFAULT 'dipinjam',
  `denda` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `transaksi` VALUES('1', '11', 'N5M-003', 'leta', '2026-02-09', '2026-02-16', 'dipinjam', '0');
INSERT INTO `transaksi` VALUES('2', '12', 'SDL-001', 'leta', '2026-02-09', '2026-02-16', 'dipinjam', '0');
INSERT INTO `transaksi` VALUES('3', '12', 'SDL-004', 'arya', '2026-02-09', '2026-02-16', 'dipinjam', '0');
INSERT INTO `transaksi` VALUES('4', '13', 'PY-006', 'arya', '2026-02-09', '2026-02-16', 'dipinjam', '0');
INSERT INTO `transaksi` VALUES('5', '16', 'AD-001', 'arya', '2026-02-04', '2026-02-06', 'dipinjam', '0');
INSERT INTO `transaksi` VALUES('6', '10', 'BM-002', 'leta', '2026-02-05', '2026-02-09', 'kembali', '3000');
INSERT INTO `transaksi` VALUES('7', '1', 'MK-001', 'viktor', '2026-02-01', '2026-02-09', 'dipinjam', '2500');
INSERT INTO `transaksi` VALUES('8', '15', 'TD-001', 'putra', '2026-02-10', '2026-02-17', 'dipinjam', '0');
INSERT INTO `transaksi` VALUES('9', '1', 'MK-002', 'putra', '2026-02-06', '2026-02-10', 'kembali', '1500');
INSERT INTO `transaksi` VALUES('10', '10', 'BM-004', 'aan', '2026-02-11', '2026-02-18', 'dipinjam', '0');
INSERT INTO `transaksi` VALUES('11', '12', 'SDL-003', 'aan', '2026-02-11', '2026-02-18', 'dipinjam', '0');
INSERT INTO `transaksi` VALUES('12', '15', 'TD-005', 'aan', '2026-02-05', '2026-02-08', 'pending_payment', '0');


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `level` enum('admin','user') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `kelas` varchar(50) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `status` enum('pending','aktif') DEFAULT 'pending',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` VALUES('1', 'raka', '$2y$10$DhRpYaHb.uCa6Du8m56r3ubTFkk7f8VnwGgmVl6LNqbouUmD6BsdG', 'admin', '2026-01-10 21:20:29', 'GURU', 'kaliwuluh 1', '2008-01-19', 'aktif');
INSERT INTO `users` VALUES('2', 'budi', '$2y$10$E17Y2zgpt0N5WAhYS/q27.8ufm5ESsULZKV6ZK3dC/lG/JQ5B18Pq', 'user', '2026-02-08 14:22:16', 'XII RPL 1', 'Jl. Merdeka No. 10, Jakarta', '2006-05-20', 'aktif');
INSERT INTO `users` VALUES('3', 'siti', '$2y$10$djB78K3aHfZXjbHVRmO3Y.2B4I.t.GzjJT5qd7n4kBDzvCltmjQPG', 'user', '2026-02-08 14:22:16', 'XII TKJ 2', 'Jl. Mawar No. 5, Bandung', '2007-08-15', 'pending');
INSERT INTO `users` VALUES('4', 'pian', '$2y$10$qku6jsiERkI1b0axou1G5e521SwDDPo/UziOIidrypd3PQKE/mRIW', 'user', '2026-02-08 14:22:16', 'XII RPL 1', 'Jl. Plupuh No. 5, Masaran', '2007-08-16', 'aktif');
INSERT INTO `users` VALUES('5', 'leta', '$2y$10$IcfaRQrbB7ulbyffD3RM/.AvX0TalkGL4/65qlTMW8qKSTJi3kDcG', 'user', '2026-02-08 14:22:17', 'XII RPL 2', 'Jl. Magetan No. 1, Laron', '2007-08-17', 'aktif');
INSERT INTO `users` VALUES('6', 'putra', '$2y$10$KdEm0mNhkK7/JOblmQgD2Ory9m6/XJCeKo8Ex9FQM7kRW9kXG0wpu', 'user', '2026-02-08 14:22:17', 'XII RPL 3', 'Jl. Lawu No. 5, Cangakan', '2007-08-18', 'aktif');
INSERT INTO `users` VALUES('7', 'arya', '$2y$10$Cu6zzWDGhgr/8fOq6W6v7e2fkcEmRyHly2W5A5OzajBv2QFmXLWcO', 'user', '2026-02-08 14:22:17', 'XII RPL 4', 'Jl. Depok No. 7, Puri', '2007-08-20', 'aktif');
INSERT INTO `users` VALUES('8', 'malik', '$2y$10$8aNY0yIYbKCd9Ciu.F2REO0UTNIOtMDUMP4bcts31dLdQEI5jEp2S', 'user', '2026-02-08 14:22:17', 'XII RPL 2', 'Jl. Papua No. 5, Suri', '2007-08-20', 'aktif');
INSERT INTO `users` VALUES('9', 'aan', '$2y$10$gfb4y6li157jV8bnlpXJtOqln16W1m50k6aGqIbGmZTt6xYyrvKOa', 'user', '2026-02-08 14:22:17', 'XII RPL 1', 'Jl. Sirupo No. 5, Lihu', '2007-08-21', 'aktif');
INSERT INTO `users` VALUES('10', 'john_doe', '$2y$10$ybLcPWC0t3Gp4PIzCLdiNOOEZ9JXZP5IhPcYAH0DeQs24uyRaESAe', 'user', '2026-02-09 07:43:20', 'XII RPL 1', 'Jl. Merdeka No. 10', '2005-05-15', 'aktif');
INSERT INTO `users` VALUES('11', 'rangga', '$2y$10$AhmYAwRQ34XeQovLUml8Cenq/fDkVcr2CVYx5QfkpKLNBEq8BOOB6', 'user', '2026-02-09 07:43:20', 'XII RPL 1', 'JLnsnscsncj', '2004-02-12', 'pending');
INSERT INTO `users` VALUES('12', 'budi_santoso', '$2y$10$ZwuKIQMtS5FAx03FSpngc.GHiyGEdaWbjejtCjNyIg6EDoSRzRODi', 'user', '2026-02-09 07:46:24', 'XII RPL 1', 'Jl. Merdeka No. 10, Jakarta', '2006-05-20', 'aktif');
INSERT INTO `users` VALUES('13', 'viktor', '$2y$10$x8qKfnPQIm9YZIfrkcX13.4qopqVUFLz2Tufh.NEZTb1e3K6ffXY6', 'user', '2026-02-09 14:04:02', 'XII RPL 1', 'Solo,manahann', '2007-01-12', 'aktif');
INSERT INTO `users` VALUES('14', 'admin', '$2y$10$ytLf.1ARYGF9aEF/nP3UmOS2NyDV1JLea7gWBoCeiLmP6i4FylpPi', 'admin', '2026-02-09 17:15:45', 'guru', 'kali', '2001-08-19', 'pending');
INSERT INTO `users` VALUES('15', 'rapel', '$2y$10$.doDhtn4gljor7UBMZPgf.PGN3ClRRC0O92DI6VFJcW8cO7Dt0WAO', 'user', '2026-02-10 08:27:53', 'XII RPL 1', 'Jl. Merdeka No. 10, Jakarta', '2006-05-20', 'aktif');
INSERT INTO `users` VALUES('16', 'john', '$2y$10$1IoatslQq.VOyxEN8QSW1uGhlygfSCLGAhO68l2UaTsJcArcgjqka', 'user', '2026-02-10 08:44:52', 'XII RPL 1', 'Jl. Merdeka No. 10', '2005-05-15', 'aktif');
INSERT INTO `users` VALUES('17', 'jane', '$2y$10$ZP4fAlwbI19rCM422QOiK.vKjzrVUn3hQcaK3xlncvzcsbXPGQ8oS', 'user', '2026-02-10 08:44:52', 'XII TKJ 2', 'Jl. Sudirman No. 20', '2005-08-20', 'aktif');
INSERT INTO `users` VALUES('18', 'dafa', '$2y$10$9W0kR5FO6h9op4SVQta0gOKy4kS1DK59Ne/KCAEPGpGibTKB1Ovz.', 'user', '2026-02-10 10:09:16', 'XII RPL', 'sidodadii', '2026-02-19', 'aktif');
INSERT INTO `users` VALUES('19', 'eka', '$2y$10$BjHNlBDiX7EYR61.8IF4wOXlXmWDNQGVdzb748yojdCpnd0XNu8h2', 'user', '2026-02-10 19:17:50', 'XII RPL 3', 'Cangakan', '2006-01-20', 'aktif');
INSERT INTO `users` VALUES('20', 'rey', '$2y$10$wJChOyl5Jep8d/0M.SB1ruvXGbYLNZv44V0secE0B8EFOD0sVpWfm', 'user', '2026-02-11 07:59:06', 'XII RPL 1', 'Jl. Merdeka No. 19, Surabaya', '2008-02-14', 'aktif');
INSERT INTO `users` VALUES('21', 'siti_aminah', '$2y$10$yX49oq2ZfQuB2O6xiK4HHODEM5R5udm8/tx488jeUbox3cMsWV9ui', 'user', '2026-02-11 07:59:06', 'XI TKJ 2', 'Jl. Mawar No. 25, Aceh', '2008-01-02', 'aktif');
INSERT INTO `users` VALUES('22', 'irfan', '$2y$10$dGUozlkZPwOP0pG8OVHjVeiR27d8584eN3TDR91SRFN2ojc1/.1de', 'user', '2026-02-11 08:15:30', 'XII RPL 1', 'Jl. Merdeka No. 10, Papua', '2006-05-20', 'aktif');


