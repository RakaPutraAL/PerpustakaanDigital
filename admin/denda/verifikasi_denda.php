<?php
session_start();
include __DIR__ . "/../../config/database.php";

// 1. Cek Sesi & Level Admin
if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
    die("Akses ditolak. Halaman ini khusus Administrator.");
}

// --- HITUNG BADGE NOTIFIKASI PENGAJUAN (TAMBAHKAN INI) ---
$count_pending_pinjam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status = 'pending_pinjam'"))['total'];
$count_pending_kembali = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status = 'pending_kembali'"))['total'];
$total_pending = $count_pending_pinjam + $count_pending_kembali;

// --- HITUNG BADGE NOTIFIKASI DENDA ---
$count_denda_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status = 'pending_payment'"))['total'];

// --- KONFIGURASI DENDA (Disamakan dengan peminjaman.php) ---
$denda_per_hari = 1500;

// --- LOGIKA ACTION (SETUJUI / TOLAK) ---
$message = "";
$msg_type = "";

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    // Ambil data transaksi yang akan diproses
    $q_check = mysqli_query($conn, "SELECT * FROM transaksi WHERE id = $id");
    $data = mysqli_fetch_assoc($q_check);

    if ($data) {
        if ($action == 'approve') {
            // ADMIN MENYETUJUI PEMBAYARAN DENDA

            // 1. Ambil Tanggal Kembali (Due Date) dari database
            $tgl_kembali_db = $data['tanggal_kembali'];

            // 2. Hitung Denda Final (Menggunakan Logika PHP DateTime agar presisi)
            $tgl_kembali_obj = new DateTime($tgl_kembali_db);
            // Tanggal hari ini (saat admin menyetujui)
            $today_obj = new DateTime();

            $hitung_denda = 0;

            // Cek apakah hari ini lewat dari tanggal kembali
            if ($today_obj > $tgl_kembali_obj) {
                $interval = $today_obj->diff($tgl_kembali_obj);
                $hari_terlambat = $interval->days;

                // Pastikan minimal terlambat 1 hari jika diff menunjukkan ada keterlambatan
                if ($hari_terlambat < 1) $hari_terlambat = 1;

                // Rumus Denda
                $hitung_denda = $hari_terlambat * $denda_per_hari;
            } else {
                // Jika ternyata tidak terlambat (misal admin approve tepat waktu atau user bayar prematur)
                $hitung_denda = 0;
            }

            // 3. UPDATE DATABASE
            $today_date = date('Y-m-d'); // Tanggal realisasi kembali

            // Update status jadi 'kembali', simpan nilai denda final, dan update tanggal kembali real
            $query_update = "UPDATE transaksi SET 
                                status = 'kembali', 
                                denda = $hitung_denda, 
                                tanggal_kembali = '$today_date' 
                             WHERE id = $id";

            if (mysqli_query($conn, $query_update)) {
                // 4. Kembalikan Stok Buku (+1)
                $id_buku = $data['id_buku'];
                mysqli_query($conn, "UPDATE buku SET stok = stok + 1 WHERE id = $id_buku");

                $message = "Berhasil! Pembayaran denda diverifikasi. Transaksi selesai.";
                $msg_type = "success";
            } else {
                $message = "Gagal memverifikasi: " . mysqli_error($conn);
                $msg_type = "danger";
            }
        } elseif ($action == 'reject') {
            // ADMIN MENOLAK PEMBAYARAN
            // Kembalikan status ke 'dipinjam' agar siswa bisa mencoba lagi atau membawa bukunya
            $query_reject = "UPDATE transaksi SET status = 'dipinjam' WHERE id = $id";

            if (mysqli_query($conn, $query_reject)) {
                $message = "Verifikasi pembayaran ditolak. Status transaksi dikembalikan ke 'Dipinjam'.";
                $msg_type = "warning";
            } else {
                $message = "Gagal menolak verifikasi.";
                $msg_type = "danger";
            }
        }
    }
}

// --- PAGINATION & FILTER ---
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Query Hitung Data 
// PERUBAHAN: Hanya ambil yang statusnya 'pending_payment' (Sudah diklik bayar oleh user)
// Yang status 'dipinjam' (terlambat tapi belum diklik bayar) DIHAPUS/DISembunyikan
$query_count = "
    SELECT COUNT(*) as total 
    FROM transaksi
    JOIN buku ON transaksi.id_buku = buku.id
    WHERE transaksi.status = 'pending_payment'
";

if (!empty($search)) {
    $query_count .= " AND (buku.judul LIKE '%$search%' OR transaksi.nama_peminjam LIKE '%$search%')";
}

$result_count = mysqli_query($conn, $query_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_data = $row_count['total'];
$total_pages = ceil($total_data / $limit);

// Query Data Utama
// PERUBAHAN: Filter status HANYA 'pending_payment'
$query = "SELECT transaksi.*, buku.judul, detail_buku.gambar, buku.penerbit, detail_buku.kategori
          FROM transaksi
          JOIN buku ON transaksi.id_buku = buku.id
          LEFT JOIN detail_buku ON buku.id = detail_buku.id_buku
          WHERE transaksi.status = 'pending_payment'
          " . (!empty($search) ? " AND (buku.judul LIKE '%$search%' OR transaksi.nama_peminjam LIKE '%$search%')" : "") . "
          ORDER BY transaksi.tanggal_kembali ASC 
          LIMIT $start, $limit";

$transaksi = mysqli_query($conn, $query);

$admin_name = $_SESSION['username'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Verifikasi Denda | Admin Perpustakaan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- BOOTSTRAP 5 CSS -->
    <link href="../../asset/css/bootstrap.min.css" rel="stylesheet">
    <!-- BOOTSTRAP ICONS -->
    <link rel="stylesheet" href="../../asset/font/bootstrap-icons.css">

    <style>
        :root {
            --primary-blue: #0d6efd;
            --bg-soft: #f8f9f6;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-soft);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar-custom {
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            height: 64px;
            border-bottom: 1px solid #e5e7eb;
        }

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

        .sidebar .nav-link.active {
            background-color: rgba(13, 110, 253, 0.15);
            color: var(--primary-blue);
            font-weight: 600;
            box-shadow: none;
            border-left: 4px solid var(--primary-blue);
        }

        .sidebar .btn-logout {
            color: #dc3545;
        }

        .sidebar .btn-logout:hover {
            background-color: #fff5f5;
            color: #dc3545;
        }

        .card-custom {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

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

        .table-custom th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            color: #6c757d;
            letter-spacing: 0.5px;
        }

        .table td {
            vertical-align: middle;
        }

        .badge-kategori {
            background-color: #e7f1ff;
            color: #0d6efd;
            border: 1px solid #cde4ff;
            font-size: 0.75rem;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-custom fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center text-primary fw-bold" href="../kelola-buku/dashboard.php" style="color: var(--primary-blue) !important; text-decoration: none;">
                <div class="bg-white rounded p-2 shadow-sm d-flex align-items-center justify-content-center me-2" style="width: 42px; height: 42px;">
                    <img src="../../asset/img/logo.png" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <span class="fs-5">SIPERDI</span>
            </a>
            <div class="d-none d-md-block text-end">
                <div class="fw-bold text-dark">Halo, <?= htmlspecialchars($admin_name) ?></div>
                <small class="text-muted">Administrator</small>
            </div>
        </div>
    </nav>

    <div class="container-fluid pt-5 mt-4">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse show">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link" href="../kelola-buku/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="../kelola-buku/kelola_buku.php"><i class="bi bi-book"></i> Kelola Buku</a></li>
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
                        <!-- Menu Aktif -->
                        <li class="nav-item">
                            <a class="nav-link active" href="../denda/verifikasi_denda.php">
                                <i class="bi bi-cash-coin"></i> Verifikasi Denda
                                <?php if ($count_denda_pending > 0): ?>
                                    <span class="badge bg-danger ms-auto"><?= $count_denda_pending ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="../kelola-anggota/anggota.php"><i class="bi bi-people"></i> Kelola Anggota</a></li>
                        <li class="nav-item"><a class="nav-link" href="../laporan/laporan.php"><i class="bi bi-file-earmark-bar-graph"></i> Laporan</a></li>
                        <li class="nav-item"><a class="nav-link" href="../backup/backup.php"><i class="bi bi-database-add"></i> Backup & Restore</a></li>
                        <li class="nav-item"><a class="nav-link" href="../profil/profil.php"><i class="bi bi-person"></i> Profil</a></li>
                    </ul>
                    <hr>
                    <div class="nav-item"><a class="nav-link btn-logout" href="../konfirmasi_logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></div>
                </div>
            </nav>

            <!-- MAIN CONTENT -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4">
                    <h2 class="fw-bold" style="color: var(--primary-blue);">Verifikasi Pembayaran Denda</h2>
                    <a href="../transaksi/transaksi.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Ke Data Transaksi
                    </a>
                </div>

                <!-- Notifikasi -->
                <?php if ($message): ?>
                    <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show mb-4" role="alert">
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- FILTER SEARCH -->
                <div class="card card-custom p-3 mb-4">
                    <form method="get">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control border-start-0 bg-light" placeholder="Cari Judul Buku atau Nama Peminjam...">
                            <button type="submit" class="btn btn-primary">Cari</button>
                            <a href="verifikasi_denda.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                        </div>
                    </form>
                </div>

                <!-- TABEL DATA -->
                <div class="card card-custom border-0 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-custom">
                                <tr>
                                    <th>Peminjam</th>
                                    <th>Judul Buku</th>
                                    <th>Jatuh Tempo (Tgl Kembali)</th>
                                    <th>Status</th>
                                    <th>Estimasi Denda</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($transaksi) > 0): ?>
                                    <?php while ($t = mysqli_fetch_assoc($transaksi)):

                                        // Karena filter HANYA pending_payment, kita tidak perlu if-else status rumit lagi.
                                        // Namun kita tetap hitung estimasi denda untuk ditampilkan di tabel sebelum disetujui.

                                        $display_status = $t['status']; // Pasti 'pending_payment'

                                        // Hitung estimasi denda (Sama dengan logic approve)
                                        $estimasi_denda = 0;
                                        if (!empty($t['tanggal_kembali'])) {
                                            $today_timestamp = time();
                                            $due_timestamp = strtotime($t['tanggal_kembali'] . ' 23:59:59');

                                            if ($today_timestamp > $due_timestamp) {
                                                $selisih_detik = $today_timestamp - $due_timestamp;
                                                $hari_terlambat = ceil($selisih_detik / (60 * 60 * 24));
                                                if ($hari_terlambat < 1) $hari_terlambat = 1;
                                                $estimasi_denda = $hari_terlambat * $denda_per_hari;
                                            }
                                        }

                                        $label_status = '<span class="badge bg-info text-dark">Menunggu Verifikasi</span>';
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                        <i class="bi bi-person-fill text-secondary"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark"><?= htmlspecialchars($t['nama_peminjam']) ?></div>
                                                        <small class="text-muted">ID Transaksi: #<?= $t['id'] ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($t['judul']) ?></div>
                                                <small class="text-muted"><?= $t['penerbit'] ?></small>
                                            </td>
                                            <td>
                                                <div class="text-danger small fw-bold">
                                                    <i class="bi bi-calendar-event"></i> <?= $t['tanggal_kembali'] ?>
                                                </div>
                                                <?php
                                                // Tampilkan info hari terlambat jika ada
                                                $today = date('Y-m-d');
                                                if ($today > $t['tanggal_kembali']) {
                                                    $d1 = new DateTime($t['tanggal_kembali']);
                                                    $d2 = new DateTime($today);
                                                    $diff = $d1->diff($d2);
                                                    echo '<span class="badge bg-danger bg-opacity-10 text-dark border border-danger border-opacity-25 mt-1">+' . $diff->days . ' Hari</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><?= $label_status ?></td>
                                            <td class="fw-bold text-danger">
                                                Rp <?= number_format($estimasi_denda) ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <!-- TOMBOL SETUJUI -->
                                                    <a href="?action=approve&id=<?= $t['id'] ?>&search=<?= $search ?>&page=<?= $page ?>"
                                                        onclick="return confirm('Konfirmasi pembayaran denda sebesar Rp <?= number_format($estimasi_denda) ?>? \n\nStatus akan berubah menjadi SELESAI dan stok buku akan dikembalikan.')"
                                                        class="btn btn-sm btn-success" title="Setujui Pembayaran">
                                                        <i class="bi bi-check-lg"></i> Setuju
                                                    </a>

                                                    <!-- TOMBOL TOLAK -->
                                                    <a href="?action=reject&id=<?= $t['id'] ?>&search=<?= $search ?>&page=<?= $page ?>"
                                                        onclick="return confirm('Tolak bukti pembayaran ini? \n\nStatus transaksi akan dikembalikan ke DIPINJAM.')"
                                                        class="btn btn-sm btn-danger" title="Tolak Pembayaran">
                                                        <i class="bi bi-x-lg"></i> Tolak
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <div class="mb-3">
                                                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="fw-bold text-secondary">Tidak Ada Antrian Verifikasi</h5>
                                            <p>Tidak ada siswa yang mengajukan pembayaran denda saat ini.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION -->
                    <?php if ($total_pages > 1): ?>
                        <div class="card-footer bg-white border-top py-3">
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0 justify-content-end">
                                    <?php
                                    $search_link = !empty($search) ? "search=$search&" : "";
                                    if ($page > 1): ?>
                                        <li class="page-item"><a class="page-link" href="?<?= $search_link ?>page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a></li>
                                    <?php endif; ?>

                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                            <a class="page-link" href="?<?= $search_link ?>page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= $search_link ?>page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- BOOTSTRAP JS -->
    <script src="../../asset/js/bootstrap.bundle.min.js"></script>
</body>

</html>