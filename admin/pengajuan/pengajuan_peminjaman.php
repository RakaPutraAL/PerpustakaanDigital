<?php
session_start();
include __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
  header("Location: ../../index.php");
  exit;
}

// --- PROSES APPROVE/REJECT PEMINJAMAN ---
if (isset($_POST['action']) && isset($_POST['id_transaksi'])) {
  $id_transaksi = (int)$_POST['id_transaksi'];
  $action = $_POST['action'];

  if ($action === 'approve_pinjam') {
    // AMBIL DATA TRANSAKSI YANG AKAN DI-APPROVE
    $query_check = mysqli_query($conn, "SELECT * FROM transaksi WHERE id = $id_transaksi AND status = 'pending_pinjam'");
    $data_transaksi = mysqli_fetch_assoc($query_check);

    if ($data_transaksi) {
        $id_buku = $data_transaksi['id_buku'];
        $kode_spesifik = $data_transaksi['kode_spesifik'];
        $peminjam_aju = $data_transaksi['nama_peminjam'];

        // --- VALIDASI KONFLIK KODE BUKU ---
        // Cek apakah kode ini SUDAH dipakai oleh orang LAIN di status 'dipinjam' atau 'pending_kembali'
        $cek_konflik = mysqli_query($conn, "SELECT id FROM transaksi 
                                WHERE id_buku = '$id_buku' 
                                AND kode_spesifik = '$kode_spesifik' 
                                AND nama_peminjam != '$peminjam_aju'
                                AND status IN ('dipinjam', 'pending_kembali')
                                LIMIT 1");

        if (mysqli_num_rows($cek_konflik) > 0) {
            // JIKA KONFLIK: Kode sedang dipakai orang lain
            $_SESSION['error'] = "Gagal! Kode buku '$kode_spesifik' sedang dipinjam oleh user lain. Peminjaman ditolak otomatis.";
            mysqli_query($conn, "DELETE FROM transaksi WHERE id = $id_transaksi");

        } else {
            // JIKA TIDAK KONFLIK: Lanjutkan Approve
            $update = mysqli_query($conn, "UPDATE transaksi SET status = 'dipinjam' WHERE id = $id_transaksi");

            if ($update) {
                // Kurangi stok buku
                mysqli_query($conn, "UPDATE buku SET stok = stok - 1 WHERE id = $id_buku");
                $_SESSION['success'] = "Peminjaman berhasil disetujui!";
            } else {
                $_SESSION['error'] = "Gagal menyetujui peminjaman!";
            }
        }
    } else {
        $_SESSION['error'] = "Data transaksi tidak ditemukan!";
    }

  } elseif ($action === 'reject_pinjam') {
    // Hapus data peminjaman yang ditolak
    $delete = mysqli_query($conn, "DELETE FROM transaksi WHERE id = $id_transaksi AND status = 'pending_pinjam'");

    if ($delete) {
      $_SESSION['success'] = "Peminjaman berhasil ditolak dan dihapus!";
    } else {
      $_SESSION['error'] = "Gagal menolak peminjaman!";
    }
  } elseif ($action === 'approve_kembali') {
    // Update status dari pending_kembali menjadi kembali
    $update = mysqli_query($conn, "UPDATE transaksi SET status = 'kembali', tanggal_kembali = NOW() WHERE id = $id_transaksi AND status = 'pending_kembali'");

    if ($update) {
      // Kembalikan stok buku
      $get_buku = mysqli_query($conn, "SELECT id_buku FROM transaksi WHERE id = $id_transaksi");
      $data_buku = mysqli_fetch_assoc($get_buku);
      if ($data_buku) {
        mysqli_query($conn, "UPDATE buku SET stok = stok + 1 WHERE id = " . $data_buku['id_buku']);
      }
      $_SESSION['success'] = "Pengembalian berhasil dikonfirmasi!";
    } else {
      $_SESSION['error'] = "Gagal mengkonfirmasi pengembalian!";
    }
  } elseif ($action === 'reject_kembali') {
    // Update status dari pending_kembali menjadi dipinjam kembali
    $update = mysqli_query($conn, "UPDATE transaksi SET status = 'dipinjam' WHERE id = $id_transaksi AND status = 'pending_kembali'");
    
    if ($update) {
      $_SESSION['success'] = "Pengembalian ditolak. Status buku kembali 'Dipinjam'.";
    } else {
      $_SESSION['error'] = "Gagal menolak pengembalian!";
    }
  }

  header("Location: pengajuan_peminjaman.php");
  exit;
}

// --- FILTER TAB ---
 $tab = $_GET['tab'] ?? 'pending_pinjam';
 $valid_tabs = ['pending_pinjam', 'pending_kembali'];
if (!in_array($tab, $valid_tabs)) {
  $tab = 'pending_pinjam';
}

// --- PENCARIAN (SEARCH) ---
 $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// --- PAGINATION ---
 $page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? (int)$_GET['page'] : 1;
 $limit = 10;
 $offset = ($page - 1) * $limit;

// --- QUERY DATA PENGAJUAN DENGAN FITUR SEARCH ---
 $query = "SELECT t.*, b.judul, b.penulis, u.username, u.kelas 
          FROM transaksi t
          LEFT JOIN buku b ON t.id_buku = b.id
          LEFT JOIN users u ON t.nama_peminjam = u.username
          WHERE t.status = '$tab'";

// Jika ada input search, tambahkan kondisi WHERE
if (!empty($search)) {
  $query .= " AND (u.username LIKE '%$search%' 
                   OR u.kelas LIKE '%$search%'
                   OR b.judul LIKE '%$search%' 
                   OR t.kode_spesifik LIKE '%$search%')";
}

 $query .= " ORDER BY t.id DESC LIMIT $limit OFFSET $offset";

 $result = mysqli_query($conn, $query);

// --- TOTAL DATA UNTUK PAGINATION ---
// Hitung total data sesuai filter pencarian
 $total_query = "SELECT COUNT(*) as total FROM transaksi t
                LEFT JOIN buku b ON t.id_buku = b.id
                LEFT JOIN users u ON t.nama_peminjam = u.username
                WHERE t.status = '$tab'";

if (!empty($search)) {
  $total_query .= " AND (u.username LIKE '%$search%' 
                        OR u.kelas LIKE '%$search%'
                        OR b.judul LIKE '%$search%' 
                        OR t.kode_spesifik LIKE '%$search%')";
}

 $total_result = mysqli_query($conn, $total_query);
 $total_data = mysqli_fetch_assoc($total_result)['total'];
 $total_pages = ceil($total_data / $limit);

// --- HITUNG BADGE NOTIFIKASI ---
 $count_pending_pinjam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status = 'pending_pinjam'"))['total'];
 $count_pending_kembali = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status = 'pending_kembali'"))['total'];

// --- HITUNG BADGE NOTIFIKASI DENDA ---
 $count_denda_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status = 'pending_payment'"))['total'];

// --- NAMA ADMIN ---
 $admin_name = $_SESSION['username'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Pengajuan Peminjaman | SIPERDI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- BOOTSTRAP 5 CSS (OFFLINE) -->
  <link href="../../asset/css/bootstrap.min.css" rel="stylesheet">

  <!-- BOOTSTRAP ICONS (OFFLINE) -->
  <link rel="stylesheet" href="../../asset/font/bootstrap-icons.css">

  <style>
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

    .table-responsive {
      border-radius: 8px;
    }

    .badge-notification {
      position: absolute;
      top: -5px;
      right: -10px;
      padding: 3px 6px;
      font-size: 0.7rem;
      border-radius: 10px;
    }

    .nav-tabs .nav-link {
      border: none;
      color: #6c757d;
      font-weight: 500;
      position: relative;
    }

    .nav-tabs .nav-link.active {
      color: var(--primary-blue);
      background: transparent;
      border-bottom: 3px solid var(--primary-blue);
    }

    .nav-tabs .nav-link:hover {
      color: var(--primary-blue);
      border-color: transparent;
    }
  </style>
</head>

<body>

  <!-- ================= NAVBAR ================= -->
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
              <a class="nav-link active" href="pengajuan_peminjaman.php">
                <i class="bi bi-hourglass-split"></i> Pengajuan Peminjaman & Pengembalian
                <?php if (($count_pending_pinjam + $count_pending_kembali) > 0): ?>
                  <span class="badge bg-danger ms-auto"><?= $count_pending_pinjam + $count_pending_kembali ?></span>
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
                <i class="bi bi-database-add"></i> Backup & Restore
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../profil/profil.php">
                <i class="bi bi-person"></i> Profil
              </a>
            </li>
          </ul>

          <hr>

          <div class="nav-item">
            <a class="nav-link btn-logout" href="../konfirmasi_logout.php">
              <i class="bi bi-box-arrow-right"></i> Logout
            </a>
          </div>
        </div>
      </nav>

      <!-- ================= MAIN CONTENT ================= -->
      <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

        <!-- Judul Halaman -->
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4">
          <h2 class="fw-bold" style="color: var(--primary-blue);">
            <i class="bi bi-hourglass-split me-2"></i>Pengajuan Peminjaman & Pengembalian
          </h2>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i><?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Tab Navigation & Search -->
        <div class="card card-custom mb-4">
          <div class="card-header bg-white border-0 pt-3">
            <ul class="nav nav-tabs card-header-tabs">
              <li class="nav-item position-relative">
                <a class="nav-link <?= $tab === 'pending_pinjam' ? 'active' : '' ?>" href="?tab=pending_pinjam&search=<?= $search ?>">
                  <i class="bi bi-inbox me-2"></i>Pengajuan Pinjam
                  <?php if ($count_pending_pinjam > 0): ?>
                    <span class="badge bg-warning text-dark ms-2"><?= $count_pending_pinjam ?></span>
                  <?php endif; ?>
                </a>
              </li>
              <li class="nav-item position-relative">
                <a class="nav-link <?= $tab === 'pending_kembali' ? 'active' : '' ?>" href="?tab=pending_kembali&search=<?= $search ?>">
                  <i class="bi bi-arrow-return-left me-2"></i>Pengajuan Kembali
                  <?php if ($count_pending_kembali > 0): ?>
                    <span class="badge bg-info text-dark ms-2"><?= $count_pending_kembali ?></span>
                  <?php endif; ?>
                </a>
              </li>
            </ul>
          </div>

          <div class="card-body">
            <!-- FITUR PENCARIAN (BARU) -->
            <form method="GET" class="mb-4">
                <input type="hidden" name="tab" value="<?= $tab ?>">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Cari berdasarkan Username, Kelas, Judul Buku, atau Kode Buku..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Cari</button>
                    <a href="pengajuan_peminjaman.php?tab=<?= $tab ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Reset</a>
                </div>
            </form>

            <!-- Tabel Data -->
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>No</th>
                    <th>Peminjam</th>
                    <th>Kelas</th>
                    <th>Judul Buku</th>
                    <th>Penulis</th>
                    <th>Tanggal Pinjam</th>
                    <!-- Perubahan: Kolom Tgl Kembali sekarang selalu muncul -->
                    <th>Tgl Kembali</th>
                    <th>Kode Spesifik</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (mysqli_num_rows($result) > 0):
                    $no = $offset + 1;
                    while ($row = mysqli_fetch_assoc($result)): ?>
                      <tr>
                        <td><?= $no++ ?></td>
                        <td>
                          <div class="fw-bold"><?= htmlspecialchars($row['nama_peminjam']) ?></div>
                          <small class="text-muted"><?= htmlspecialchars($row['username']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($row['kelas'] ?? '-') ?></td>
                        <td>
                          <div class="fw-semibold"><?= htmlspecialchars($row['judul']) ?></div>
                        </td>
                        <td><?= htmlspecialchars($row['penulis']) ?></td>
                        <td>
                          <small><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></small>
                        </td>
                        <td>
                          <!-- Tampilkan Tgl Kembali (Jika ada) -->
                          <small><?= $row['tanggal_kembali'] ? date('d/m/Y', strtotime($row['tanggal_kembali'])) : '<span class="text-muted fst-italic">-</span>' ?></small>
                        </td>
                        <td>
                          <code><?= htmlspecialchars($row['kode_spesifik'] ?? '-') ?></code>
                        </td>
                        <td>
                          <?php if ($row['status'] === 'pending_pinjam'): ?>
                            <span class="badge bg-warning text-dark">
                              <i class="bi bi-clock-history"></i> Pending Pinjam
                            </span>
                          <?php elseif ($row['status'] === 'pending_kembali'): ?>
                            <span class="badge bg-info text-dark">
                              <i class="bi bi-arrow-return-left"></i> Pending Kembali
                            </span>
                          <?php endif; ?>
                        </td>
                        <td class="text-center">
                          <div class="btn-group btn-group-sm" role="group">
                            <?php if ($tab === 'pending_pinjam'): ?>
                              <!-- Tombol Approve Pinjam -->
                              <form method="POST" style="display: inline;" onsubmit="return confirm('Setujui peminjaman ini?');">
                                <input type="hidden" name="id_transaksi" value="<?= $row['id'] ?>">
                                <input type="hidden" name="action" value="approve_pinjam">
                                <button type="submit" class="btn btn-success btn-sm" title="Setujui">
                                  <i class="bi bi-check-circle"></i> Setujui
                                </button>
                              </form>
                              <!-- Tombol Reject Pinjam -->
                              <form method="POST" style="display: inline;" onsubmit="return confirm('Tolak dan hapus peminjaman ini?');">
                                <input type="hidden" name="id_transaksi" value="<?= $row['id'] ?>">
                                <input type="hidden" name="action" value="reject_pinjam">
                                <button type="submit" class="btn btn-danger btn-sm" title="Tolak">
                                  <i class="bi bi-x-circle"></i> Tolak
                                </button>
                              </form>
                            <?php elseif ($tab === 'pending_kembali'): ?>
                              <!-- Tombol Approve Kembali -->
                              <form method="POST" style="display: inline;" onsubmit="return confirm('Konfirmasi pengembalian buku ini?');">
                                <input type="hidden" name="id_transaksi" value="<?= $row['id'] ?>">
                                <input type="hidden" name="action" value="approve_kembali">
                                <button type="submit" class="btn btn-success btn-sm" title="Setujui">
                                  <i class="bi bi-check-circle"></i> Setujui
                                </button>
                              </form>
                              
                              <!-- TOMBOL TOLAK KEMBALI (BARU) -->
                              <form method="POST" style="display: inline;" onsubmit="return confirm('Tolak pengembalian? Status akan kembali menjadi Dipinjam.');">
                                <input type="hidden" name="id_transaksi" value="<?= $row['id'] ?>">
                                <input type="hidden" name="action" value="reject_kembali">
                                <button type="submit" class="btn btn-danger btn-sm" title="Tolak">
                                  <i class="bi bi-x-circle"></i> Tolak
                                </button>
                              </form>
                            <?php endif; ?>
                          </div>
                        </td>
                      </tr>
                    <?php endwhile;
                  else: ?>
                    <tr>
                      <td colspan="10" class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="text-muted mt-2">
                          <?php if ($tab === 'pending_pinjam'): ?>
                            Tidak ada pengajuan peminjaman saat ini.
                          <?php else: ?>
                            Tidak ada pengajuan pengembalian saat ini.
                          <?php endif; ?>
                        </p>
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
              <div class="d-flex justify-content-center mt-4">
                <nav aria-label="Page navigation">
                  <ul class="pagination">
                    <?php if ($page > 1): ?>
                      <li class="page-item">
                        <!-- Tambah parameter search ke URL pagination -->
                        <a class="page-link" href="?tab=<?= $tab ?>&search=<?= $search ?>&page=<?= $page - 1 ?>">
                          <i class="bi bi-chevron-left"></i>
                        </a>
                      </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                      <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?tab=<?= $tab ?>&search=<?= $search ?>&page=<?= $i ?>"><?= $i ?></a>
                      </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                      <li class="page-item">
                        <!-- Tambah parameter search ke URL pagination -->
                        <a class="page-link" href="?tab=<?= $tab ?>&search=<?= $search ?>&page=<?= $page + 1 ?>">
                          <i class="bi bi-chevron-right"></i>
                        </a>
                      </li>
                    <?php endif; ?>
                  </ul>
                </nav>
              </div>
            <?php endif; ?>

          </div>
        </div>

      </main>
    </div>
  </div>

  <!-- BOOTSTRAP 5 JS (OFFLINE) -->
  <script src="../../asset/js/bootstrap.bundle.min.js"></script>
</body>

</html>