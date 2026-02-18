<?php
session_start();
include "../config/database.php";

// 1. Cek Login & Level (Hanya Siswa)
if (!isset($_SESSION['login'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_SESSION['level']) && $_SESSION['level'] === 'admin') {
    header("Location: ../admin/kelola-buku/dashboard.php");
    exit;
}

// 2. Ambil Data Buku Berdasarkan ID
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

 $id_buku = (int)$_GET['id'];

// --- CEK KUOTA PEMINJAMAN (MAX 3 BUKU) ---
 $nama_user = $_SESSION['username'] ?? $_SESSION['nama'] ?? 'Siswa';
 $max_borrow_limit = 3;
 $current_borrows = 0;

// Hitung transaksi yang BENAR-BENAR masih memegang buku fisik
// Status yang DIHITUNG: pending, dipinjam, pending_payment
// Status yang TIDAK DIHITUNG: kembali, selesai
 $check_quota = mysqli_query($conn, "SELECT COUNT(*) as total 
    FROM transaksi 
    WHERE nama_peminjam = '$nama_user' 
    AND LOWER(TRIM(status)) NOT IN ('selesai', 'kembali')");

if ($check_quota) {
    $quota_data = mysqli_fetch_assoc($check_quota);
    $current_borrows = (int)$quota_data['total'];
}

// Flag untuk tracking berbagai kondisi
 $quota_exceeded = false;
 $already_borrowed = false;
 $active_borrow_data = null;

// Jika sudah pinjam 3 atau lebih
if ($current_borrows >= $max_borrow_limit) {
    $quota_exceeded = true;
}

// ===== CEK APAKAH SISWA SEDANG MEMINJAM BUKU INI =====
// Kita tetap mengecek status pending_pinjam agar user tidak request buku yang sama 2x
 $check_active_borrow = mysqli_query($conn, "SELECT kode_spesifik, tanggal_pinjam, tanggal_kembali, status 
    FROM transaksi 
    WHERE nama_peminjam = '$nama_user' 
    AND id_buku = $id_buku 
    AND LOWER(TRIM(status)) NOT IN ('selesai', 'kembali')
    LIMIT 1");

if (mysqli_num_rows($check_active_borrow) > 0) {
    $already_borrowed = true;
    $active_borrow_data = mysqli_fetch_assoc($check_active_borrow);
}
// ===== AKHIR PENGECEKAN =====

// Query Utama
 $query_buku = mysqli_query($conn, "SELECT buku.*, detail_buku.gambar, detail_buku.kategori, detail_buku.deskripsi, detail_buku.kode_buku as prefix_kode
                                   FROM buku 
                                   LEFT JOIN detail_buku ON buku.id = detail_buku.id_buku 
                                   WHERE buku.id = $id_buku");
 $buku = mysqli_fetch_assoc($query_buku);

if (!$buku) {
    echo "<script>alert('Buku tidak ditemukan!'); window.location='dashboard.php';</script>";
    exit;
}

// 3. Ambil daftar kode buku yang SUDAH DIPINJAM (untuk di-disable)
// PERUBAHAN LOGIKA:
// Hanya status dimana buku FISIKNYA sudah keluar dari rak (dipinjam, terlambat, dll) yang dihitung.
// pending_pinjam TIDAK dihitung karena buku masih di rak.
 $query_kode_dipinjam = mysqli_query($conn, "SELECT kode_spesifik 
    FROM transaksi 
    WHERE id_buku = '$id_buku' 
    AND status IN ('dipinjam', 'terlambat', 'pending_kembali', 'pending_payment')");

 $kode_dipinjam = [];
while ($row = mysqli_fetch_assoc($query_kode_dipinjam)) {
    $kode_dipinjam[] = $row['kode_spesifik'];
}

 $prefix = $buku['prefix_kode'] ?? 'KODE-';

// --- OTOMATISASI TANGGAL ---
 $tgl_hari_ini = date('Y-m-d'); // Hari ini
 $tgl_kembali  = date('Y-m-d', strtotime('+7 days')); // Hari ini + 7 hari
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Detail Buku | Siswa</title>

    <!-- BOOTSTRAP 5 CSS (OFFLINE) -->
    <link href="../asset/css/bootstrap.min.css" rel="stylesheet">

    <!-- BOOTSTRAP ICONS (OFFLINE) -->
    <link rel="stylesheet" href="../asset/font/bootstrap-icons.css">

    <style>
        :root {
            --bs-primary: #0d6efd;
            --sidebar-bg: #1a1e21;
            --sidebar-text: #e9ecef;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f3f4f6;
            overflow: hidden;
        }

        .d-flex-wrapper {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            display: flex;
            flex-direction: column;
            border-right: 1px solid #343a40;
            flex-shrink: 0;
        }

        .sidebar .brand {
            padding: 1.5rem;
            border-bottom: 1px solid #343a40;
            background-color: #0f1113;
        }

        .sidebar .nav-link {
            color: #adb5bd;
            padding: 0.8rem 1.5rem;
            border-radius: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            text-decoration: none;
        }

        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(13, 110, 253, 0.15);
            border-left: 4px solid var(--bs-primary);
        }

        .sidebar .logout-link {
            color: #ff6b6b;
            margin-top: auto;
        }

        .sidebar .logout-link:hover {
            background-color: rgba(255, 107, 107, 0.1);
            border-left: 4px solid #ff6b6b;
            color: #ff6b6b;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .top-header {
            background-color: #ffffff;
            border-bottom: 1px solid #dee2e6;
            padding: 0.75rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content-scroll {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
        }

        /* Card Styling */
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            background: #fff;
        }

        /* Radio Button Styling for Codes */
        .code-radio-input {
            display: none;
        }

        .code-radio-box {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #fff;
        }

        .code-radio-box:hover {
            border-color: #86b7fe;
        }

        .code-radio-input:checked+.code-radio-box {
            border-color: #0d6efd;
            background-color: #e7f1ff;
            color: #0d6efd;
            font-weight: bold;
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25);
        }

        .code-radio-input:disabled+.code-radio-box {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: #e9ecef;
            border-color: #dee2e6;
        }
    </style>
</head>

<body>

    <div class="d-flex-wrapper">

        <!-- SIDEBAR -->
        <aside class="sidebar d-none d-md-flex">
            <div class="brand d-flex align-items-center gap-3">
                <div class="bg-white rounded p-2 shadow-sm d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <img src="../asset/img/logo.png" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div>
                    <h5 class="m-0 fw-bold">SIPERDI</h5>
                    <small class="text-secondary">Student Portal</small>
                </div>
            </div>

            <nav class="nav flex-column mt-3">
                <a class="nav-link" href="dashboard.php">
                    <i class="bi bi-grid"></i> Katalog Buku
                </a>
                <a class="nav-link" href="peminjaman.php">
                    <i class="bi bi-journal-bookmark-fill"></i> Peminjaman Saya
                </a>
                <a class="nav-link" href="profil.php">
                    <i class="bi bi-person-fill"></i> Profil
                </a>
            </nav>

            <div class="mt-auto p-3">
                <a href="konfirmasi_logout.php" class="nav-link logout-link rounded">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">

            <!-- Header -->
            <header class="top-header">
                <div class="d-flex align-items-center gap-2">
                    <a href="dashboard.php" class="text-decoration-none text-secondary">
                        <i class="bi bi-arrow-left fs-4"></i>
                    </a>
                    <h5 class="fw-bold mb-0 text-dark">Detail Buku</h5>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div class="text-end d-none d-sm-block">
                        <div class="fw-bold text-dark"><?= htmlspecialchars($nama_user) ?></div>
                        <small class="text-muted">Siswa</small>
                    </div>

                    <div class="rounded-circle border border-2 border-primary overflow-hidden bg-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-person-fill text-white fs-5"></i>
                    </div>
                </div>
            </header>

            <!-- Scrollable Content -->
            <div class="content-scroll">
                <div class="container p-0">

                    <!-- ===== NOTIFIKASI KUOTA TERCAPAI ===== -->
                    <?php if ($quota_exceeded): ?>
                        <div class="alert alert-danger border-0 shadow-sm mb-4 d-flex align-items-start" role="alert">
                            <i class="bi bi-exclamation-triangle-fill fs-4 me-3 flex-shrink-0"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Batas Maksimal Peminjaman Tercapai</h6>
                                <p class="mb-2">Anda sudah meminjam <strong><?= $current_borrows ?> dari <?= $max_borrow_limit ?> buku</strong> yang diizinkan.</p>
                                <p class="mb-0 small">Kembalikan buku terlebih dahulu dan tunggu konfirmasi admin untuk dapat meminjam buku lainnya.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- ===== NOTIFIKASI SUDAH MEMINJAM BUKU INI ===== -->
                    <?php if ($already_borrowed && $active_borrow_data): ?>
                        <div class="alert alert-warning border-0 shadow-sm mb-4 d-flex align-items-start" role="alert">
                            <i class="bi bi-info-circle-fill fs-4 me-3 flex-shrink-0"></i>
                            <div class="w-100">
                                <h6 class="fw-bold mb-2">Anda Sedang Meminjam Buku Ini</h6>
                                <div class="bg-white bg-opacity-50 p-3 rounded-3 mb-2">
                                    <div class="row g-2 small">
                                        <div class="col-12 col-md-3">
                                            <div class="text-muted">Kode Buku:</div>
                                            <div class="fw-bold font-monospace text-dark"><?= htmlspecialchars($active_borrow_data['kode_spesifik']) ?></div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="text-muted">Status:</div>
                                            <div class="fw-bold text-dark">
                                                <?php
                                                $status_display = [
                                                    'pending_pinjam' => '⏳ Menunggu Konfirmasi',
                                                    'pending' => '⏳ Menunggu',
                                                    'dipinjam' => '📚 Dipinjam',
                                                    'pending_payment' => '💰 Verif Bayar',
                                                    'dikembalikan' => '✅ Dikembalikan'
                                                ];
                                                echo $status_display[$active_borrow_data['status']] ?? htmlspecialchars($active_borrow_data['status']);
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="text-muted">Tanggal Pinjam:</div>
                                            <div class="fw-bold text-dark"><?= date('d M Y', strtotime($active_borrow_data['tanggal_pinjam'])) ?></div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="text-muted">Tanggal Kembali:</div>
                                            <div class="fw-bold text-dark"><?= date('d M Y', strtotime($active_borrow_data['tanggal_kembali'])) ?></div>
                                        </div>
                                    </div>
                                </div>
                                <p class="mb-0 small">
                                    <strong>Catatan:</strong> Buku ini masih dalam proses transaksi aktif. Anda harus mengembalikan buku ini dan menunggu konfirmasi admin (status "Selesai") sebelum dapat meminjam buku yang sama lagi.
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="row g-4">
                        <!-- Kolom Kiri: Gambar -->
                        <div class="col-lg-4 col-md-5">
                            <div class="card-custom p-4 h-100">
                                <div style="aspect-ratio: 3/4;" class="bg-light rounded-3 mb-3 overflow-hidden d-flex align-items-center justify-content-center shadow-sm border">
                                    <?php if (!empty($buku['gambar'])): ?>
                                        <img class="w-100 h-100 object-fit-cover" src="../uploads/<?= $buku['gambar'] ?>" alt="Cover Buku">
                                    <?php else: ?>
                                        <div class="text-center p-4">
                                            <i class="bi bi-book text-secondary" style="font-size: 5rem; opacity: 0.3;"></i>
                                            <div class="small text-muted fw-bold mt-2">No Cover</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($buku['judul']) ?></h4>
                                <p class="text-secondary mb-0"><?= htmlspecialchars($buku['penulis']) ?></p>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Info & Form -->
                        <div class="col-lg-8 col-md-7">

                            <!-- Info Buku -->
                            <div class="card-custom p-4 mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="badge bg-primary bg-opacity-10 text-dark border border-primary border-opacity-25 rounded-pill px-3 py-2">
                                        <?= htmlspecialchars($buku['kategori'] ?? 'Umum') ?>
                                    </span>
                                </div>
                                <p class="text-secondary mb-3" style="line-height: 1.6;">
                                    <?= !empty($buku['deskripsi']) ? nl2br(htmlspecialchars($buku['deskripsi'])) : 'Tidak ada deskripsi.' ?>
                                </p>
                                <div class="row g-3 text-secondary">
                                    <div class="col-6">
                                        <small class="text-uppercase fw-bold text-muted" style="font-size: 0.75rem;">Penerbit</small>
                                        <div class="text-dark"><?= $buku['penerbit'] ?></div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-uppercase fw-bold text-muted" style="font-size: 0.75rem;">Tahun</small>
                                        <div class="text-dark"><?= $buku['tahun'] ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Peminjaman -->
                            <div class="card-custom p-4 <?= ($quota_exceeded || $already_borrowed) ? 'opacity-75' : '' ?>">
                                <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                    <i class="bi bi-qr-code-scan text-primary"></i> Pilih Kode Buku
                                </h5>
                                <p class="text-muted small mb-3">Silakan pilih kode buku fisik yang akan Anda pinjam.</p>

                                <form method="POST" action="konfirmasi_buku.php">
                                    <input type="hidden" name="id_buku" value="<?= $id_buku ?>">
                                    
                                    <!-- INPUT TANGGAL OTOMATIS (HIDDEN) -->
                                    <input type="hidden" name="tanggal_pinjam" value="<?= $tgl_hari_ini ?>">
                                    <input type="hidden" name="tanggal_kembali" value="<?= $tgl_kembali ?>">

                                    <!-- Grid Kode Buku -->
                                    <div class="row g-2 mb-4">
                                        <?php if ($buku['stok'] > 0): ?>
                                            <?php for ($i = 1; $i <= $buku['stok']; $i++):
                                                $num = str_pad($i, 3, '0', STR_PAD_LEFT);
                                                $code = $prefix . $num;
                                                // Cek apakah kode ini statusnya buku fisiknya keluar (dipinjam/terlambat/dll)
                                                // pending_pinjam TIDAK masuk sini, jadi dianggap TERSEDIA
                                                $is_dipinjam = in_array($code, $kode_dipinjam);
                                            ?>
                                                <div class="col-6 col-md-4 col-lg-3">
                                                    <label class="mb-0 d-block">
                                                        <input type="radio" 
                                                               name="kode_pilihan" 
                                                               value="<?= $code ?>" 
                                                               class="code-radio-input" 
                                                               required 
                                                               <?= ($is_dipinjam || $quota_exceeded || $already_borrowed) ? 'disabled' : '' ?>>
                                                        <div class="code-radio-box">
                                                            <div class="fw-bold font-monospace"><?= $code ?></div>
                                                            <div class="small" style="font-size: 0.75rem; margin-top: 2px;">
                                                                <?= $is_dipinjam ? '<span class="text-danger fw-bold">Dipinjam</span>' : '<span class="text-muted">Tersedia</span>' ?>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            <?php endfor; ?>
                                        <?php else: ?>
                                            <div class="col-12 text-center py-4 text-muted bg-light rounded">
                                                Stok buku habis.
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Tampilan Info Tanggal (Hanya Read Only untuk Info Saja) -->
                                    <div class="alert alert-light border d-flex align-items-center mb-4" role="alert">
                                        <i class="bi bi-info-circle-fill text-primary me-3 fs-4"></i>
                                        <div>
                                            <small class="text-muted d-block">Masa Peminjaman Otomatis (7 Hari)</small>
                                            <div class="fw-bold text-dark">
                                                Tgl Pinjam: <?= date('d M Y', strtotime($tgl_hari_ini)) ?> &rarr; 
                                                Tgl Kembali: <?= date('d M Y', strtotime($tgl_kembali)) ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tombol Aksi -->
                                    <?php if ($quota_exceeded): ?>
                                        <button disabled class="btn btn-danger w-100 py-3 fw-bold d-flex align-items-center justify-content-center gap-2">
                                            <i class="bi bi-x-circle-fill"></i> Kuota Peminjaman Penuh
                                        </button>
                                    <?php elseif ($already_borrowed): ?>
                                        <button disabled class="btn btn-warning w-100 py-3 fw-bold d-flex align-items-center justify-content-center gap-2">
                                            <i class="bi bi-bookmark-fill"></i> Anda Sedang Meminjam Buku Ini
                                        </button>
                                    <?php elseif ($buku['stok'] > 0): ?>
                                        <button type="submit" name="pinjam_buku" class="btn btn-primary w-100 py-3 fw-bold d-flex align-items-center justify-content-center gap-2">
                                            <i class="bi bi-bookmark-check-fill"></i> Lanjut ke Konfirmasi
                                        </button>
                                    <?php else: ?>
                                        <button disabled class="btn btn-secondary w-100 py-3 fw-bold d-flex align-items-center justify-content-center gap-2">
                                            <i class="bi bi-slash-circle"></i> Stok Habis
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>

                        </div>
                    </div> <!-- End Row -->

                </div>
            </div>
        </main>
    </div>

    <!-- BOOTSTRAP 5 JS (OFFLINE) -->
    <script src="../asset/js/bootstrap.bundle.min.js"></script>
</body>

</html>