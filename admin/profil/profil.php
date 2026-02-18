<?php
session_start();
// Sesuaikan path config database ini
include "../../config/database.php";

// 1. Cek Login & Level
if (!isset($_SESSION['login'])) {
    header("Location: ../login.php");
    exit;
}


// --- HITUNG BADGE NOTIFIKASI PENGAJUAN (TAMBAHKAN INI) ---
$count_pending_pinjam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status = 'pending_pinjam'"))['total'];
$count_pending_kembali = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status = 'pending_kembali'"))['total'];
$total_pending = $count_pending_pinjam + $count_pending_kembali;

// --- HITUNG BADGE NOTIFIKASI DENDA ---
 $count_denda_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status = 'pending_payment'"))['total'];

if ($_SESSION['level'] !== 'admin') {
    die("Akses ditolak. Halaman ini khusus Admin.");
}

// 2. Ambil Data Admin
$username = $_SESSION['username'];
$query_admin = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username' AND level = 'admin'");
$data_admin = mysqli_fetch_assoc($query_admin);

if (!$data_admin) {
    die("Data Admin tidak ditemukan.");
}

// 3. Logic Update Profil Admin
if (isset($_POST['update_profil'])) {
    // Mengambil data dari form
    $username_baru = mysqli_real_escape_string($conn, $_POST['username']);
    $jabatan_baru = mysqli_real_escape_string($conn, $_POST['jabatan']);
    $tgl_lahir_baru = mysqli_real_escape_string($conn, $_POST['tanggal_lahir']);
    $alamat_baru = mysqli_real_escape_string($conn, $_POST['alamat']);

    // Cek apakah username baru sudah dipakai orang lain
    $cek_username = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username_baru' AND id != " . $data_admin['id']);
    if (mysqli_num_rows($cek_username) > 0) {
        $error = "Username sudah digunakan oleh akun lain.";
    } else {
        // Query update
        $update = mysqli_query($conn, "UPDATE users SET username = '$username_baru', kelas = '$jabatan_baru', tanggal_lahir = '$tgl_lahir_baru', alamat = '$alamat_baru' WHERE username = '$username' AND level = 'admin'");

        if ($update) {
            // Update Session Username agar tidak logout
            $_SESSION['username'] = $username_baru;

            // Refresh data agar tampilan berubah
            $query_admin = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username_baru' AND level = 'admin'");
            $data_admin = mysqli_fetch_assoc($query_admin);

            $status_sukses = true;
        } else {
            $error = "Gagal mengupdate profil.";
        }
    }
}

// 4. Hitung Statistik Perpustakaan
$total_buku = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as jml FROM buku"))['jml'] ?? 0;
$total_anggota = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as jml FROM users WHERE level = 'user'"))['jml'] ?? 0;
$total_transaksi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as jml FROM transaksi"))['jml'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profil Admin | Perpustakaan</title>

    <!-- BOOTSTRAP 5 CSS (OFFLINE) -->
    <link href="../../asset/css/bootstrap.min.css" rel="stylesheet">

    <!-- BOOTSTRAP ICONS (OFFLINE) -->
    <link rel="stylesheet" href="../../asset/font/bootstrap-icons.css">

    <style>
        /* --- Kustomisasi Tema Biru --- */
        :root {
            --primary-blue: #0d6efd;
            --bg-soft: #f8f9f6;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-soft);
        }

        /* Navbar */
        .navbar-custom {
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            height: 64px;
            border-bottom: 1px solid #e5e7eb;
        }

        /* Sidebar */
        .sidebar {
            min-height: calc(100vh - 64px);
            background-color: #ffffff;
            border-right: 1px solid #e9ecef;
            padding-top: 20px;
        }

        .sidebar .nav-link {
            color: #495057;
            font-weight: 500;
            padding: 12px 20px;
            margin-bottom: 5px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }

        .sidebar .nav-link i {
            margin-right: 12px;
            font-size: 1.2rem;
        }

        .sidebar .nav-link:hover {
            background-color: #f1f3f5;
            color: var(--primary-blue);
        }

        /* Menu Aktif (Profil) */
        .sidebar .nav-link.active {
            background-color: rgba(13, 110, 253, 0.15);
            color: var(--primary-blue);
            font-weight: 600;
            box-shadow: none;
            border-left: 4px solid var(--primary-blue);
        }

        /* Logout Button Style */
        .sidebar .btn-logout {
            color: #dc3545;
        }

        .sidebar .btn-logout:hover {
            background-color: #fff5f5;
            color: #dc3545;
        }

        /* Card Style */
        .card-custom {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        /* Form Controls */
        .form-control,
        .form-select {
            border-color: #ced4da;
            border-radius: 8px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        /* Modal Animation */
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        /* Gradient Cover */
        .profile-cover {
            height: 140px;
            background: linear-gradient(45deg, #0d6efd, #0043a8);
        }
    </style>
</head>

<body>

    <!-- ================= NAVBAR ================= -->
    <nav class="navbar navbar-custom fixed-top">
        <div class="container-fluid">
            <!-- Kiri: Logo & Title -->
            <a class="navbar-brand d-flex align-items-center text-primary fw-bold" href="#" style="color: var(--primary-blue) !important; text-decoration: none;">
                <!-- Menggunakan Logo yang cocok (White bg + Shadow) -->
                <div class="bg-white rounded p-2 shadow-sm d-flex align-items-center justify-content-center me-2" style="width: 42px; height: 42px;">
                    <img src="../../asset/img/logo.png" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <span class="fs-5">SIPERDI</span>
            </a>

            <!-- Kanan: Info Admin & Tombol Edit -->
            <div class="d-flex align-items-center gap-3">
                <div class="d-none d-md-block text-end">
                    <div class="fw-bold text-dark">Halo,<?= htmlspecialchars($data_admin['username']) ?></div>
                    <small class="text-muted">Administrator</small>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalEdit">
                    <i class="bi bi-pencil-square me-1"></i> Edit Profil
                </button>
            </div>
        </div>
    </nav>

    <div class="container-fluid pt-5 mt-4">
        <div class="row">

            <!-- ================= SIDEBAR ================= -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse show">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../kelola-buku/dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../kelola-buku/kelola_buku.php">
                                <i class="bi bi-book"></i> Kelola Buku
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link " href="../pengajuan/pengajuan_peminjaman.php">
                                <i class="bi bi-hourglass-split"></i> Pengajuan Peminjaman & Pengembalian
                                <?php if (($count_pending_pinjam + $count_pending_kembali) > 0): ?>
                                    <span class="badge bg-danger rounded-pill ms-auto">
                                        <?= $count_pending_pinjam + $count_pending_kembali ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../transaksi/transaksi.php">
                                <i class="bi bi-arrow-left-right"></i> Transaksi
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../denda/verifikasi_denda.php">
                                <i class="bi bi-cash-coin"></i> Verifikasi Denda
                                <?php if ($count_denda_pending > 0): ?>
                                    <span class="badge bg-danger ms-auto"><?= $count_denda_pending ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../kelola-anggota/anggota.php">
                                <i class="bi bi-people"></i> Kelola Anggota
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../laporan/laporan.php">
                                <i class="bi bi-file-earmark-bar-graph"></i> Laporan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../backup/backup.php">
                                <!-- GANTI ICON DI SINI: Menggunakan bi-database-add (lebih umum ada) -->
                                <i class="bi bi-database-add"></i> Backup & Restore
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="profil.php">
                                <i class="bi bi-person"></i> Profil
                            </a>
                        </li>
                    </ul>

                    <hr> <!-- PEMISAH -->

                    <div class="nav-item">
                        <a class="nav-link btn-logout" href="../konfirmasi_logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </nav>

            <!-- ================= MAIN CONTENT ================= -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

                <!-- Alert Error -->
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger mb-4">
                        <i class="bi bi-exclamation-circle me-2"></i> <?= $error ?>
                    </div>
                <?php endif; ?>

                <!-- Cover Section (Hanya Cover Biru, Tanpa Avatar/Gambar) -->
                <div class="card card-custom overflow-hidden mb-4">
                    <div class="profile-cover position-relative">
                        <div class="position-absolute bottom-0 start-0 p-4 text-white">
                            <h3 class="fw-bold mb-0"><?= htmlspecialchars($data_admin['username']) ?></h3>
                            <div class="opacity-75 small">ID: #<?= $data_admin['id'] ?> &bull; Super Admin</div>
                        </div>
                        <div class="position-absolute top-0 end-0 m-4">
                            <span class="badge bg-white text-primary border border-primary fw-bold px-3 py-2 shadow-sm">
                                <i class="bi bi-shield-check me-1"></i> Akses Penuh
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Stats Section -->
                <div class="card card-custom p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold text-dark mb-0">Ringkasan Perpustakaan</h5>
                        <i class="bi bi-bar-chart-line text-primary fs-4"></i>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded text-center border border-primary border-opacity-10 h-100 d-flex flex-column justify-content-center">
                                <i class="bi bi-book fs-2 text-primary mb-2"></i>
                                <h3 class="fw-bold mb-0"><?= $total_buku ?></h3>
                                <small class="text-muted fw-bold text-uppercase">Total Koleksi Buku</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded text-center border border-info border-opacity-10 h-100 d-flex flex-column justify-content-center">
                                <i class="bi bi-people fs-2 text-info mb-2"></i>
                                <h3 class="fw-bold mb-0"><?= $total_anggota ?></h3>
                                <small class="text-muted fw-bold text-uppercase">Total Anggota</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded text-center border border-warning border-opacity-10 h-100 d-flex flex-column justify-content-center">
                                <i class="bi bi-arrow-left-right fs-2 text-warning mb-2"></i>
                                <h3 class="fw-bold mb-0"><?= $total_transaksi ?></h3>
                                <small class="text-muted fw-bold text-uppercase">Total Transaksi</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detail Info Section -->
                <div class="card card-custom p-4">
                    <h5 class="fw-bold text-dark mb-4 border-bottom pb-2">Informasi Detail</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="border rounded p-3 bg-white h-100">
                                <label class="small text-muted fw-bold text-uppercase mb-1 d-block"><i class="bi bi-person-badge me-1"></i> Username</label>
                                <div class="text-dark fw-medium"><?= htmlspecialchars($data_admin['username']) ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 bg-white h-100">
                                <label class="small text-muted fw-bold text-uppercase mb-1 d-block"><i class="bi bi-briefcase me-1"></i> Jabatan</label>
                                <div class="text-dark fw-medium"><?= $data_admin['kelas'] ?? '-' ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 bg-white h-100">
                                <label class="small text-muted fw-bold text-uppercase mb-1 d-block"><i class="bi bi-calendar-event me-1"></i> Tanggal Lahir</label>
                                <div class="text-dark fw-medium"><?= $data_admin['tanggal_lahir'] ? date('d F Y', strtotime($data_admin['tanggal_lahir'])) : '-' ?></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-3 bg-white h-100">
                                <label class="small text-muted fw-bold text-uppercase mb-1 d-block"><i class="bi bi-geo-alt me-1"></i> Alamat</label>
                                <div class="text-dark fw-medium"><?= $data_admin['alamat'] ?? '-' ?></div>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- ================= MODAL EDIT PROFIL ================= -->
    <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Edit Profil Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="profil.php" method="POST">

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" value="<?= htmlspecialchars($data_admin['username']) ?>" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jabatan</label>
                            <input type="text" name="jabatan" value="<?= htmlspecialchars($data_admin['kelas'] ?? '') ?>" class="form-control" placeholder="Contoh: Kepala Perpustakaan">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" value="<?= $data_admin['tanggal_lahir'] ?? '' ?>" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat Lengkap</label>
                            <textarea name="alamat" rows="3" class="form-control" placeholder="Masukkan alamat..."><?= htmlspecialchars($data_admin['alamat'] ?? '') ?></textarea>
                        </div>

                        <div class="text-end mt-4">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="update_profil" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifikasi Sukses -->
    <?php if (isset($status_sukses) && $status_sukses): ?>
        <script>
            window.location.href = 'profil.php';
        </script>
    <?php endif; ?>

    <!-- BOOTSTRAP 5 JS (OFFLINE) -->
    <script src="../../asset/js/bootstrap.bundle.min.js"></script>

</body>

</html>