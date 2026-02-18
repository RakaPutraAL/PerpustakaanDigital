<?php
session_start();
include "../config/database.php";

//1. Cek Login & Level
if (!isset($_SESSION['login'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_SESSION['level']) && $_SESSION['level'] === 'admin') {
    header("Location: ../admin/kelola-buku/dashboard.php");
    exit;
}

// 2. Ambil Data User dari Database
$username = $_SESSION['username'] ?? 'Siswa';
$query_user = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
$data_user = mysqli_fetch_assoc($query_user);

if (!$data_user) {
    die("User tidak ditemukan.");
}

// 3. Logic Update Profil
if (isset($_POST['update_profil'])) {
    $kelas_baru = mysqli_real_escape_string($conn, $_POST['kelas']);
    $alamat_baru = mysqli_real_escape_string($conn, $_POST['alamat']);
    $tgl_lahir_baru = mysqli_real_escape_string($conn, $_POST['tanggal_lahir']);

    $update = mysqli_query($conn, "UPDATE users SET kelas = '$kelas_baru', alamat = '$alamat_baru', tanggal_lahir = '$tgl_lahir_baru' WHERE username = '$username'");

    if ($update) {
        header("Location: profil.php?status=sukses");
        exit;
    } else {
        $error = "Gagal mengupdate profil.";
    }
}

// 4. Hitung Statistik Peminjaman (REAL-TIME LOGIC DENDA)
$query_stats = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_transaksi,
        SUM(CASE WHEN status = 'dipinjam' THEN 1 ELSE 0 END) as dipinjam,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as menunggu,
        SUM(CASE WHEN status = 'kembali' THEN 1 ELSE 0 END) as kembali
    FROM transaksi 
    WHERE nama_peminjam = '$username'
");
$stats = mysqli_fetch_assoc($query_stats);

$total_pinjam = $stats['dipinjam'] ?? 0;
$total_menunggu = $stats['menunggu'] ?? 0;
$total_kembali = $stats['kembali'] ?? 0;

// Hitung Terlambat (Real-time Logic)
$query_cek_terlambat = mysqli_query($conn, "
    SELECT id, tanggal_kembali 
    FROM transaksi 
    WHERE nama_peminjam = '$username' AND (status = 'dipinjam' OR status = 'pending_payment')
");
$terlambat_count = 0;

while ($row = mysqli_fetch_assoc($query_cek_terlambat)) {
    if (!empty($row['tanggal_kembali'])) {
        $today_timestamp = time();
        $due_timestamp = strtotime($row['tanggal_kembali'] . ' 23:59:59');

        if ($today_timestamp > $due_timestamp) {
            $terlambat_count++;
        }
    }
}

$query_status_terlambat = mysqli_query($conn, "SELECT COUNT(*) as jml FROM transaksi WHERE nama_peminjam = '$username' AND status = 'terlambat'");
$manual_terlambat = mysqli_fetch_assoc($query_status_terlambat)['jml'] ?? 0;

$total_terlambat = $manual_terlambat + $terlambat_count;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profil Saya | Siswa</title>

    <!-- BOOTSTRAP 5 CSS (OFFLINE) -->
    <link href="../asset/css/bootstrap.min.css" rel="stylesheet">
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

        .sidebar .nav-link.active {
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

        /* Custom Card */
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            background: #fff;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            background: #fff;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>

    <div class="d-flex-wrapper">

        <!-- SIDEBAR -->
        <aside class="sidebar d-none d-md-flex">
            <div class="brand d-flex align-items-center gap-3">
                <!-- Logo Container: Background putih + Shadow -->
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
                <a class="nav-link active" href="profil.php">
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
                <h5 class="fw-bold mb-0 text-dark">Profil Saya</h5>

                <div class="d-flex align-items-center gap-3">
                    <button onclick="openModalEdit()" class="btn btn-primary fw-bold d-flex align-items-center gap-2">
                        <i class="bi bi-pencil-square"></i> Edit Profil
                    </button>

                    <div class="d-flex align-items-center gap-3">
                        <div class="text-end d-none d-sm-block">
                            <div class="fw-bold text-dark"><?= htmlspecialchars($username) ?></div>
                            <small class="text-muted">Siswa</small>
                        </div>
                        <!-- ICON AVATAR PLACEHOLDER (TANPA FOTO) -->
                        <div class="rounded-circle border border-2 border-primary overflow-hidden bg-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-person-fill text-white fs-5"></i>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="content-scroll">
                <div class="container p-0" style="max-width: 900px;">

                    <!-- Cover Section (TANPA FOTO) -->
                    <div class="card-custom overflow-hidden">
                        <!-- Cover Biru Gradient -->
                        <div class="bg-primary bg-opacity-10 h-32 w-100"></div>
                        <div class="px-5 pb-5">
                            <div class="d-flex flex-wrap justify-content-between align-items-center mt-4">
                                <div>
                                    <h3 class="fw-bold text-dark mb-1"><?= htmlspecialchars($data_user['username']) ?></h3>
                                    <div class="d-flex gap-4 text-secondary small">
                                        <div class="d-flex align-items-center gap-1">
                                            <i class="bi bi-credit-card"></i> ID: #<?= $data_user['id'] ?>
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            <i class="bi bi-calendar-event"></i> Bergabung <?= date('Y', strtotime($data_user['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <span class="badge bg-primary bg-opacity-10 text-dark border border-primary border-opacity-25 px-3 py-2 rounded-pill fw-bold">AKTIF</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Section (REAL-TIME) -->
                    <div class="card-custom bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-dark mb-0">Ringkasan Peminjaman</h5>
                            <i class="bi bi-bar-chart-line text-primary fs-4"></i>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="stat-card text-center">
                                    <i class="bi bi-book text-primary opacity-50 fs-2 d-block mb-2"></i>
                                    <h2 class="fw-bold text-primary mb-0"><?= $total_pinjam ?></h2>
                                    <p class="text-uppercase small fw-bold text-muted mt-1">Sedang Dipinjam</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card text-center">
                                    <i class="bi bi-exclamation-triangle text-danger opacity-50 fs-2 d-block mb-2"></i>
                                    <h2 class="fw-bold text-danger mb-0"><?= $total_terlambat ?></h2>
                                    <p class="text-uppercase small fw-bold text-muted mt-1">Terlambat</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card text-center">
                                    <i class="bi bi-arrow-counterclockwise text-secondary opacity-50 fs-2 d-block mb-2"></i>
                                    <h2 class="fw-bold text-dark mb-0"><?= $total_kembali ?></h2>
                                    <p class="text-uppercase small fw-bold text-muted mt-1">Sudah Kembali</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <h5 class="fw-bold text-dark mb-3">Informasi Detail</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="card-custom p-4">
                                <div class="d-flex align-items-center gap-2 text-primary mb-2">
                                    <i class="bi bi-person-badge"></i>
                                    <span class="text-uppercase small fw-bold">Username</span>
                                </div>
                                <p class="text-dark fw-medium mb-0"><?= htmlspecialchars($data_user['username']) ?></p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card-custom p-4">
                                <div class="d-flex align-items-center gap-2 text-primary mb-2">
                                    <i class="bi bi-mortarboard"></i>
                                    <span class="text-uppercase small fw-bold">Kelas</span>
                                </div>
                                <p class="text-dark fw-medium mb-0"><?= $data_user['kelas'] ?? '-' ?></p>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card-custom p-4">
                                <div class="d-flex align-items-center gap-2 text-primary mb-2">
                                    <i class="bi bi-geo-alt"></i>
                                    <span class="text-uppercase small fw-bold">Alamat</span>
                                </div>
                                <p class="text-dark fw-medium mb-0"><?= $data_user['alamat'] ?? '-' ?></p>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card-custom p-4">
                                <div class="d-flex align-items-center gap-2 text-primary mb-2">
                                    <i class="bi bi-cake"></i>
                                    <span class="text-uppercase small fw-bold">Tanggal Lahir</span>
                                </div>
                                <p class="text-dark fw-medium mb-0">
                                    <?= $data_user['tanggal_lahir'] ? date('d F Y', strtotime($data_user['tanggal_lahir'])) : '-' ?>
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- MODAL EDIT PROFIL (Bootstrap Modal) -->
    <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Edit Data Profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="profil.php" method="POST">

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Kelas</label>
                            <input type="text" name="kelas" value="<?= htmlspecialchars($data_user['kelas'] ?? '') ?>" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Alamat Lengkap</label>
                            <textarea name="alamat" rows="3" class="form-control"><?= htmlspecialchars($data_user['alamat'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" value="<?= $data_user['tanggal_lahir'] ?? '' ?>" class="form-control">
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="update_profil" class="btn btn-primary fw-bold">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- BOOTSTRAP5 JS -->
    <script src="../asset/js/bootstrap.bundle.min.js"></script>

    <!-- SCRIPT -->
    <script>
        let editModal; // Bootstrap Modal Instance

        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi Modal Bootstrap
            editModal = new bootstrap.Modal(document.getElementById('modalEdit'));
        });

        function openModalEdit() {
            editModal.show();
        }

        function closeModalEdit() {
            editModal.hide();
        }

        <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
            alert('Profil berhasil diperbarui!');
            window.history.replaceState(null, null, window.location.pathname);
        <?php endif; ?>
    </script>

</body>

</html>