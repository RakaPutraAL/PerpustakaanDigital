<?php
session_start();
include "../config/database.php";

if (!isset($_SESSION['login'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_SESSION['level']) && $_SESSION['level'] === 'admin') {
    header("Location: ../admin/kelola-buku/dashboard.php");
    exit;
}

// Validasi Input
if (!isset($_POST['id_buku']) || !isset($_POST['kode_pilihan']) || !isset($_POST['tanggal_pinjam']) || !isset($_POST['tanggal_kembali'])) {
    header("Location: dashboard.php");
    exit;
}

 $id_buku = (int)$_POST['id_buku'];
 $kode_pilihan = mysqli_real_escape_string($conn, $_POST['kode_pilihan']);
 $tgl_pinjam_input = mysqli_real_escape_string($conn, $_POST['tanggal_pinjam']);
 $tgl_kembali_input = mysqli_real_escape_string($conn, $_POST['tanggal_kembali']);

// Ambil data buku untuk ditampilkan
 $query_buku = mysqli_query($conn, "SELECT buku.*, detail_buku.gambar, detail_buku.kategori 
                                   FROM buku 
                                   LEFT JOIN detail_buku ON buku.id = detail_buku.id_buku 
                                   WHERE buku.id = $id_buku");
 $buku = mysqli_fetch_assoc($query_buku);

if (!$buku) {
    echo "<script>alert('Buku tidak ditemukan!'); window.location='dashboard.php';</script>";
    exit;
}

// LOGIKA PROSES KONFIRMASI
if (isset($_POST['konfirmasi_aksi'])) {
    $username = $_SESSION['username'];

    if ($tgl_kembali_input < $tgl_pinjam_input) {
        $error = "Tanggal kembali harus lebih besar dari tanggal pinjam.";
    } else {
        // PERUBAHAN: Kita cek stok > 0 agar user tidak meminjam buku yang stoknya habis total
        // Namun stok TIDAK dikurangi disini, hanya validasi saja
        if ($buku['stok'] > 0) {
            
            // PERUBAHAN: Status di set ke 'pending_pinjam' menunggu admin
            // PERUBAHAN: Stok TIDAK dikurangi disini
            $insert = mysqli_query($conn, "INSERT INTO transaksi (id_buku, nama_peminjam, kode_spesifik, tanggal_pinjam, tanggal_kembali, status) 
                                           VALUES ('$id_buku', '$username', '$kode_pilihan', '$tgl_pinjam_input', '$tgl_kembali_input', 'pending_pinjam')");

            if ($insert) {
                // Redirect dengan parameter sukses
                header("Location: peminjaman.php?status=pinjam_pending");
                exit;
            } else {
                $error = "Gagal mengajukan peminjaman. Coba lagi.";
            }
        } else {
            $error = "Maaf, stok buku habis.";
        }
    }
}

 $nama_user = $_SESSION['username'] ?? 'Siswa';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Konfirmasi Peminjaman | Siswa</title>

    <!-- BOOTSTRAP 5 CSS (OFFLINE) -->
    <link href="../asset/css/bootstrap.min.css" rel="stylesheet">

    <!-- BOOTSTRAP ICONS (OFFLINE) -->
    <link rel="stylesheet" href="../asset/font/bootstrap-icons.css">

    <style>
        :root {
            --bs-primary: #0d6efd;
            /* Bootstrap Blue */
            --sidebar-bg: #1a1e21;
            /* Very dark blue/grey */
            --sidebar-text: #e9ecef;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            /* Font Offline */
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
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            background: #fff;
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
                <h5 class="fw-bold mb-0 text-dark">Konfirmasi Peminjaman</h5>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end d-none d-sm-block">
                        <div class="fw-bold text-dark"><?= htmlspecialchars($nama_user) ?></div>
                        <small class="text-muted">Siswa</small>
                    </div>

                    <!-- ICON OFFLINE SEPERTI DASHBOARD -->
                    <div class="rounded-circle border border-2 border-primary overflow-hidden bg-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-person-fill text-white fs-5"></i>
                    </div>
                </div>
            </header>

            <!-- Scrollable Content -->
            <div class="content-scroll">
                <div class="container p-0 d-flex justify-content-center align-items-start h-100">

                    <div class="card-custom w-100 overflow-hidden" style="max-width: 900px;">

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger m-0 rounded-0 border-0">
                                <?= $error ?>
                                <br>
                                <a href="detail_buku.php?id=<?= $id_buku ?>" class="alert-link text-decoration-none">Kembali ke Detail Buku</a>
                            </div>
                        <?php else: ?>

                            <div class="row g-0">
                                <!-- Kolom Kiri: Gambar -->
                                <div class="col-md-4 bg-light p-4 d-flex align-items-center justify-content-center">
                                    <div style="aspect-ratio: 3/4;" class="bg-light rounded-3 mb-3 overflow-hidden d-flex align-items-center justify-content-center shadow-sm border">
                                        <?php if (!empty($buku['gambar'])): ?>
                                            <!-- Jika ada gambar upload, tampilkan gambar -->
                                            <img class="w-100 h-100 object-fit-cover" src="../uploads/<?= $buku['gambar'] ?>" alt="Cover Buku">
                                        <?php else: ?>
                                            <!-- JIKA TIDAK ADA GAMBAR, TAMPILKAN ICON OFFLINE -->
                                            <div class="text-center p-4">
                                                <i class="bi bi-book text-secondary" style="font-size: 5rem; opacity: 0.3;"></i>
                                                <div class="small text-muted fw-bold mt-2">No Cover</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Kolom Kanan: Detail & Form -->
                                <div class="col-md-8 p-5 d-flex flex-column justify-content-center">
                                    <small class="text-uppercase text-muted fw-bold mb-2" style="letter-spacing: 1px;">Anda akan meminjam</small>
                                    <h2 class="fw-bold text-dark mb-3 display-6"><?= htmlspecialchars($buku['judul']) ?></h2>


                                    <!-- Tampilkan Kode Pilihan -->
                                    <div class="mb-4">
                                        <span class="badge bg-primary text-white px-3 py-2 rounded fs-5 fw-bold font-monospace">
                                            Kode: <?= $kode_pilihan ?>
                                        </span>
                                    </div>

                                    <!-- Info Tanggal -->
                                    <div class="bg-light rounded p-3 mb-5 border">
                                        <div class="row align-items-center text-center">
                                            <div class="col-5">
                                                <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.75rem;">Tanggal Pinjam</small>
                                                <span class="fw-bold text-dark fs-5"><?= date('d M Y', strtotime($tgl_pinjam_input)) ?></span>
                                            </div>
                                            <div class="col-2 text-primary">
                                                <i class="bi bi-arrow-right-circle-fill fs-4"></i>
                                            </div>
                                            <div class="col-5">
                                                <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 0.75rem;">Tanggal Kembali</small>
                                                <span class="fw-bold text-dark fs-5"><?= date('d M Y', strtotime($tgl_kembali_input)) ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- PERUBAHAN TEKS INFORMASI -->
                                    <div class="alert alert-info mb-4 py-2">
                                        <small class="mb-0"><i class="bi bi-info-circle-fill me-1"></i> Peminjaman akan dikirim ke Admin untuk disetujui.</small>
                                    </div>

                                    <!-- Tombol Aksi -->
                                    <form method="POST" class="d-flex gap-3">
                                        <input type="hidden" name="id_buku" value="<?= $id_buku ?>">
                                        <input type="hidden" name="kode_pilihan" value="<?= $kode_pilihan ?>">
                                        <input type="hidden" name="tanggal_pinjam" value="<?= $tgl_pinjam_input ?>">
                                        <input type="hidden" name="tanggal_kembali" value="<?= $tgl_kembali_input ?>">

                                        <a href="detail_buku.php?id=<?= $id_buku ?>" class="btn btn-outline-secondary py-3 px-4 fw-bold flex-fill">
                                            Batal
                                        </a>

                                        <button type="submit" name="konfirmasi_aksi" class="btn btn-primary py-3 px-5 fw-bold flex-fill d-flex align-items-center justify-content-center gap-2 shadow">
                                            <i class="bi bi-send-fill"></i> Ajukan Peminjaman
                                        </button>
                                    </form>
                                </div>
                            </div>

                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- BOOTSTRAP 5 JS (OFFLINE) -->
    <script src="../asset/js/bootstrap.bundle.min.js"></script>
</body>

</html>