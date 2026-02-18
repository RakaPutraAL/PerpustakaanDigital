<?php
session_start();
include __DIR__ . "/../../config/database.php";

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
 $limit = 10;
 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
 $start = ($page > 1) ? ($page * $limit) - $limit : 0;

// Ambil parameter filter
 $tgl_awal  = $_GET['tgl_awal'] ?? '';
 $tgl_akhir = $_GET['tgl_akhir'] ?? '';
 $filter_status = $_GET['status'] ?? '';

 $denda_per_hari = 1500;

 $data = null;
 $total_data = 0;
 $total_pages = 0;
 $total_denda_semua = 0;

// Label untuk Header Cetak
 $status_label_print = "Semua Status";
if ($filter_status == 'dipinjam') $status_label_print = "Sedang Dipinjam";
elseif ($filter_status == 'pending_pinjam') $status_label_print = "Menunggu Konfirmasi Pinjam";
elseif ($filter_status == 'pending_kembali') $status_label_print = "Menunggu Konfirmasi Kembali";
elseif ($filter_status == 'kembali') $status_label_print = "Selesai";
elseif ($filter_status == 'terlambat') $status_label_print = "Terlambat";
elseif ($filter_status == 'pending_payment') $status_label_print = "Menunggu Bayar";

if ($tgl_awal && $tgl_akhir) {

  // 1. QUERY COUNTING (Untuk Pagination Web)
  $query_count = "
        SELECT COUNT(*) as total 
        FROM transaksi t
        JOIN buku b ON t.id_buku = b.id
        LEFT JOIN detail_buku db ON b.id = db.id_buku
        WHERE DATE(t.tanggal_pinjam) BETWEEN '$tgl_awal' AND '$tgl_akhir'
    ";

  if (!empty($filter_status)) {
    if ($filter_status == 'terlambat') {
      // Khusus Terlambat: Status dipinjam TAPI tanggal lewat
      $query_count .= " AND t.status = 'dipinjam' AND t.tanggal_kembali < CURDATE()";
    } elseif ($filter_status == 'dipinjam') {
      // Khusus Sedang Dipinjam: Status dipinjam DAN tanggal BELUM lewat (Aman)
      $query_count .= " AND t.status = 'dipinjam' AND (t.tanggal_kembali >= CURDATE() OR t.tanggal_kembali IS NULL)";
    } else {
      // Status lain (kembali, pending_payment, pending_pinjam, pending_kembali)
      $query_count .= " AND t.status = '$filter_status'";
    }
  }

  $result_count = mysqli_query($conn, $query_count);
  $row_count = mysqli_fetch_assoc($result_count);
  $total_data = $row_count['total'];
  $total_pages = ceil($total_data / $limit);

  // 2. QUERY DATA UTAMA (AMBIL SEMUA DATA AGAR TOTAL DENDA AKURAT) + KOLOM DENDA
  $query = "
        SELECT 
            t.id as TransaksiID,
            t.tanggal_pinjam,
            t.tanggal_kembali,
            t.nama_peminjam,
            b.judul,
            b.penerbit,
            db.kategori,
            t.kode_spesifik,
            t.status,
            t.denda
        FROM transaksi t
        JOIN buku b ON t.id_buku = b.id
        LEFT JOIN detail_buku db ON b.id = db.id_buku
        WHERE DATE(t.tanggal_pinjam) BETWEEN '$tgl_awal' AND '$tgl_akhir'
    ";

  if (!empty($filter_status)) {
    if ($filter_status == 'terlambat') {
      $query .= " AND t.status = 'dipinjam' AND t.tanggal_kembali < CURDATE()";
    } elseif ($filter_status == 'dipinjam') {
      // SAMA: Hanya ambil yang masih aman belum lewat
      $query .= " AND t.status = 'dipinjam' AND (t.tanggal_kembali >= CURDATE() OR t.tanggal_kembali IS NULL)";
    } else {
      $query .= " AND t.status = '$filter_status'";
    }
  }

  $query .= " ORDER BY t.tanggal_pinjam ASC";

  $result_all = mysqli_query($conn, $query);

  // Ubah result menjadi array
  $all_data = [];
  while ($row = mysqli_fetch_assoc($result_all)) {
    $all_data[] = $row;
  }

  // Potong array untuk tampilan Web
  $data = array_slice($all_data, $start, $limit);
}

// --- NAMA ADMIN ---
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
  <title>Laporan Transaksi Periode | Perpustakaan</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

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

    /* Menu Aktif (Laporan) */
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

    /* Card & Form */
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

    /* Elemen Print */
    .ttd-print {
      display: none;
    }

    .print-header {
      display: none;
    }

    @media print {
      body {
        background: white;
      }

      /* Sembunyikan elemen web */
      .no-print,
      .sidebar,
      .navbar-custom,
      button,
      .form-control,
      .form-select,
      a.btn,
      .pagination,
      .page-link {
        display: none !important;
      }

      /* Tampilkan elemen khusus print yang disembunyikan di web */
      .d-print-table-row {
        display: table-row !important;
      }

      .container-fluid {
        padding-top: 0 !important;
        margin-top: 0 !important;
      }

      main {
        margin-left: 0 !important;
        padding: 0 !important;
      }

      /* LOGIKA PAGINATION PDF (10 DATA PER HALAMAN) */
      tbody tr {
        page-break-inside: avoid;
      }

      /* Class khusus untuk memaksa page break setiap 10 baris */
      .page-break-after-row {
        page-break-after: always;
      }

      .table {
        margin-bottom: 0;
        width: 100%;
      }

      /* Header Laporan Print */
      .print-header {
        display: block !important;
        margin-bottom: 20px;
        text-align: center;
      }

      table,
      th,
      td {
        border: 1px solid black !important;
      }

      th,
      td {
        padding: 8px !important;
        font-size: 11px;
      }

      /* TTD & Footer Print */
      .ttd-print {
        display: block;
        float: right;
        margin-right: 20px;
        margin-top: 20px;
        font-size: 12px;
        text-align: center;
      }

      .card-custom {
        box-shadow: none;
        border: none;
      }
    }
  </style>
</head>

<body>

  <!-- ================= NAVBAR ================= -->
  <nav class="navbar navbar-custom fixed-top">
    <div class="container-fluid">
      <!-- Kiri: Logo & Title -->
      <a class="navbar-brand d-flex align-items-center text-primary fw-bold" href="#" style="color: var(--primary-blue) !important; text-decoration: none;">
        <div class="bg-white rounded p-2 shadow-sm d-flex align-items-center justify-content-center me-2" style="width: 42px; height: 42px;">
          <img src="../../asset/img/logo.png" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
        </div>
        <span class="fs-5">SIPERDI</span>
      </a>

      <!-- Kanan: Info Admin -->
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
              <a class="nav-link active" href="laporan.php">
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

        <!-- HEADER CETAK (Hanya Muncul Saat Print) -->
        <div class="print-header">
          <div class="row align-items-center">
            <div class="col-auto text-center">
              <img src="../../asset/img/logo.png" style="width: 90px; height: auto; margin-top: 10px;" alt="Logo">
            </div>
            <div class="col text-center" style="margin-left: -90px;">
              <h4 style="margin: 0; font-weight: bold; font-size: 16pt;">SIPERDI</h4>
              <h5 style="margin: 0; font-weight: normal; font-size: 14pt;">Sistem Informasi Perpustakaan Digital</h5>
            </div>
          </div>
          <hr style="border-top: 2px solid black; margin: 0;">
          <h3 style="text-align: center; margin-bottom: 5px; font-size: 14pt;">LAPORAN TRANSAKSI</h3>
          <p style="text-align: center; margin: 0; font-size: 11pt;">
            Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> s.d. <?= date('d/m/Y', strtotime($tgl_akhir)) ?><br>
            Status: <?= $status_label_print ?>
          </p>
        </div>

        <!-- JUDUL & AKSI -->
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
          <h2 class="fw-bold" style="color: var(--primary-blue);">Laporan Transaksi</h2>
          <?php if (!empty($all_data) && count($all_data) > 0): ?>
            <button onclick="window.print()" class="btn btn-primary">
              <i class="bi bi-printer-fill"></i> Cetak Laporan
            </button>
          <?php endif; ?>
        </div>

        <!-- FORM FILTER -->
        <div class="card card-custom p-3 mb-4 no-print">
          <form method="get">
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Tanggal Awal</label>
                <input type="date" name="tgl_awal" required value="<?= $tgl_awal ?>" class="form-control">
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Tanggal Akhir</label>
                <input type="date" name="tgl_akhir" required value="<?= $tgl_akhir ?>" class="form-control">
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Filter Status</label>
                <select name="status" class="form-select">
                  <option value="">-- Semua Status --</option>
                  <!-- DITAMBAHKAN: Pending Pinjam & Pending Kembali -->
                  <option value="pending_pinjam" <?= $filter_status == 'pending_pinjam' ? 'selected' : '' ?>>Pending Pinjam</option>
                  <option value="dipinjam" <?= $filter_status == 'dipinjam' ? 'selected' : '' ?>>Sedang Dipinjam</option>
                  <option value="terlambat" <?= $filter_status == 'terlambat' ? 'selected' : '' ?>>Terlambat</option>
                  <option value="pending_kembali" <?= $filter_status == 'pending_kembali' ? 'selected' : '' ?>>Pending Kembali</option>
                  <option value="pending_payment" <?= $filter_status == 'pending_payment' ? 'selected' : '' ?>>Menunggu Bayar</option>
                  <option value="kembali" <?= $filter_status == 'kembali' ? 'selected' : '' ?>>Selesai / Kembali</option>
                </select>
              </div>
              <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                  <i class="bi bi-funnel"></i> Tampilkan Data
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- TABEL LAPORAN -->
        <?php
        if (!empty($all_data) && count($all_data) > 0):
        ?>
          <div class="card card-custom border-0 overflow-hidden">
            <div class="table-responsive">
              <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 20%;">Judul Buku</th>
                    <th style="width: 10%;">Kode</th>
                    <th style="width: 15%;">Peminjam</th>
                    <th style="width: 15%;">Kategori</th>
                    <th style="width: 10%;">Tgl Pinjam</th>
                    <th style="width: 10%;">Tgl Kembali</th>
                    <th style="width: 5%;">Denda</th>
                    <th style="width: 10%;">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  // Loop SELURUH DATA ($all_data)
                  foreach ($all_data as $index => $t):

                    $current_no = $index + 1;
                    $is_visible_on_web = ($index >= $start && $index < ($start + $limit));

                    // ========== LOGIKA DENDA SAMA SEPERTI TRANSAKSI.PHP ==========
                    $display_status = $t['status'];
                    $is_late = false;
                    $denda_total = 0;
                    $denda_badge = '';

                    // Hitung Denda & Cek Terlambat (Untuk Tampilan Real-Time)
                    if ($display_status == 'dipinjam' && !empty($t['tanggal_kembali'])) {
                      $today_timestamp = time();
                      $due_timestamp = strtotime($t['tanggal_kembali'] . ' 23:59:59');
                      if ($today_timestamp > $due_timestamp) {
                        $is_late = true;
                        $selisih_detik = $today_timestamp - $due_timestamp;
                        $selisih_hari = ceil($selisih_detik / (60 * 60 * 24));
                        if ($selisih_hari < 1) $selisih_hari = 1;
                        $denda_total = $selisih_hari * $denda_per_hari;
                        $total_denda_semua += $denda_total;
                        $denda_badge = '<span class="badge bg-danger text-white ms-1">' . $selisih_hari . ' hari</span>';
                      }
                    }

                    // Jika status KEMBALI dan ada denda di database, tambahkan ke total
                    if ($display_status == 'kembali' && (int)$t['denda'] > 0) {
                      $total_denda_semua += (int)$t['denda'];
                    }

                    // Badge Status (Visual Tampilan)
                    // DITAMBAHKAN: Logic untuk pending_pinjam & pending_kembali
                    if ($is_late) {
                      $badgeClass = 'bg-danger text-white';
                      $status_label = "Terlambat";
                    } elseif ($display_status == 'pending_pinjam') {
                      $badgeClass = 'bg-warning text-dark';
                      $status_label = "Pending Pinjam";
                    } elseif ($display_status == 'pending_kembali') {
                      $badgeClass = 'bg-info text-dark';
                      $status_label = "Pending Kembali";
                    } elseif ($display_status == 'pending_payment') {
                      $badgeClass = 'bg-secondary text-white';
                      $status_label = "Menunggu Bayar";
                    } elseif ($display_status == 'kembali') {
                      $badgeClass = 'bg-success text-white';
                      $status_label = "Selesai";
                    } else {
                      $badgeClass = 'bg-primary text-white';
                      $status_label = "Dipinjam";
                    }

                    // Page Break Logic
                    $page_break_class = '';
                    if (($current_no % 10 === 0) && ($current_no < $total_data)) {
                      $page_break_class = 'page-break-after-row';
                    }

                    $row_style = '';
                    if (!$is_visible_on_web) {
                      $row_style = 'class="d-none d-print-table-row ' . $page_break_class . '"';
                    } else {
                      $row_style = 'class="' . $page_break_class . '"';
                    }
                  ?>
                    <tr <?= $row_style ?>>
                      <td class="text-center"><?= $current_no ?></td>
                      <td>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($t['judul']) ?></div>
                        <div class="small text-muted"><?= htmlspecialchars($t['penerbit']) ?></div>
                      </td>
                      <td class="text-center font-monospace"><?= $t['kode_spesifik'] ?? '-' ?></td>
                      <td><?= htmlspecialchars($t['nama_peminjam']) ?></td>
                      <td>
                        <span class="badge bg-light text-primary border"><?= htmlspecialchars($t['kategori']) ?></span>
                      </td>
                      <td class="text-center"><?= $t['tanggal_pinjam'] ?></td>
                      <td class="text-center">
                        <?= $t['tanggal_kembali'] ?? '-' ?>
                        <?= $denda_badge ?>
                      </td>
                      <td class="text-center">
                        <div class="text-danger fw-bold">
                          <?php
                          // SAMA PERSIS DENGAN TRANSAKSI.PHP
                          // Tampilkan denda: Jika sedang telat (hitungan real-time) atau Jika sudah selesai (ambil dari DB)
                          if ($is_late) {
                            echo 'Rp ' . number_format($denda_total);
                          } elseif ($display_status == 'kembali' && (int)$t['denda'] > 0) {
                            echo 'Rp ' . number_format($t['denda']);
                          } else {
                            echo '-';
                          }
                          ?>
                        </div>
                      </td>
                      <td class="text-center">
                        <span class="badge <?= $badgeClass ?>">
                          <?= $status_label ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
                <!-- FOOTER TABEL -->
                <tfoot>
                  <tr class="fw-bold bg-light">
                    <td colspan="7" class="text-end">Total Denda Periode Ini:</td>
                    <td class="text-center text-danger">Rp <?= number_format($total_denda_semua) ?></td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <!-- PAGINATION (WEB ONLY) -->
          <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-center mt-4 no-print">
              <nav aria-label="Page navigation">
                <ul class="pagination">
                  <?php
                  $query_params = http_build_query(['tgl_awal' => $tgl_awal, 'tgl_akhir' => $tgl_akhir, 'status' => $filter_status]);
                  ?>

                  <?php if ($page > 1): ?>
                    <li class="page-item">
                      <a class="page-link" href="?<?= $query_params ?>&page=<?= $page - 1 ?>">Previous</a>
                    </li>
                  <?php endif; ?>

                  <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                      <a class="page-link" href="?<?= $query_params ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                  <?php endfor; ?>

                  <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                      <a class="page-link" href="?<?= $query_params ?>&page=<?= $page + 1 ?>">Next</a>
                    </li>
                  <?php endif; ?>
                </ul>
              </nav>
            </div>
          <?php endif; ?>

          <!-- TANDA TANGAN (PRINT ONLY) -->
          <div class="ttd-print">
            <p><?= date('d/m/Y') ?></p>
            <p>Mengetahui,</p>
            <br><br><br>
            <p><u><?= htmlspecialchars($admin_name) ?></u></p>
            <p>Administrator</p>
          </div>

        <?php elseif ($tgl_awal && $tgl_akhir): ?>
          <div class="alert alert-warning text-center no-print">
            <i class="bi bi-exclamation-triangle-fill"></i> Tidak ada data transaksi pada periode dan status tersebut.
          </div>
        <?php endif; ?>

      </main>
    </div>
  </div>

  <!-- BOOTSTRAP 5 JS (OFFLINE) -->
  <script src="../../asset/js/bootstrap.bundle.min.js"></script>
</body>

</html>