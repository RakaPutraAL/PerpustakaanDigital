<?php
session_start();
include __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
  die("Akses ditolak");
}

// --- HITUNG BADGE NOTIFIKASI PENGAJUAN ---
 $count_pending_pinjam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status = 'pending_pinjam'"))['total'];
 $count_pending_kembali = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status = 'pending_kembali'"))['total'];
 $total_pending = $count_pending_pinjam + $count_pending_kembali;


// --- HITUNG BADGE NOTIFIKASI DENDA ---
 $count_denda_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status = 'pending_payment'"))['total'];


// --- KONFIGURASI DENDA ---
 $denda_per_hari = 1500; // Rp 1500 per hari

// --- PAGINATION & PARAMETERS ---
 $limit = 5;
 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
 $start = ($page > 1) ? ($page * $limit) - $limit : 0;

 $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
 $filter_status = isset($_GET['status']) ? $_GET['status'] : '';
 $filter_kategori = $_GET['kategori'] ?? '';

 $query_kategori = "SELECT DISTINCT kategori FROM detail_buku WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori ASC";
 $result_kategori = mysqli_query($conn, $query_kategori);

// --- QUERY BUILDING ---
 $query_count = "
    SELECT COUNT(*) as total 
    FROM transaksi
    JOIN buku ON transaksi.id_buku = buku.id
    LEFT JOIN detail_buku ON buku.id = detail_buku.id_buku
    WHERE 1=1";

if (!empty($search)) {
  $query_count .= " AND (buku.judul LIKE '%$search%' 
                        OR transaksi.nama_peminjam LIKE '%$search%'
                        OR buku.penerbit LIKE '%$search%'
                        OR detail_buku.kategori LIKE '%$search%'
                        OR transaksi.kode_spesifik LIKE '%$search%')";
}

if (!empty($filter_kategori)) {
  $query_count .= " AND detail_buku.kategori = '$filter_kategori'";
}

if (!empty($filter_status)) {
  if ($filter_status == 'terlambat') {
    $query_count .= " AND transaksi.status = 'dipinjam' AND transaksi.tanggal_kembali < CURDATE()";
  } elseif ($filter_status == 'dipinjam') {
    $query_count .= " AND transaksi.status = 'dipinjam' AND (transaksi.tanggal_kembali >= CURDATE() OR transaksi.tanggal_kembali IS NULL)";
  } else {
    $query_count .= " AND transaksi.status = '$filter_status'";
  }
}

 $result_count = mysqli_query($conn, $query_count);
 $row_count = mysqli_fetch_assoc($result_count);
 $total_data = $row_count['total'];
 $total_pages = ceil($total_data / $limit);

 $transaksi = mysqli_query($conn, "
    SELECT transaksi.*, buku.judul, detail_buku.gambar, buku.penerbit, detail_buku.kategori
    FROM transaksi
    JOIN buku ON transaksi.id_buku = buku.id
    LEFT JOIN detail_buku ON buku.id = detail_buku.id_buku
    WHERE 1=1
    " . (!empty($search) ? " AND (buku.judul LIKE '%$search%' OR transaksi.nama_peminjam LIKE '%$search%' OR buku.penerbit LIKE '%$search%' OR detail_buku.kategori LIKE '%$search%' OR transaksi.kode_spesifik LIKE '%$search%')" : "") . "
    " . (!empty($filter_kategori) ? " AND detail_buku.kategori = '$filter_kategori'" : "") . "
    " . (
  !empty($filter_status) ?
  (
    $filter_status == 'terlambat' ?
    " AND transaksi.status = 'dipinjam' AND transaksi.tanggal_kembali < CURDATE()" : (
      $filter_status == 'dipinjam' ?
      " AND transaksi.status = 'dipinjam' AND (transaksi.tanggal_kembali >= CURDATE() OR transaksi.tanggal_kembali IS NULL)" :
      " AND transaksi.status = '$filter_status'"
    )
  )
  : ""
) . "
    ORDER BY transaksi.id DESC
    LIMIT $start, $limit
");

 $buku = mysqli_query($conn, "SELECT * FROM buku WHERE stok > 0");
 $anggota = mysqli_query($conn, "SELECT * FROM users WHERE level = 'user' ORDER BY username ASC");

// --- TANGGAL OTOMATIS ---
 $tgl_hari_ini = date('Y-m-d');
 $tgl_kembali_otomatis = date('Y-m-d', strtotime('+7 days'));

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
  <title>Transaksi | Admin Perpustakaan</title>
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

    .badge-status {
      font-weight: 600;
      padding: 0.35em 0.65em;
      border-radius: 50rem;
      font-size: 0.75rem;
    }

    .badge-kategori {
      background-color: #e7f1ff;
      color: #0d6efd;
      border: 1px solid #cde4ff;
      font-size: 0.75rem;
    }

    .modal-content {
      border-radius: 12px;
      border: none;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    /* Styling untuk autocomplete suggestions */
    .suggestion-item {
      cursor: pointer;
      padding: 10px 15px;
      border: none;
      transition: background-color 0.2s;
    }

    .suggestion-item:hover {
      background-color: #f8f9fa;
    }

    .suggestion-item.active {
      background-color: #e7f1ff;
      color: var(--primary-blue);
    }

    .suggestion-item small {
      color: #6c757d;
    }

    .search-input-wrapper {
      position: relative;
    }

    /* Styling untuk gambar buku di suggestions */
    .book-cover-small {
      width: 40px;
      height: 56px;
      object-fit: cover;
      border-radius: 4px;
      flex-shrink: 0;
    }

    .book-cover-placeholder {
      width: 40px;
      height: 56px;
      background-color: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
  </style>
</head>

<body>

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
            <li class="nav-item"><a class="nav-link active" aria-current="page" href="transaksi.php"><i class="bi bi-arrow-left-right"></i> Transaksi</a></li>
            <li class="nav-item">
              <a class="nav-link" href="../denda/verifikasi_denda.php">
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

      <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4">
          <h2 class="fw-bold" style="color: var(--primary-blue);">Transaksi Peminjaman</h2>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-lg me-1"></i> Pinjam Buku
          </button>
        </div>

        <!-- ALERT MESSAGE -->
        <?php if (isset($_SESSION['message'])):
          $msg_type = isset($_SESSION['msg_type']) ? $_SESSION['msg_type'] : 'success';
          if ($msg_type == 'danger') {
            $bg_color = '#f8d7da';
            $text_color = '#842029';
            $icon = 'bi-exclamation-circle-fill';
            $title = 'Gagal!';
          } else {
            $bg_color = '#d1e7dd';
            $text_color = '#0f5132';
            $icon = 'bi-check-circle-fill';
            $title = 'Berhasil!';
          }
        ?>
          <div class="alert d-flex align-items-center border-0 shadow-sm mb-4" style="background-color: <?= $bg_color ?>; color: <?= $text_color ?>;">
            <i class="bi <?= $icon ?> me-2 fs-5"></i>
            <div>
              <strong><?= $title ?></strong> <?= $_SESSION['message'];
                                              unset($_SESSION['message']);
                                              unset($_SESSION['msg_type']); ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- FORM FILTER -->
        <div class="card card-custom p-3 mb-4">
          <form method="get">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">Cari (Kode, Judul, Peminjam, dll)</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                  <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control border-start-0 bg-light" placeholder="Ketik kata kunci...">
                </div>
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Kategori</label>
                <select name="kategori" class="form-select">
                  <option value="">-- Semua Kategori --</option>
                  <?php if (mysqli_num_rows($result_kategori) > 0) {
                    while ($kat = mysqli_fetch_assoc($result_kategori)) {
                      $selected = ($filter_kategori == $kat['kategori']) ? 'selected' : '';
                      echo "<option value='" . htmlspecialchars($kat['kategori']) . "' $selected>" . htmlspecialchars($kat['kategori']) . "</option>";
                    }
                  } ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Status Transaksi</label>
                <select name="status" class="form-select">
                  <option value="">-- Semua Status --</option>
                  <option value="pending_pinjam" <?= $filter_status == 'pending_pinjam' ? 'selected' : '' ?>>Pending Pinjam</option>
                  <option value="dipinjam" <?= $filter_status == 'dipinjam' ? 'selected' : '' ?>>Sedang Dipinjam</option>
                  <option value="terlambat" <?= $filter_status == 'terlambat' ? 'selected' : '' ?>>Terlambat</option>
                  <option value="pending_kembali" <?= $filter_status == 'pending_kembali' ? 'selected' : '' ?>>Pending Kembali</option>
                  <option value="pending_payment" <?= $filter_status == 'pending_payment' ? 'selected' : '' ?>>Menunggu Bayar</option>
                  <option value="kembali" <?= $filter_status == 'kembali' ? 'selected' : '' ?>>Kembali / Selesai</option>
                </select>
              </div>
              <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i></button>
                <a href="transaksi.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
              </div>
            </div>
          </form>
        </div>

        <!-- TABEL DATA -->
        <div class="card card-custom border-0 overflow-hidden">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th width="20%">Buku & Penerbit</th>
                  <th width="15%">Kode</th>
                  <th width="15%">Peminjam</th>
                  <th width="15%">Kategori</th>
                  <th width="10%">Tgl Pinjam</th>
                  <th width="10%">Tgl Kembali</th>
                  <th width="5%">Denda</th>
                  <th width="5%">Status</th>
                  <th width="5%" class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                if (mysqli_num_rows($transaksi) > 0) {
                  while ($t = mysqli_fetch_assoc($transaksi)) {
                    $display_status = $t['status'];
                    $is_late = false;
                    $denda_total = 0;
                    $denda_badge = '';

                    // Hitung Denda & Cek Terlambat (Untuk Tampilan Admin)
                    if ($display_status == 'dipinjam' && !empty($t['tanggal_kembali'])) {
                      $today_timestamp = time();
                      $due_timestamp = strtotime($t['tanggal_kembali'] . ' 23:59:59');
                      if ($today_timestamp > $due_timestamp) {
                        $is_late = true;
                        $selisih_detik = $today_timestamp - $due_timestamp;
                        $selisih_hari = ceil($selisih_detik / (60 * 60 * 24));
                        if ($selisih_hari < 1) $selisih_hari = 1;
                        $denda_total = $selisih_hari * $denda_per_hari;
                        $denda_badge = '<span class="badge bg-danger text-white ms-1">' . $selisih_hari . ' hari</span>';
                      }
                    }

                    // Tentukan Badge Status
                    if ($display_status == 'pending_pinjam') {
                      $badgeClass = 'bg-warning text-dark';
                      $status_label = "Pending Pinjam";
                    } elseif ($display_status == 'pending_kembali') {
                      $badgeClass = 'bg-info text-dark';
                      $status_label = "Pending Kembali";
                    } elseif ($is_late) {
                      $badgeClass = 'bg-danger text-white';
                      $status_label = "Terlambat";
                    } elseif ($display_status == 'pending_payment') {
                      $badgeClass = 'bg-warning text-dark';
                      $status_label = "Menunggu Bayar";
                    } elseif ($display_status == 'kembali') {
                      $badgeClass = 'bg-success text-white';
                      $status_label = "Selesai";
                    } else {
                      $badgeClass = 'bg-primary text-white';
                      $status_label = "Dipinjam";
                    }

                    $tampil_kode = $t['kode_spesifik'] ?? '-';
                    $penerbit = $t['penerbit'] ?? '-';
                    $kategori = $t['kategori'] ?: '-';
                ?>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center">
                          <div class="bg-light rounded border d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 56px; flex-shrink: 0;">
                            <?php if (!empty($t['gambar'])): ?>
                              <img src="../../uploads/<?= $t['gambar'] ?>" class="img-fluid" style="width: 100%; height: 100%; object-fit: cover;" alt="<?= htmlspecialchars($t['judul']) ?>">
                            <?php else: ?>
                              <i class="bi bi-book text-muted" style="font-size: 1.5rem;"></i>
                            <?php endif; ?>
                          </div>
                          <div>
                            <div class="fw-bold text-dark"><?= $t['judul'] ?></div>
                            <div class="small text-muted"><i class="bi bi-building"></i> <?= $penerbit ?></div>
                          </div>
                        </div>
                      </td>
                      <td><span class="font-monospace text-primary fw-bold"><?= $tampil_kode ?></span></td>
                      <td class="text-muted fw-medium"><?= $t['nama_peminjam'] ?></td>
                      <td><span class="badge badge-kategori"><?= $kategori ?></span></td>
                      <td class="text-muted"><?= $t['tanggal_pinjam'] ?></td>
                      <td class="text-muted">
                        <?= $t['tanggal_kembali'] ?? '-' ?>
                        <?= $denda_badge ?>
                      </td>
                      <td>
                        <div class="text-danger fw-bold">
                          <?php
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
                      <td>
                        <span class="badge badge-status <?= $badgeClass ?>"><?= $status_label ?></span>
                      </td>

                      <!-- KOLOM AKSI - HANYA EDIT DAN HAPUS -->
                      <td class="text-center">
                        <div class="btn-group" role="group">
                          <!-- Tombol Edit -->
                          <button onclick="openModalEdit(<?= $t['id'] ?>, '<?= $t['id_buku'] ?>', '<?= addslashes($t['nama_peminjam']) ?>', '<?= $t['tanggal_pinjam'] ?>', '<?= $t['status'] ?>', '<?= $t['tanggal_kembali'] ?? '' ?>', '<?= $t['kode_spesifik'] ?>')" class="btn btn-sm btn-primary" title="Edit">
                            <i class="bi bi-pencil"></i>
                          </button>

                          <!-- Tombol Hapus -->
                          <a href="hapus_transaksi.php?id=<?= $t['id'] ?>" onclick="return confirm('Hapus transaksi ini?')" class="btn btn-sm btn-danger" title="Hapus">
                            <i class="bi bi-trash"></i>
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php }
                } else { ?>
                  <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                      <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                      Data tidak ditemukan.
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>

          <!-- PAGINATION -->
          <?php if ($total_pages > 1): ?>
            <div class="card-footer bg-white border-top py-3">
              <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small">
                  Menampilkan <strong><?= $start + 1 ?></strong> sampai <strong><?= min($start + $limit, $total_data) ?></strong> dari <strong><?= $total_data ?></strong> data
                </div>
                <nav aria-label="Page navigation">
                  <ul class="pagination pagination-sm mb-0">
                    <?php
                    $search_link = "search=$search&status=$filter_status&kategori=$filter_kategori";
                    ?>
                    <?php if ($page > 1): ?>
                      <li class="page-item"><a class="page-link" href="?<?= $search_link ?>&page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a></li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                      <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= $search_link ?>&page=<?= $i ?>"><?= $i ?></a>
                      </li>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                      <li class="page-item"><a class="page-link" href="?<?= $search_link ?>&page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a></li>
                    <?php endif; ?>
                  </ul>
                </nav>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </main>
    </div>
  </div>

  <!-- MODAL TAMBAH -->
  <div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title fw-bold">Pinjam Buku Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Alert Info Kuota -->
          <div id="alertKuota" class="alert alert-warning border-0 d-none mb-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <span id="alertKuotaText"></span>
          </div>

          <!-- Alert Buku Sama -->
          <div id="alertBukuSama" class="alert alert-danger border-0 d-none mb-3">
            <i class="bi bi-x-circle-fill me-2"></i>
            User ini sedang meminjam buku yang sama dan belum dikembalikan!
          </div>

          <form action="simpan_transaksi.php" method="POST" id="formTambah">
            <!-- SEARCH INPUT NAMA PEMINJAM -->
            <div class="mb-3 search-input-wrapper">
              <label class="form-label">Nama Peminjam</label>
              <input type="text"
                id="tambah_nama_peminjam"
                class="form-control"
                placeholder="Ketik untuk mencari anggota..."
                autocomplete="off">
              <input type="hidden" name="nama_peminjam" id="tambah_nama_peminjam_hidden">
              <div id="anggota-suggestions" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1050; max-height: 200px; overflow-y: auto; display: none;"></div>
            </div>

            <!-- SEARCH INPUT PILIH BUKU -->
            <div class="mb-3 search-input-wrapper">
              <label class="form-label">Pilih Buku</label>
              <input type="text"
                id="tambah_buku_search"
                class="form-control"
                placeholder="Ketik untuk mencari buku..."
                autocomplete="off">
              <input type="hidden" name="id_buku" id="tambah_id_buku">
              <div id="buku-suggestions" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1050; max-height: 250px; overflow-y: auto; display: none;"></div>
            </div>

            <div class="mb-3">
              <label class="form-label">Pilih Kode Buku</label>
              <select name="kode_spesifik" id="tambah_kode_spesifik" class="form-select" required>
                <option value="">-- Pilih Kode --</option>
              </select>
              <div class="form-text">Hanya kode buku yang tersedia (tidak sedang dipinjam/pending) yang muncul.</div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label">Tgl Pinjam</label>
                <input type="date" name="tanggal_pinjam" id="tambah_tanggal_pinjam" class="form-control" value="<?= $tgl_hari_ini ?>" readonly style="background-color: #e9ecef;">
              </div>
              <div class="col-6">
                <label class="form-label">Tgl Kembali (7 Hari)</label>
                <input type="date" name="tanggal_kembali" id="tambah_tanggal_kembali" class="form-control" value="<?= $tgl_kembali_otomatis ?>" readonly style="background-color: #e9ecef;">
              </div>
            </div>

            <div class="text-end mt-4">
              <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Batal</button>
              <button type="submit" name="pinjam" id="btnSubmitTambah" class="btn btn-primary">Simpan</button>
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
          <h5 class="modal-title fw-bold">Edit Transaksi</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="formEdit" action="update_transaksi.php" method="POST">
            <input type="hidden" name="id" id="edit_id">
            <div class="mb-3">
              <label class="form-label">Ubah Buku</label>
              <select name="id_buku" id="edit_id_buku" class="form-select" required>
                <option value="">-- Pilih Buku --</option>
                <?php mysqli_data_seek($buku, 0);
                while ($b = mysqli_fetch_assoc($buku)) { ?>
                  <option value="<?= $b['id'] ?>"><?= $b['judul'] ?> (Stok: <?= $b['stok'] ?>)</option>
                <?php } ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Kode Buku</label>
              <select name="kode_spesifik" id="edit_kode_spesifik" class="form-select" required>
                <option value="">-- Pilih Kode --</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Nama Peminjam</label>
              <select name="nama_peminjam" id="edit_nama_peminjam" class="form-select" required>
                <option value="">-- Pilih Anggota --</option>
                <?php
                mysqli_data_seek($anggota, 0);
                while ($a = mysqli_fetch_assoc($anggota)) { ?>
                  <option value="<?= $a['username'] ?>"><?= $a['username'] ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="row g-3 mb-3">
              <div class="col-6">
                <label class="form-label">Tgl Pinjam</label>
                <input type="date" name="tanggal_pinjam" id="edit_tanggal_pinjam" class="form-control" required>
              </div>
              <div class="col-6">
                <label class="form-label">Tgl Kembali</label>
                <input type="date" name="tanggal_kembali" id="edit_tanggal_kembali" class="form-control" required>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Status</label>
              <select name="status" id="edit_status" class="form-select" required>
                <option value="pending_pinjam">Pending Pinjam</option>
                <option value="dipinjam">Dipinjam</option>
                <option value="pending_kembali">Pending Kembali</option>
                <option value="kembali">Kembali</option>
              </select>
            </div>
            <div class="text-end mt-4">
              <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Batal</button>
              <button type="submit" name="update" class="btn btn-primary">Update</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="../../asset/js/bootstrap.bundle.min.js"></script>

  <script>
    // Data Buku & Kode
    const dataBuku = <?php
                      mysqli_data_seek($buku, 0);
                      $arrBuku = [];
                      while ($b = mysqli_fetch_assoc($buku)) {
                        $q_det = mysqli_query($conn, "SELECT kode_buku, gambar FROM detail_buku WHERE id_buku = " . $b['id']);
                        $det = mysqli_fetch_assoc($q_det);
                        $b['prefix'] = $det['kode_buku'] ?? '';
                        $b['gambar'] = $det['gambar'] ?? '';
                        $arrBuku[] = $b;
                      }
                      echo json_encode($arrBuku);
                      ?>;

    const dataAnggota = <?php
                        mysqli_data_seek($anggota, 0);
                        $arrAnggota = [];
                        while ($a = mysqli_fetch_assoc($anggota)) {
                          $arrAnggota[] = $a['username'];
                        }
                        echo json_encode($arrAnggota);
                        ?>;

    // ============================================
    // PERBAIKAN LOGIKA KODE DIPINJAM (GABUNG SEMUA STATUS SIBUK)
    // ============================================
    const kodeDipinjam = <?php
                          // LOGIKA: Kode dianggap tersedia HANYA jika statusnya 'kembali'.
                          // Jadi, kita ambil semua kode yang statusnya BUKAN 'kembali'.
                          // Termasuk: dipinjam, pending_pinjam, pending_kembali, pending_payment
                          $q_kode_pinjam = mysqli_query($conn, "SELECT kode_spesifik FROM transaksi WHERE status IN ('dipinjam', 'pending_pinjam', 'pending_kembali', 'pending_payment')");
                          $arrKodePinjam = [];
                          while ($k = mysqli_fetch_assoc($q_kode_pinjam)) {
                            if(!empty($k['kode_spesifik'])){
                                $arrKodePinjam[] = $k['kode_spesifik'];
                            }
                          }
                          echo json_encode($arrKodePinjam);
                          ?>;

    // Data Transaksi User (untuk cek kuota dan buku sama)
    const userTransaksi = <?php
                          $q_user_trans = mysqli_query($conn, "SELECT nama_peminjam, id_buku FROM transaksi WHERE status IN ('dipinjam', 'pending_pinjam', 'pending_kembali', 'pending_payment')");
                          $arrUserTrans = [];
                          while ($ut = mysqli_fetch_assoc($q_user_trans)) {
                            $user = $ut['nama_peminjam'];
                            if (!isset($arrUserTrans[$user])) {
                              $arrUserTrans[$user] = [];
                            }
                            $arrUserTrans[$user][] = (int)$ut['id_buku'];
                          }
                          echo json_encode($arrUserTrans);
                          ?>;

    const MAX_BORROW_LIMIT = 3;

    // ============================================
    // AUTOCOMPLETE UNTUK NAMA PEMINJAM
    // ============================================
    const inputAnggota = document.getElementById('tambah_nama_peminjam');
    const hiddenAnggota = document.getElementById('tambah_nama_peminjam_hidden');
    const suggestionsAnggota = document.getElementById('anggota-suggestions');

    inputAnggota.addEventListener('input', function() {
      const query = this.value.toLowerCase().trim();
      suggestionsAnggota.innerHTML = '';

      if (query.length === 0) {
        suggestionsAnggota.style.display = 'none';
        hiddenAnggota.value = '';
        return;
      }

      const filtered = dataAnggota.filter(nama => nama.toLowerCase().includes(query));

      if (filtered.length === 0) {
        suggestionsAnggota.style.display = 'none';
        return;
      }

      filtered.forEach(nama => {
        const item = document.createElement('a');
        item.href = '#';
        item.className = 'list-group-item list-group-item-action suggestion-item';
        item.innerHTML = `<i class="bi bi-person-circle me-2"></i>${nama}`;
        item.addEventListener('click', function(e) {
          e.preventDefault();
          inputAnggota.value = nama;
          hiddenAnggota.value = nama;
          suggestionsAnggota.style.display = 'none';
          cekKuotaDanBuku();
        });
        suggestionsAnggota.appendChild(item);
      });

      suggestionsAnggota.style.display = 'block';
    });

    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
      if (!inputAnggota.contains(e.target) && !suggestionsAnggota.contains(e.target)) {
        suggestionsAnggota.style.display = 'none';
      }
    });

    // ============================================
    // AUTOCOMPLETE UNTUK PILIH BUKU
    // ============================================
    const inputBuku = document.getElementById('tambah_buku_search');
    const hiddenBuku = document.getElementById('tambah_id_buku');
    const suggestionsBuku = document.getElementById('buku-suggestions');

    inputBuku.addEventListener('input', function() {
      const query = this.value.toLowerCase().trim();
      suggestionsBuku.innerHTML = '';

      if (query.length === 0) {
        suggestionsBuku.style.display = 'none';
        hiddenBuku.value = '';
        document.getElementById('tambah_kode_spesifik').innerHTML = '<option value="">-- Pilih Kode --</option>';
        return;
      }

      const filtered = dataBuku.filter(buku =>
        buku.judul.toLowerCase().includes(query) ||
        buku.penerbit.toLowerCase().includes(query)
      );

      if (filtered.length === 0) {
        suggestionsBuku.style.display = 'none';
        return;
      }

      filtered.forEach(buku => {
        const item = document.createElement('a');
        item.href = '#';
        item.className = 'list-group-item list-group-item-action suggestion-item';

        // Tampilkan gambar sampul buku jika ada
        let coverHTML = '';
        if (buku.gambar && buku.gambar !== '') {
          coverHTML = `<img src="../../uploads/${buku.gambar}" class="book-cover-small me-2" alt="${buku.judul}">`;
        } else {
          coverHTML = `<div class="book-cover-placeholder me-2"><i class="bi bi-book text-muted" style="font-size: 1.5rem;"></i></div>`;
        }

        item.innerHTML = `
          <div class="d-flex align-items-center">
            ${coverHTML}
            <div>
              <div class="fw-bold">${buku.judul}</div>
              <small class="text-muted">${buku.penerbit || '-'} | Stok: ${buku.stok}</small>
            </div>
          </div>
        `;

        item.addEventListener('click', function(e) {
          e.preventDefault();
          inputBuku.value = buku.judul;
          hiddenBuku.value = buku.id;
          suggestionsBuku.style.display = 'none';
          populateKode(document.getElementById('tambah_kode_spesifik'), buku.id, null, null);
          cekKuotaDanBuku();
        });
        suggestionsBuku.appendChild(item);
      });

      suggestionsBuku.style.display = 'block';
    });

    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
      if (!inputBuku.contains(e.target) && !suggestionsBuku.contains(e.target)) {
        suggestionsBuku.style.display = 'none';
      }
    });

    // FUNGSI CEK KUOTA DAN BUKU SAMA
    function cekKuotaDanBuku() {
      const username = hiddenAnggota.value;
      const idBuku = hiddenBuku.value;
      const alertKuota = document.getElementById('alertKuota');
      const alertBukuSama = document.getElementById('alertBukuSama');
      const btnSubmit = document.getElementById('btnSubmitTambah');

      // Reset
      alertKuota.classList.add('d-none');
      alertBukuSama.classList.add('d-none');
      btnSubmit.disabled = false;

      if (!username || !idBuku) return;

      const userBorrows = userTransaksi[username] || [];
      const currentCount = userBorrows.length;

      // Cek Kuota
      if (currentCount >= MAX_BORROW_LIMIT) {
        alertKuota.classList.remove('d-none');
        alertKuota.classList.remove('alert-info');
        alertKuota.classList.add('alert-warning');
        document.getElementById('alertKuotaText').innerText =
          `User "${username}" sudah meminjam ${currentCount}/${MAX_BORROW_LIMIT} buku. Tidak bisa menambah peminjaman lagi!`;
        btnSubmit.disabled = true;
        return;
      }

      // Cek Buku Sama
      if (userBorrows.includes(parseInt(idBuku))) {
        alertBukuSama.classList.remove('d-none');
        btnSubmit.disabled = true;
        return;
      }

      // Jika lolos
      alertKuota.classList.remove('d-none');
      alertKuota.classList.remove('alert-warning');
      alertKuota.classList.add('alert-info');
      document.getElementById('alertKuotaText').innerText =
        `User "${username}" saat ini meminjam ${currentCount}/${MAX_BORROW_LIMIT} buku. Masih bisa meminjam.`;
    }

    // FUNGSI POPULATE KODE DENGAN EXCLUDE PARAMETER
    function populateKode(selectElement, idBuku, currentKode = null, excludeKode = null) {
      const buku = dataBuku.find(b => b.id == idBuku);
      selectElement.innerHTML = '<option value="">-- Pilih Kode --</option>';

      if (buku) {
        const prefix = buku.prefix;
        const stok = parseInt(buku.stok);

        for (let i = 1; i <= stok; i++) {
          let num = i.toString().padStart(3, '0');
          let kodeFull = prefix + num;

          // Cek apakah kode sedang "dipinjam" (termasuk pending payment/pending kembali)
          // Array 'kodeDipinjam' sudah berisi semua status sibuk dari PHP
          let isDipinjam = kodeDipinjam.includes(kodeFull);

          // LOGIKA EXCLUDE:
          // Jika kode sedang dipinjam, TAPI kode tersebut adalah kode yang sedang diedit (excludeKode),
          // Maka anggap kode itu tersedia (untuk transaksi ini saja).
          if (!isDipinjam || kodeFull === excludeKode) {
            let opt = document.createElement('option');
            opt.value = kodeFull;
            opt.innerText = kodeFull;

            if (currentKode && currentKode === kodeFull) {
              opt.selected = true;
            }

            selectElement.appendChild(opt);
          }
        }
      }
    }

    // Event listener untuk edit transaksi
    let originalKodeEdit = null;

    document.getElementById('edit_id_buku').addEventListener('change', function() {
      populateKode(document.getElementById('edit_kode_spesifik'), this.value, null, originalKodeEdit);
    });

    // FUNGSI OPEN MODAL EDIT YANG DIPERBAIKI
    function openModalEdit(id, id_buku, nama, tgl, status, tgl_kembali, kode) {
      originalKodeEdit = kode; // Simpan kode asli untuk pengecualian saat edit

      document.getElementById('edit_id').value = id;

      const selectBukuEdit = document.getElementById('edit_id_buku');
      selectBukuEdit.value = id_buku;

      const selectKodeEdit = document.getElementById('edit_kode_spesifik');

      // Populate kode, kirim 'kode' sebagai currentKode DAN excludeKode
      // agar kode yang sedang dipakai transaksi ini tetap muncul di list
      populateKode(selectKodeEdit, id_buku, kode, kode);

      var selectNama = document.getElementById('edit_nama_peminjam');
      selectNama.value = nama;

      document.getElementById('edit_tanggal_pinjam').value = tgl;
      document.getElementById('edit_status').value = status;
      document.getElementById('edit_tanggal_kembali').value = tgl_kembali;

      var myModal = new bootstrap.Modal(document.getElementById('modalEdit'));
      myModal.show();
    }

    // Reset modal tambah saat ditutup
    document.getElementById('modalTambah').addEventListener('hidden.bs.modal', function() {
      document.getElementById('formTambah').reset();
      inputAnggota.value = '';
      hiddenAnggota.value = '';
      inputBuku.value = '';
      hiddenBuku.value = '';
      suggestionsAnggota.style.display = 'none';
      suggestionsBuku.style.display = 'none';
      document.getElementById('alertKuota').classList.add('d-none');
      document.getElementById('alertBukuSama').classList.add('d-none');
      document.getElementById('tambah_kode_spesifik').innerHTML = '<option value="">-- Pilih Kode --</option>';
    });
  </script>
</body>

</html>