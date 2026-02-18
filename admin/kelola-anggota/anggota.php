<?php
session_start();
include __DIR__ . "/../../config/database.php";

// Cek login admin
if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
    die("Akses ditolak");
}


// --- HITUNG BADGE NOTIFIKASI PENGAJUAN (TAMBAHKAN INI) ---
$count_pending_pinjam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status = 'pending_pinjam'"))['total'];
$count_pending_kembali = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status = 'pending_kembali'"))['total'];
$total_pending = $count_pending_pinjam + $count_pending_kembali;


// --- HITUNG BADGE NOTIFIKASI DENDA ---
 $count_denda_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status = 'pending_payment'"))['total'];
 


// --- KONFIGURASI PAGINATION ---
$perPage = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $perPage) - $perPage : 0;

// Ambil parameter pencarian dan filter
$search = $_GET['search'] ?? '';
$filter_level = $_GET['filter_level'] ?? 'all';
$filter_status = $_GET['filter_status'] ?? 'all';

$search_sql = mysqli_real_escape_string($conn, $search);
$filter_level_sql = mysqli_real_escape_string($conn, $filter_level);
$filter_status_sql = mysqli_real_escape_string($conn, $filter_status);

// 1. Hitung Total Data
$query_count = "SELECT COUNT(*) as total FROM users";
$conditions = [];

// --- MODIFIKASI LOGIKA SEARCH ---
// Mencari di Username, Kelas, atau Tahun Lahir (4 digit terakhir dari tanggal lahir)
if (!empty($search_sql)) {
    $conditions[] = "(username LIKE '%$search_sql%' OR kelas LIKE '%$search_sql%' OR YEAR(tanggal_lahir) LIKE '%$search_sql%')";
}
// -------------------------------

if ($filter_level_sql != 'all') {
    $conditions[] = "level='$filter_level_sql'";
}
if ($filter_status_sql != 'all') {
    $conditions[] = "status='$filter_status_sql'";
}

if (!empty($conditions)) {
    $query_count .= " WHERE " . implode(' AND ', $conditions);
}

$result_count = mysqli_query($conn, $query_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_data = $row_count['total'];

$total_pages = ceil($total_data / $perPage);

// 2. Query Data
$query = "SELECT id, username, level, kelas, alamat, tanggal_lahir, status FROM users";

if (!empty($conditions)) {
    $query .= " WHERE " . implode(' AND ', $conditions);
}

$query .= " ORDER BY id DESC LIMIT $start, $perPage";
$result = mysqli_query($conn, $query);

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

$admin_name = $_SESSION['username'] ?? $_SESSION['nama'] ?? 'Admin';
if ($admin_name === 'Admin' && isset($_SESSION['id'])) {
    $query_user = mysqli_query($conn, "SELECT username FROM users WHERE id = " . (int)$_SESSION['id']);
    $data_user = mysqli_fetch_assoc($query_user);
    if ($data_user) {
        $admin_name = $data_user['username'];
        $_SESSION['username'] = $admin_name;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kelola Anggota | Admin Perpustakaan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../../asset/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../asset/font/bootstrap-icons.css">
    <style>
        /* CSS Tetap Sama */
        :root {
            --primary-blue: #0d6efd;
            --bg-soft: #f8f9f6;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-soft);
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

        .table thead th {
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

        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .pagination .page-link {
            color: var(--primary-blue);
            border-color: #e9ecef;
            font-weight: 500;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-custom fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center text-primary fw-bold" href="#" style="color: var(--primary-blue) !important; text-decoration: none;">
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

            <!-- SIDEBAR -->
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
                        <li class="nav-item"><a class="nav-link" href="../transaksi/transaksi.php"><i class="bi bi-arrow-left-right"></i> Transaksi</a></li>
                        <li class="nav-item">
                            <a class="nav-link" href="../denda/verifikasi_denda.php">
                                <i class="bi bi-cash-coin"></i> Verifikasi Denda
                                <?php if ($count_denda_pending > 0): ?>
                                    <span class="badge bg-danger ms-auto"><?= $count_denda_pending ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item"><a class="nav-link active" href="anggota.php"><i class="bi bi-people"></i> Kelola Anggota</a></li>
                        <li class="nav-item">
                            <a class="nav-link" href="../laporan/laporan.php">
                                <i class="bi bi-file-earmark-bar-graph"></i> Laporan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../backup/backup.php">
                                <i class="bi bi-database-add"></i> Backup & Restore
                            </a>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="../profil/profil.php"><i class="bi bi-person"></i> Profil</a></li>
                    </ul>
                    <hr>
                    <div class="nav-item">
                        <a class="nav-link btn-logout" href="../konfirmasi_logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </div>
                </div>
            </nav>

            <!-- MAIN CONTENT -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4">
                    <h2 class="fw-bold" style="color: var(--primary-blue);">Kelola Data Anggota</h2>

                    <!-- Tombol Aksi (Import, Export, Tambah) -->
                    <div class="btn-group">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalImport">
                            <i class="bi bi-upload me-1"></i> Import Excel
                        </button>
                        <a href="ekspor_anggota.php" class="btn btn-info text-white">
                            <i class="bi bi-download me-1"></i> Ekspor Excel
                        </a>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Anggota
                        </button>
                    </div>
                </div>

                <!-- FORM PENCARIAN & FILTER -->
                <div class="card card-custom p-3 mb-4">
                    <form method="get">
                        <div class="row g-3 align-items-end">
                            <!-- Kolom Search digabung -->
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">Cari Data</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control border-start-0 bg-light" placeholder="Username, Kelas, atau Tahun Lahir...">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted">Level</label>
                                <select name="filter_level" class="form-select">
                                    <option value="all" <?= $filter_level == 'all' ? 'selected' : '' ?>>Semua Level</option>
                                    <option value="admin" <?= $filter_level == 'admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="user" <?= $filter_level == 'user' ? 'selected' : '' ?>>User</option>
                                </select>
                            </div>

                            <!-- FILTER STATUS -->
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted">Status</label>
                                <select name="filter_status" class="form-select">
                                    <option value="all" <?= $filter_status == 'all' ? 'selected' : '' ?>>Semua Status</option>
                                    <option value="aktif" <?= $filter_status == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                    <option value="pending" <?= $filter_status == 'pending' ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                                </select>
                            </div>

                            <div class="col-md-2 d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i></button>
                                <a href="anggota.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- TABEL DATA ANGGOTA -->
                <div class="card card-custom border-0 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="18%">Username</th>
                                    <th width="12%">Kelas/Jabatan</th>
                                    <th width="10%">Level</th>
                                    <th width="15%">Status</th>
                                    <th width="20%">Alamat</th>
                                    <th width="10%">Tgl Lahir</th>
                                    <th width="10%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = $start + 1;
                                if (mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td class="text-muted"><?= $no++ ?></td>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($row['username']) ?></td>
                                            <td><?= htmlspecialchars($row['kelas'] ?? '-') ?></td>
                                            <td>
                                                <span class="badge <?= ($row['level'] ?? 'user') == 'admin' ? 'bg-danger text-white' : 'bg-primary text-white' ?>">
                                                    <?= ucfirst($row['level'] ?? 'user') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($row['status'] == 'aktif'): ?>
                                                    <span class="badge bg-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-truncate" style="max-width: 180px;" title="<?= htmlspecialchars($row['alamat'] ?? '') ?>"><?= htmlspecialchars($row['alamat'] ?? '-') ?></td>
                                            <td class="text-muted small"><?= $row['tanggal_lahir'] ?? '-' ?></td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">

                                                    <?php if ($row['status'] == 'pending'): ?>
                                                        <!-- Tombol Terima (Konfirmasi) -->
                                                        <a href="update_status.php?id=<?= $row['id'] ?>&status=aktif"
                                                            class="btn btn-sm btn-success" title="Konfirmasi / Terima">
                                                            <i class="bi bi-check-circle-fill"></i>
                                                        </a>

                                                        <!-- Tombol TOLAK (Hapus) -->
                                                        <a href="hapus.php?id=<?= $row['id'] ?>"
                                                            onclick="return confirm('Apakah Anda yakin ingin MENOLAK dan menghapus pendaftaran <?= htmlspecialchars($row['username']) ?>?')"
                                                            class="btn btn-sm btn-danger"
                                                            title="Tolak / Hapus">
                                                            <i class="bi bi-x-circle-fill"></i>
                                                        </a>
                                                    <?php endif; ?>

                                                    <!-- Tombol Edit (Untuk semua user) -->
                                                    <button onclick="openModalEdit(
                                                        '<?= $row['id'] ?>',
                                                        '<?= htmlspecialchars($row['username']) ?>',
                                                        '<?= htmlspecialchars($row['kelas'] ?? '') ?>',
                                                        '<?= htmlspecialchars($row['alamat'] ?? '') ?>',
                                                        '<?= $row['tanggal_lahir'] ?? '' ?>',
                                                        '<?= $row['level'] ?? 'user' ?>',
                                                        '<?= $row['status'] ?? 'pending' ?>'
                                                    )" class="btn btn-sm btn-primary" title="Edit Data">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>

                                                    <!-- Tombol Hapus Biasa (Hanya muncul jika BUKAN pending) -->
                                                    <?php if ($row['status'] != 'pending'): ?>
                                                        <a href="hapus.php?id=<?= $row['id'] ?>" onclick="return confirm('Yakin ingin menghapus anggota <?= htmlspecialchars($row['username']) ?>?')" class="btn btn-sm btn-danger" title="Hapus">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    <?php endif; ?>

                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile;
                                } else { ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            Data tidak ditemukan.
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION -->
                    <?php if ($total_data > $perPage): ?>
                        <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
                            <div class="text-muted small">Menampilkan <?= $start + 1 ?> - <?= min($start + $perPage, $total_data) ?> dari total <?= $total_data ?> data</div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination mb-0">
                                    <li class="page-item <?= ($page == 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= $search ?>&filter_level=<?= $filter_level ?>&filter_status=<?= $filter_status ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    <li class="page-item active"><span class="page-link"><?= $page ?></span></li>
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= $search ?>&filter_level=<?= $filter_level ?>&filter_status=<?= $filter_status ?>"><?= $page + 1 ?></a></li>
                                    <?php endif; ?>
                                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= $search ?>&filter_level=<?= $filter_level ?>&filter_status=<?= $filter_status ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- MODAL IMPORT ANGGOTA -->
    <div class="modal fade" id="modalImport" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Import Data Anggota dari Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Perhatian:</strong> Pastikan format Excel sesuai dengan template.
                    </div>

                    <div class="mb-3">
                        <a href="template_anggota.php" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-download me-2"></i> Download Template Excel
                        </a>
                    </div>

                    <form action="impor_anggota.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Pilih File Excel</label>
                            <input type="file" name="file_excel" class="form-control" accept=".xlsx,.xls" required>
                            <div class="form-text">Format: .xlsx atau .xls (Maksimal 5MB)</div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="import" class="btn btn-success">
                                <i class="bi bi-upload me-1"></i> Import Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH -->
    <div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Tambah Anggota Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="tambah.php">
                        <div class="mb-3"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Kelas</label><input type="text" name="kelas" class="form-control"></div>
                        <div class="mb-3"><label class="form-label">Alamat</label><textarea name="alamat" rows="3" class="form-control"></textarea></div>
                        <div class="mb-3"><label class="form-label">Tanggal Lahir</label><input type="date" name="tanggal_lahir" class="form-control" required></div>
                        <div class="mb-3">
                            <label class="form-label">Level</label>
                            <select name="level" class="form-select">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="pending">Pending (Perlu Konfirmasi)</option>
                                <option value="aktif">Aktif (Langsung Bisa Login)</option>
                            </select>
                        </div>
                        <div class="text-end mt-4">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="tambah" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT -->
    <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Edit Anggota</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="update.php">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3"><label class="form-label">Username</label><input type="text" name="username" id="edit_username" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Password Baru</label><input type="password" name="password" placeholder="Kosongkan jika tidak ingin diubah" class="form-control"></div>
                        <div class="mb-3"><label class="form-label">Kelas</label><input type="text" id="edit_kelas" name="kelas" class="form-control"></div>
                        <div class="mb-3"><label class="form-label">Alamat</label><textarea id="edit_alamat" name="alamat" rows="3" class="form-control"></textarea></div>
                        <div class="mb-3"><label class="form-label">Tanggal Lahir</label><input type="date" name="tanggal_lahir" id="edit_tanggal_lahir" class="form-control" required></div>
                        <div class="mb-3">
                            <label class="form-label">Level</label>
                            <select name="level" id="edit_level" class="form-select">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="pending">Pending</option>
                                <option value="aktif">Aktif</option>
                            </select>
                        </div>
                        <div class="text-end mt-4">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../../asset/js/bootstrap.bundle.min.js"></script>
    <script>
        function openModalEdit(id, username, kelas, alamat, tanggal, level, status) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_kelas').value = kelas;
            document.getElementById('edit_alamat').value = alamat;
            document.getElementById('edit_tanggal_lahir').value = tanggal;
            document.getElementById('edit_level').value = level;
            document.getElementById('edit_status').value = status;
            var myModal = new bootstrap.Modal(document.getElementById('modalEdit'));
            myModal.show();
        }
    </script>
</body>

</html>