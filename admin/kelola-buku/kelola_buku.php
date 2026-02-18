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
$limit = 4; // 4 Buku per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

// Ambil parameter pencarian & filter
$search_all = $_GET['search_all'] ?? ''; // Gabungan Judul, Penulis, Penerbit, Kode Buku
$filter_kategori = $_GET['filter_kategori'] ?? '';
$filter_tahun = $_GET['filter_tahun'] ?? '';

// 1. AMBIL DATA KATEGORI UNIK UNTUK DROPDOWN
$query_kategori = "SELECT DISTINCT kategori FROM detail_buku WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori ASC";
$result_kategori = mysqli_query($conn, $query_kategori);


// 2. Query COUNT Total Data (untuk Pagination)
$query_count = "SELECT COUNT(*) as total FROM buku 
                LEFT JOIN detail_buku ON buku.id = detail_buku.id_buku
                WHERE (buku.judul LIKE '%$search_all%' 
                       OR buku.penulis LIKE '%$search_all%' 
                       OR buku.penerbit LIKE '%$search_all%'
                       OR detail_buku.kode_buku LIKE '%$search_all%')
                  AND (detail_buku.kategori LIKE '%$filter_kategori%')
                  " . ($filter_tahun ? "AND buku.tahun = '$filter_tahun'" : "");

$result_count = mysqli_query($conn, $query_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_data = $row_count['total'];
$total_pages = ceil($total_data / $limit);

// 3. Query buku JOIN detail_buku dengan LIMIT
$data = mysqli_query($conn, "
  SELECT buku.*, detail_buku.kode_buku as prefix_kode, detail_buku.gambar, detail_buku.kategori, detail_buku.deskripsi
  FROM buku
  LEFT JOIN detail_buku ON buku.id = detail_buku.id_buku
  WHERE (buku.judul LIKE '%$search_all%' 
         OR buku.penulis LIKE '%$search_all%' 
         OR buku.penerbit LIKE '%$search_all%'
         OR detail_buku.kode_buku LIKE '%$search_all%')
    AND (detail_buku.kategori LIKE '%$filter_kategori%')
    " . ($filter_tahun ? "AND buku.tahun = '$filter_tahun'" : "") . "
  ORDER BY buku.id DESC
  LIMIT $start, $limit
");

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
  <title>Kelola Buku | Admin Perpustakaan</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- BOOTSTRAP 5 CSS (OFFLINE) -->
  <link href="../../asset/css/bootstrap.min.css" rel="stylesheet">

  <!-- BOOTSTRAP ICONS (OFFLINE) -->
  <link rel="stylesheet" href="../../asset/font/bootstrap-icons.css">

  <style>
    /* --- Kustomisasi Tema Biru (Sama dengan Dashboard) --- */
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

    /* Menu Aktif (Kelola Buku) */
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
      transition: transform 0.2s;
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

    /* Pagination */
    .page-link {
      color: var(--primary-blue);
    }

    .page-item.active .page-link {
      background-color: var(--primary-blue);
      border-color: var(--primary-blue);
    }

    /* Table Customization */
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

    /* Badge Kategori */
    .badge-kategori {
      background-color: #e7f1ff;
      color: #0d6efd;
      border: 1px solid #cde4ff;
      font-size: 0.75rem;
      padding: 0.35em 0.65em;
      border-radius: 50rem;
    }

    /* Animasi Modal */
    .modal-content {
      border-radius: 12px;
      border: none;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    /* Alert Styling */
    .alert {
      border-radius: 10px;
      border: none;
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
              <a class="nav-link" href="dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link active" aria-current="page" href="kelola_buku.php">
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

        <!-- Judul Halaman & Tombol Aksi -->
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4">
          <h2 class="fw-bold" style="color: var(--primary-blue);">Kelola Data Buku</h2>
          <div class="btn-group">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalImport">
              <i class="bi bi-upload me-1"></i> Import Excel
            </button>
            <a href="ekspor_buku.php" class="btn btn-info text-white">
              <i class="bi bi-download me-1"></i> Ekspor Excel
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
              <i class="bi bi-plus-lg me-1"></i> Tambah Buku
            </button>
          </div>
        </div>

        <!-- ALERT SUCCESS/ERROR -->
        <?php if (isset($_SESSION['success'])): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- FORM PENCARIAN & FILTER -->
        <div class="card card-custom p-3 mb-4">
          <form method="get">
            <div class="row g-3">
              <div class="col-md-5">
                <label class="form-label small fw-bold text-muted">Cari Buku (Judul, Penulis, Penerbit, Kode)</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                  <input type="text" name="search_all" value="<?= htmlspecialchars($search_all) ?>"
                    class="form-control border-start-0 bg-light" placeholder="Ketik kode, judul, atau penulis...">
                </div>
              </div>

              <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Kategori</label>
                <select name="filter_kategori" class="form-select">
                  <option value="">-- Semua Kategori --</option>
                  <?php
                  if (mysqli_num_rows($result_kategori) > 0) {
                    while ($kat = mysqli_fetch_assoc($result_kategori)) {
                      $selected = ($filter_kategori == $kat['kategori']) ? 'selected' : '';
                      echo "<option value='" . htmlspecialchars($kat['kategori']) . "' $selected>" . htmlspecialchars($kat['kategori']) . "</option>";
                    }
                  }
                  ?>
                </select>
              </div>

              <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Tahun</label>
                <input type="number" name="filter_tahun" value="<?= htmlspecialchars($filter_tahun) ?>" class="form-control" placeholder="Tahun">
              </div>

              <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                  <i class="bi bi-funnel"></i>
                </button>
                <a href="kelola_buku.php" class="btn btn-outline-secondary">
                  <i class="bi bi-arrow-counterclockwise"></i>
                </a>
              </div>
            </div>
          </form>
        </div>

        <!-- TABLE CONTAINER -->
        <div class="card card-custom border-0 overflow-hidden">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th width="5%">No</th>
                  <th width="8%">Gambar</th>
                  <th width="22%">Informasi Buku</th>
                  <th width="10%">Kategori</th>
                  <th width="12%">Penulis</th>
                  <th width="12%">Penerbit</th>
                  <th width="8%" class="text-center">Tahun</th>
                  <th width="5%" class="text-center">Stok</th>
                  <th width="10%" class="text-center">Status Kode</th>
                  <th width="8%" class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $no = $start + 1;
                if (mysqli_num_rows($data) > 0) {
                  while ($b = mysqli_fetch_assoc($data)) { ?>
                    <tr>
                      <td class="text-muted"><?= $no++ ?></td>

                      <td>
                        <div class="bg-light border rounded d-flex align-items-center justify-content-center" style="width: 60px; height: 80px;">
                          <?php if (!empty($b['gambar'])): ?>
                            <img src="../../uploads/<?= $b['gambar'] ?>" alt="<?= $b['judul'] ?>" class="img-fluid" style="width: 100%; height: 100%; object-fit: cover;">
                          <?php else: ?>
                            <i class="bi bi-book text-muted fs-4"></i>
                          <?php endif; ?>
                        </div>
                      </td>

                      <td>
                        <h6 class="fw-bold mb-1 text-dark"><?= $b['judul'] ?></h6>
                        <p class="text-muted small text-truncate mb-0" style="max-width: 200px;" title="<?= $b['deskripsi'] ?>">
                          <?= $b['deskripsi'] ?: '-' ?>
                        </p>
                      </td>

                      <td>
                        <span class="badge badge-kategori">
                          <?= $b['kategori'] ?: 'Umum' ?>
                        </span>
                      </td>

                      <td><?= $b['penulis'] ?></td>
                      <td><?= $b['penerbit'] ?></td>

                      <td class="text-center">
                        <span class="badge bg-light text-dark border">
                          <?= $b['tahun'] ?>
                        </span>
                      </td>

                      <td class="text-center fw-bold"><?= $b['stok'] ?></td>

                      <td class="text-center">
                        <button onclick="tampilKode(<?= $b['id'] ?>, '<?= htmlspecialchars($b['judul'], ENT_QUOTES) ?>', <?= $b['stok'] ?>)" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalKode">
                          <i class="bi bi-eye"></i> Lihat
                        </button>
                      </td>

                      <td class="text-center">
                        <div class="btn-group" role="group">
                          <button onclick="openModalEdit(this)" type="button" class="btn btn-sm btn-primary"
                            data-id="<?= $b['id'] ?>"
                            data-judul="<?= $b['judul'] ? htmlspecialchars($b['judul'], ENT_QUOTES) : '' ?>"
                            data-penulis="<?= $b['penulis'] ? htmlspecialchars($b['penulis'], ENT_QUOTES) : '' ?>"
                            data-penerbit="<?= $b['penerbit'] ? htmlspecialchars($b['penerbit'], ENT_QUOTES) : '' ?>"
                            data-tahun="<?= $b['tahun'] ?>"
                            data-stok="<?= $b['stok'] ?>"
                            data-deskripsi="<?= $b['deskripsi'] ? htmlspecialchars($b['deskripsi'], ENT_QUOTES) : '' ?>"
                            data-kategori="<?= $b['kategori'] ? htmlspecialchars($b['kategori'], ENT_QUOTES) : '' ?>"
                            data-prefix="<?= $b['prefix_kode'] ? htmlspecialchars($b['prefix_kode'], ENT_QUOTES) : '' ?>"
                            data-gambar="<?= $b['gambar'] ?>"
                            data-bs-toggle="modal" data-bs-target="#modalEdit">
                            <i class="bi bi-pencil-square"></i> Edit
                          </button>
                          <a href="simpan_buku.php?hapus=<?= $b['id'] ?>" onclick="return confirm('Yakin ingin menghapus buku <?= htmlspecialchars($b['judul'], ENT_QUOTES) ?>?')" class="btn btn-sm btn-danger">
                            <i class="bi bi-trash"></i>
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php }
                } else { ?>
                  <tr>
                    <td colspan="11" class="text-center py-5 text-muted">
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
                    $search_link = "search_all=$search_all&filter_kategori=$filter_kategori&filter_tahun=$filter_tahun";
                    ?>

                    <?php if ($page > 1): ?>
                      <li class="page-item">
                        <a class="page-link" href="?<?= $search_link ?>&page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a>
                      </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                      <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= $search_link ?>&page=<?= $i ?>"><?= $i ?></a>
                      </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                      <li class="page-item">
                        <a class="page-link" href="?<?= $search_link ?>&page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a>
                      </li>
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

  <!-- ================= MODAL IMPORT ================= -->
  <div class="modal fade" id="modalImport" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title fw-bold">Import Data Buku dari Excel</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Perhatian:</strong> Pastikan format Excel sesuai dengan template.
          </div>

          <div class="mb-3">
            <a href="template_import.php" class="btn btn-sm btn-outline-primary w-100">
              <i class="bi bi-download me-2"></i> Download Template Excel
            </a>
          </div>

          <form action="impor_buku.php" method="POST" enctype="multipart/form-data">
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

  <!-- ================= MODAL TAMBAH ================= -->
  <div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title fw-bold">Tambah Buku Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form action="simpan_buku.php" method="POST" enctype="multipart/form-data">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Judul Buku</label>
                <input type="text" name="judul" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Penulis</label>
                <input type="text" name="penulis" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Penerbit</label>
                <input type="text" name="penerbit" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Tahun</label>
                <input type="number" name="tahun" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Stok</label>
                <input type="number" name="stok" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Kategori</label>
                <input type="text" name="kategori" class="form-control" placeholder="Tulis kategori..." required>
              </div>
              <div class="col-12">
                <label class="form-label">Prefix Kode Buku</label>
                <input type="text" name="prefix_kode" class="form-control" placeholder="Misal: SJ-" required>
                <div class="form-text">Akan otomatis menjadi SJ-001, SJ-002, dst.</div>
              </div>
              <div class="col-12">
                <label class="form-label">Deskripsi Buku</label>
                <textarea name="deskripsi" class="form-control" rows="3" placeholder="Sinopsis singkat buku..."></textarea>
              </div>
              <div class="col-12">
                <label class="form-label">Gambar Sampul</label>
                <input type="file" name="gambar" class="form-control" accept="image/*">
              </div>
            </div>
            <div class="mt-4 text-end">
              <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Batal</button>
              <button type="submit" name="tambah" class="btn btn-primary">Simpan Buku</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= MODAL EDIT ================= -->
  <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title fw-bold">Edit Data Buku</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form action="update_buku.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" id="edit_id">

            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label">Judul Buku</label>
                <input type="text" name="judul" id="edit_judul" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Gambar Saat Ini</label>
                <div class="d-flex align-items-center gap-2">
                  <img id="edit_preview_gambar" src="" class="rounded border bg-light" style="width: 40px; height: 55px; object-fit: cover; display: none;">
                  <input type="file" name="gambar" class="form-control form-control-sm">
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Penulis</label>
                <input type="text" name="penulis" id="edit_penulis" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Penerbit</label>
                <input type="text" name="penerbit" id="edit_penerbit" class="form-control" required>
              </div>

              <div class="col-md-4">
                <label class="form-label">Tahun</label>
                <input type="number" name="tahun" id="edit_tahun" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Stok</label>
                <input type="number" name="stok" id="edit_stok" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Kategori</label>
                <input type="text" name="kategori" id="edit_kategori" class="form-control" placeholder="Tulis kategori...">
              </div>

              <div class="col-12">
                <label class="form-label">Prefix Kode Buku</label>
                <input type="text" name="prefix_kode" id="edit_prefix_kode" class="form-control" placeholder="Misal: SJ-">
                <div class="form-text">Mengubah prefix akan mempengaruhi kode buku ke depan.</div>
              </div>

              <div class="col-12">
                <label class="form-label">Deskripsi Buku</label>
                <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="3"></textarea>
              </div>
            </div>

            <div class="mt-4 text-end">
              <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Batal</button>
              <button type="submit" name="update" class="btn btn-primary">Update Data</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL LIHAT KODE -->
  <div class="modal fade" id="modalKode" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold">Status Kode Buku</h5>
            <p id="modalJudulBuku" class="small text-muted mb-0"></p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="loadingKode" class="text-center text-muted py-4 d-none">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Memuat Data...</p>
          </div>

          <div id="listKode" class="row g-2">
          </div>
        </div>
        <div class="modal-footer bg-light">
          <small class="text-muted">
            <span class="badge bg-primary text-white">Biru Tua</span> = Sedang Dipinjam &bull;
            <span class="badge bg-white text-dark border">Putih</span> = Tersedia
          </small>
        </div>
      </div>
    </div>
  </div>

  <!-- BOOTSTRAP 5 JS (OFFLINE) -->
  <script src="../../asset/js/bootstrap.bundle.min.js"></script>

  <script>
    function openModalEdit(btn) {
      document.getElementById('edit_id').value = btn.getAttribute('data-id');
      document.getElementById('edit_judul').value = btn.getAttribute('data-judul');
      document.getElementById('edit_penulis').value = btn.getAttribute('data-penulis');
      document.getElementById('edit_penerbit').value = btn.getAttribute('data-penerbit');
      document.getElementById('edit_tahun').value = btn.getAttribute('data-tahun');
      document.getElementById('edit_stok').value = btn.getAttribute('data-stok');
      document.getElementById('edit_deskripsi').value = btn.getAttribute('data-deskripsi');
      document.getElementById('edit_kategori').value = btn.getAttribute('data-kategori');
      document.getElementById('edit_prefix_kode').value = btn.getAttribute('data-prefix');

      const gambar = btn.getAttribute('data-gambar');
      const imgPreview = document.getElementById('edit_preview_gambar');
      if (gambar) {
        imgPreview.src = "../../uploads/" + gambar;
        imgPreview.style.display = 'block';
      } else {
        imgPreview.style.display = 'none';
      }
    }

    function tampilKode(id_buku, judul, stok) {
      document.getElementById('modalJudulBuku').innerText = judul + " (Total Stok: " + stok + ")";

      const listContainer = document.getElementById('listKode');
      const loading = document.getElementById('loadingKode');

      listContainer.innerHTML = '';
      loading.classList.remove('d-none');

      fetch('get_kode.php?id_buku=' + id_buku)
        .then(response => response.json())
        .then(data => {
          loading.classList.add('d-none');

          if (!data.prefix) {
            listContainer.innerHTML = '<div class="col-12 text-center text-danger">Gagal memuat data format.</div>';
            return;
          }

          const prefix = data.prefix;
          const borrowed = data.borrowed || [];

          for (let i = 1; i <= stok; i++) {
            let num = i.toString().padStart(3, '0');
            let codeFull = prefix + num;
            let isBorrowed = borrowed.includes(codeFull);

            let cardClass = isBorrowed ?
              'bg-primary text-white border-primary' :
              'bg-white text-dark border border-secondary';

            let statusText = isBorrowed ? 'Dipinjam' : 'Tersedia';

            let col = document.createElement('div');
            col.className = 'col-6 col-md-4 col-lg-3';

            let borderStyle = isBorrowed ? '' : 'style="border: 1px solid #dee2e6 !important; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"';

            col.innerHTML = `
              <div class="card ${cardClass} text-center p-2 h-100" ${borderStyle}>
                <div class="card-body d-flex flex-column justify-content-center p-0">
                  <div class="fw-bold fs-6">${codeFull}</div>
                  <div class="small fw-medium mt-1">${statusText}</div>
                </div>
              </div>
            `;
            listContainer.appendChild(col);
          }
        })
        .catch(error => {
          console.error(error);
          loading.classList.add('d-none');
          listContainer.innerHTML = '<div class="col-12 text-center text-danger">Gagal memuat data.</div>';
        });
    }
  </script>
</body>

</html>