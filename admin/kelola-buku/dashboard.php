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


// --- LOGIKA STATISTIK ---
try {
  $hitung_buku = mysqli_query($conn, "SELECT id FROM buku");
  $total_buku = mysqli_num_rows($hitung_buku);
} catch (Exception $e) {
  $total_buku = 0;
}

try {
  $hitung_anggota = mysqli_query($conn, "SELECT id FROM users WHERE level = 'user'");
  $total_anggota = mysqli_num_rows($hitung_anggota);
} catch (Exception $e) {
  $total_anggota = 0;
}

try {
  $hitung_pinjam = mysqli_query($conn, "SELECT id FROM transaksi WHERE status IN ('dipinjam', 'pending_payment','pending_kembali','terlambat')");
  $total_pinjam = mysqli_num_rows($hitung_pinjam);
} catch (Exception $e) {
  $total_pinjam = 0;
}

// --- LOGIKA PAGINATION & PENCARIAN ---
$search_text = $_GET['search'] ?? '';
$search_category = $_GET['category'] ?? '';
$search_year = $_GET['year'] ?? '';

// Sanitasi Input
$search_sql = mysqli_real_escape_string($conn, $search_text);
$year_sql = mysqli_real_escape_string($conn, $search_year);
$category_sql = mysqli_real_escape_string($conn, $search_category);

$page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? (int)$_GET['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

// Query Ambil Data Buku
$query = "SELECT buku.*, detail_buku.gambar, detail_buku.kategori
          FROM buku 
          LEFT JOIN detail_buku ON buku.id = detail_buku.id_buku 
          WHERE 1=1";

if ($search_sql) {
  $query .= " AND (buku.judul LIKE '%$search_sql%' 
             OR buku.penulis LIKE '%$search_sql%' 
             OR buku.penerbit LIKE '%$search_sql%')";
}

if ($year_sql) {
  $query .= " AND buku.tahun = '$year_sql'";
}

if ($category_sql) {
  $query .= " AND detail_buku.kategori = '$category_sql'";
}

$query .= " ORDER BY buku.id DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query);

// Query Pagination
$total_query = "SELECT COUNT(*) as total FROM buku 
                LEFT JOIN detail_buku ON buku.id = detail_buku.id_buku 
                WHERE 1=1";

if ($search_sql) {
  $total_query .= " AND (buku.judul LIKE '%$search_sql%' 
                    OR buku.penulis LIKE '%$search_sql%' 
                    OR buku.penerbit LIKE '%$search_sql%')";
}
if ($year_sql) {
  $total_query .= " AND buku.tahun = '$year_sql'";
}
if ($category_sql) {
  $total_query .= " AND detail_buku.kategori = '$category_sql'";
}

$total_result = mysqli_query($conn, $total_query);
$total_data = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_data / $limit);

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
  <title>Dashboard Admin | Perpustakaan</title>
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

    /* Menu Aktif (Dashboard) - Transparan */
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

    .card-custom:hover {
      transform: translateY(-3px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    /* Stat Icon */
    .stat-icon {
      width: 50px;
      height: 50px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
    }

    /* Form Controls */
    .form-control,
    .form-select {
      border-color: #ced4da;
    }

    .form-control:focus,
    .form-select:focus {
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    /* Book Card Specific */
    .book-card {
      height: 100%;
      border-radius: 12px;
      overflow: hidden;
    }

    .book-cover {
      aspect-ratio: 3/4;
      object-fit: cover;
      width: 100%;
    }

    .badge-stock {
      position: absolute;
      top: 10px;
      left: 10px;
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    /* Pagination */
    .page-link {
      color: var(--primary-blue);
    }

    .page-item.active .page-link {
      background-color: var(--primary-blue);
      border-color: var(--primary-blue);
    }
  </style>
</head>

<body>

  <!-- ================= NAVBAR ================= -->
  <nav class="navbar navbar-custom fixed-top">
    <div class="container-fluid">

      <a class="navbar-brand d-flex align-items-center text-primary fw-bold" href="#" style="color: var(--primary-blue) !important; text-decoration: none;">
        <!-- Menggunakan Logo yang cocok (White bg + Shadow) -->
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

      <!-- ================= SIDEBAR (UPDATED) ================= -->
      <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse show">
        <div class="position-sticky pt-3">
          <ul class="nav flex-column">
            <li class="nav-item">
              <a class="nav-link active" aria-current="page" href="dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="kelola_buku.php">
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
            <!-- ======================================= -->

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

            <!-- === MENU BACKUP RESTORE === -->
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

        <!-- Judul Halaman -->
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4">
          <h2 class="fw-bold" style="color: var(--primary-blue);">Dashboard Admin</h2>
        </div>

        <!-- PENCARIAN & FILTER -->
        <div class="card card-custom p-3 mb-4">
          <form method="get">
            <div class="row g-3">

              <!-- Search Text -->
              <div class="col-md-4">
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                  <input type="text" name="search" value="<?= htmlspecialchars($search_text) ?>"
                    class="form-control border-start-0 bg-light"
                    placeholder="Cari judul, penulis, penerbit...">
                </div>
              </div>

              <!-- Filter Kategori -->
              <div class="col-md-3">
                <select name="category" class="form-select">
                  <option value="">Semua Kategori</option>
                  <?php
                  $cat_query = mysqli_query($conn, "SELECT DISTINCT kategori FROM detail_buku WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori ASC");
                  while ($c = mysqli_fetch_assoc($cat_query)): ?>
                    <option value="<?= $c['kategori'] ?>" <?= ($category_sql == $c['kategori']) ? 'selected' : '' ?>>
                      <?= $c['kategori'] ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>

              <!-- Filter Tahun -->
              <div class="col-md-3">
                <select name="year" class="form-select">
                  <option value="">Semua Tahun</option>
                  <?php
                  $year_query = mysqli_query($conn, "SELECT DISTINCT tahun FROM buku WHERE tahun IS NOT NULL AND tahun != '' ORDER BY tahun DESC");
                  while ($y = mysqli_fetch_assoc($year_query)): ?>
                    <option value="<?= $y['tahun'] ?>" <?= ($year_sql == $y['tahun']) ? 'selected' : '' ?>>
                      <?= $y['tahun'] ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>

              <!-- Tombol -->
              <div class="col-md-2">
                <div class="d-grid gap-2 d-md-flex">
                  <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Cari
                  </button>
                  <a href="dashboard.php" class="btn btn-outline-secondary" title="Reset">
                    <i class="bi bi-arrow-counterclockwise"></i>
                  </a>
                </div>
              </div>

            </div>
          </form>
        </div>

        <!-- STATS CARDS -->
        <div class="row g-3 mb-4">
          <!-- Kartu Total Buku -->
          <div class="col-md-4">
            <div class="card card-custom p-3 border-0 text-white shadow-sm" style="background: linear-gradient(45deg, #0d6efd, #0043a8);">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h6 class="text-white-50 mb-1 small fw-light text-uppercase">Total Buku</h6>
                  <h3 class="fw-bold mb-0"><?= $total_buku ?></h3>
                </div>
                <!-- Logo di pojok kanan atas -->
                <i class="bi bi-book fs-1 opacity-50"></i>
              </div>
            </div>
          </div>

          <!-- Kartu Total Siswa -->
          <div class="col-md-4">
            <div class="card card-custom p-3 border-0 text-white shadow-sm" style="background: linear-gradient(45deg, #0dcaf0, #077a96);">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h6 class="text-white-50 mb-1 small fw-light text-uppercase">Total Siswa</h6>
                  <h3 class="fw-bold mb-0"><?= $total_anggota ?></h3>
                </div>
                <!-- Logo di pojok kanan atas -->
                <i class="bi bi-people fs-1 opacity-50"></i>
              </div>
            </div>
          </div>

          <!-- Kartu Peminjaman Aktif -->
          <div class="col-md-4">
            <div class="card card-custom p-3 border-0 text-white shadow-sm" style="background: linear-gradient(45deg, #ffc107, #b68900);">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h6 class="text-white-50 mb-1 small fw-light text-uppercase">Peminjaman Aktif</h6>
                  <h3 class="fw-bold mb-0"><?= $total_pinjam ?></h3>
                </div>
                <!-- Logo di pojok kanan atas -->
                <i class="bi bi-bookmark-check fs-1 opacity-50"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Header Daftar Buku -->
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="fw-bold text-dark">Daftar Buku</h5>
          <small class="text-muted">Menampilkan 5 buku per halaman</small>
        </div>

        <!-- Books Grid -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4">
          <?php if (mysqli_num_rows($result) > 0) {
            while ($b = mysqli_fetch_assoc($result)): ?>
              <div class="col">
                <div class="card book-card h-100">
                  <div class="position-relative">
                    <!-- LOGIKA GAMBAR -->
                    <div class="book-cover bg-light border d-flex align-items-center justify-content-center">
                      <?php if (!empty($b['gambar'])): ?>
                        <img src="../../uploads/<?= $b['gambar'] ?>" alt="<?= htmlspecialchars($b['judul']) ?>"
                          style="width: 100%; height: 100%; object-fit: cover;">
                      <?php else: ?>
                        <i class="bi bi-book text-muted" style="font-size: 3rem;"></i>
                      <?php endif; ?>
                    </div>

                    <!-- Badge Stok -->
                    <div class="badge-stock text-white shadow-sm <?php echo ($b['stok'] > 0) ? 'bg-primary' : 'bg-danger'; ?>">
                      <?php echo ($b['stok'] > 0) ? 'Tersedia' : 'Habis'; ?>
                    </div>
                  </div>
                  <div class="card-body d-flex flex-column p-3">
                    <h6 class="card-title fw-bold text-dark text-truncate mb-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;" title="<?= htmlspecialchars($b['judul']) ?>">
                      <?= htmlspecialchars($b['judul']) ?>
                    </h6>
                    <p class="card-text text-muted small mb-1"><?= htmlspecialchars($b['penulis']) ?></p>
                    <p class="card-text text-secondary small mb-2"><?= $b['tahun'] ?></p>

                    <?php if (!empty($b['kategori'])): ?>
                      <span class="badge bg-light text-primary border w-100 text-center py-2 mt-auto">
                        <?= htmlspecialchars($b['kategori']) ?>
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endwhile;
          } else { ?>
            <div class="col-12 text-center py-5">
              <i class="bi bi-inbox fs-1 text-muted"></i>
              <p class="text-muted mt-2">Tidak ada buku ditemukan.</p>
            </div>
          <?php } ?>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
          <nav aria-label="Page navigation">
            <ul class="pagination">
              <?php
              $params = http_build_query(['search' => $search_text, 'year' => $search_year, 'category' => $search_category]);
              $prefix = $params ? "?$params&page=" : "?page=";
              ?>

              <!-- Previous -->
              <?php if ($page > 1): ?>
                <li class="page-item">
                  <a class="page-link" href="<?= $prefix . ($page - 1) ?>">
                    <i class="bi bi-chevron-left"></i>
                  </a>
                </li>
              <?php endif; ?>

              <!-- Numbered -->
              <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                  <a class="page-link" href="<?= $prefix . $i ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>

              <!-- Next -->
              <?php if ($page < $total_pages): ?>
                <li class="page-item">
                  <a class="page-link" href="<?= $prefix . ($page + 1) ?>">
                    <i class="bi bi-chevron-right"></i>
                  </a>
                </li>
              <?php endif; ?>
            </ul>
          </nav>
        </div>

      </main>
    </div>
  </div>

  <!-- BOOTSTRAP 5 JS (OFFLINE) -->
  <script src="../../asset/js/bootstrap.bundle.min.js"></script>
</body>

</html>