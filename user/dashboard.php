<?php
session_start();
include "../config/database.php";

// 1. Cek apakah user sudah login
if (!isset($_SESSION['login'])) {
    header("Location: ../login.php");
    exit;
}

// 2. PENYESUAIAN USER/ANGGOTA:
if (isset($_SESSION['level']) && $_SESSION['level'] === 'admin') {
    header("Location: ../admin/kelola-buku/dashboard.php");
    exit;
}

// --- PERBAIKAN PENGAMBILAN NAMA USER ---
 $username = $_SESSION['username'] ?? $_SESSION['nama'] ?? $_SESSION['user'] ?? null;

if (empty($username) && isset($_SESSION['id'])) {
    $query_user = mysqli_query($conn, "SELECT username FROM users WHERE id = " . (int)$_SESSION['id']);
    $data_user = mysqli_fetch_assoc($query_user);
    if ($data_user) {
        $username = $data_user['username'];
        $_SESSION['username'] = $username;
    }
}

if (empty($username)) {
    $username = 'Siswa';
}

 $user_level = $_SESSION['level'] ?? 'user';
 $user_id = $_SESSION['id'];

/// --- LOGIKA CEK KUOTA (MAX 3 BUKU) ---
 $max_borrow_limit = 3;
 $current_borrows = 0;

 $check_quota = mysqli_query($conn, "SELECT COUNT(*) as total 
    FROM transaksi 
    WHERE nama_peminjam = '$username' 
    AND LOWER(TRIM(status)) NOT IN ('selesai', 'kembali')");

if ($check_quota) {
    $quota_data = mysqli_fetch_assoc($check_quota);
    $current_borrows = (int)$quota_data['total'];
}

 $remaining_quota = $max_borrow_limit - $current_borrows;
 $can_borrow = ($remaining_quota > 0);

// --- PERBAIKAN LOGIKA: AMBIL ID BUKU DAN STATUS SECARA TERPISAH ---
// Ini untuk membedakan mana yang 'pending' (dikunci) dan mana yang 'dipinjam' (boleh lihat detail)
 $check_active = mysqli_query($conn, "SELECT id_buku, status FROM transaksi WHERE nama_peminjam = '$username' AND status IN ('pending_pinjam', 'dipinjam')");
 $active_book_status = []; // Array Associatif: Key = id_buku, Value = status

if ($check_active) {
    while ($row_p = mysqli_fetch_assoc($check_active)) {
        $active_book_status[$row_p['id_buku']] = $row_p['status'];
    }
}


// --- LOGIKA PAGINATION & PENCARIAN ---
 $search_text = $_GET['search'] ?? '';
 $search_year = $_GET['year'] ?? '';
 $search_category = $_GET['category'] ?? '';

 $search_sql = mysqli_real_escape_string($conn, $search_text);
 $year_sql = mysqli_real_escape_string($conn, $search_year);
 $category_sql = mysqli_real_escape_string($conn, $search_category);

 $page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? (int)$_GET['page'] : 1;

// LOGIKA HALAMAN
if ($page == 1) {
    $limit = 5;
} else {
    $limit = 10;
}

// Hitung offset
if ($page == 1) {
    $offset = 0;
} else {
    $offset = 5 + (($page - 2) * 10);
}

// Query Ambil Data Buku
 $query = "SELECT buku.*, detail_buku.gambar, detail_buku.kategori, detail_buku.kode_buku, buku.penulis
          FROM buku 
          LEFT JOIN detail_buku ON buku.id = detail_buku.id_buku 
          WHERE 1=1";

// PERUBAHAN: Search Bar sekarang mencakup Judul, Penulis, Penerbit, DAN KODE BUKU
if ($search_sql) {
    $query .= " AND (buku.judul LIKE '%$search_sql%' 
               OR buku.penerbit LIKE '%$search_sql%' 
               OR buku.penulis LIKE '%$search_sql%'
               OR detail_buku.kode_buku LIKE '%$search_sql%')";
}

if ($year_sql) {
    $query .= " AND buku.tahun = '$year_sql'";
}

if ($category_sql) {
    $query .= " AND detail_buku.kategori = '$category_sql'";
}

 $query .= " ORDER BY buku.id DESC LIMIT $limit OFFSET $offset";
 $result = mysqli_query($conn, $query);

// Query Pagination (Harus konsisten dengan query utama)
 $total_query = "SELECT COUNT(*) as total FROM buku 
                LEFT JOIN detail_buku ON buku.id = detail_buku.id_buku 
                WHERE 1=1";

if ($search_sql) {
    $total_query .= " AND (buku.judul LIKE '%$search_sql%' 
                    OR buku.penerbit LIKE '%$search_sql%' 
                    OR buku.penulis LIKE '%$search_sql%'
                    OR detail_buku.kode_buku LIKE '%$search_sql%')";
}

if ($year_sql) {
    $total_query .= " AND tahun = '$year_sql'";
}

if ($category_sql) {
    $total_query .= " AND kategori = '$category_sql'";
}

 $total_result = mysqli_query($conn, $total_query);
 $total_data = mysqli_fetch_assoc($total_result)['total'];

// Hitung total halaman
if ($total_data <= 5) {
    $total_pages = 1;
} else {
    $remaining_after_page1 = $total_data - 5;
    $total_pages = 1 + ceil($remaining_after_page1 / 10);
}

 $show_hero = ($page == 1);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Katalog Buku | Siswa</title>

    <!-- BOOTSTRAP 5 CSS -->
    <link href="../asset/css/bootstrap.min.css" rel="stylesheet">

    <!-- BOOTSTRAP ICONS -->
    <link rel="stylesheet" href="../asset/font/bootstrap-icons.css">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f3f4f6;
            overflow: hidden;
        }

        :root {
            --bs-primary: #0d6efd;
            --bs-dark: #212529;
            --sidebar-bg: #1a1e21;
            --sidebar-text: #e9ecef;
            --card-hover-bg: #f1f5f9;
        }

        .d-flex-wrapper {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        .sidebar {
            width: 280px;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            display: flex;
            flex-direction: column;
            border-right: 1px solid #343a40;
            flex-shrink: 0;
            transition: all 0.3s;
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

        .sidebar .nav-link:hover,
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
            padding: 1.5rem;
        }

        .book-card {
            border: none;
            border-radius: 12px;
            transition: transform 0.2s, box-shadow 0.2s;
            background: #fff;
            height: 100%;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .book-img-wrapper {
            position: relative;
            padding-top: 133%;
            overflow: hidden;
            background-color: #e9ecef;
        }

        .book-img-wrapper img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .book-card:hover .book-img-wrapper img {
            transform: scale(1.05);
        }

        .badge-custom {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            z-index: 2;
        }

        .badge-available {
            background-color: rgba(13, 110, 253, 0.9);
            color: white;
        }

        .badge-empty {
            background-color: rgba(220, 53, 69, 0.9);
            color: white;
        }

        .hero-section {
            background: linear-gradient(135deg, #0d6efd 0%, #000000 100%);
            color: white;
            border-radius: 16px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
            margin-bottom: 2rem;
            box-shadow: 0 8px 20px rgba(13, 110, 253, 0.25);
        }

        .hero-bg-icon {
            position: absolute;
            right: -20px;
            bottom: -40px;
            font-size: 10rem;
            opacity: 0.1;
            color: white;
            transform: rotate(-15deg);
        }

        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }
    </style>
</head>

<body>

    <div class="d-flex-wrapper">

        <aside class="sidebar d-none d-md-flex">
            <div class="brand d-flex align-items-center gap-3">
                <div class="bg-white rounded p-2 shadow-sm d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <img src="../asset/img/logo.png" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div>
                    <h5 class="m-0 fw-bold">SIPERDI</h5>
                    <small class="text-secondary">Student Portal</small>
                </div>
            </div>

            <nav class="nav flex-column mt-3">
                <a class="nav-link active" href="dashboard.php">
                    <i class="bi bi-grid-fill"></i> Katalog Buku
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

        <main class="main-content">

            <header class="top-header">
                <div class="d-none d-md-block">
                    <h5 class="fw-bold mb-0 text-dark">Katalog Buku</h5>
                    <small class="text-muted">Temukan buku favoritmu</small>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div class="text-end d-none d-sm-block">
                        <div class="fw-bold text-dark"><?= htmlspecialchars($username) ?></div>
                        <small class="text-muted">Siswa</small>
                    </div>
                    <div class="rounded-circle border border-2 border-primary overflow-hidden bg-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-person-fill text-white fs-5"></i>
                    </div>
                </div>
            </header>

            <div class="content-scroll">
                <div class="container-fluid p-0">

                    <!-- HERO BANNER -->
                    <?php if ($show_hero): ?>
                    <div class="hero-section">
                        <i class="bi bi-book hero-bg-icon"></i>
                        <div class="position-relative z-1" style="max-width: 600px;">
                            <h2 class="display-6 fw-bold mb-2">Selamat datang, <?= htmlspecialchars($username) ?>! 👋</h2>
                            <p class="text-white-50 mb-4 fs-5">
                                Cari buku berdasarkan Judul, Penulis, atau Kode Buku di kolom pencarian.
                            </p>
                            <div class="d-flex gap-2">
                                <button onclick="document.getElementById('search-input').focus()" class="btn btn-light text-primary fw-bold px-4 py-2 rounded-3">
                                    <i class="bi bi-search"></i> Cari Sekarang
                                </button>
                                <a href="peminjaman.php" class="btn btn-outline-light fw-bold px-4 py-2 rounded-3">
                                    <i class="bi bi-book-half"></i> Peminjaman
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- FILTER FORM -->
                    <form method="get" class="bg-white p-3 rounded-4 shadow-sm border mb-4">
                        <div class="row g-3">
                            <!-- Search Bar -->
                            <div class="col-md-5">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-secondary"></i></span>
                                    <input type="text" id="search-input" name="search" value="<?= htmlspecialchars($search_text) ?>"
                                        class="form-control border-start-0 bg-light" placeholder="Cari judul, penulis, atau KODE BUKU...">
                                </div>
                            </div>

                            <!-- Category -->
                            <div class="col-md-2">
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

                            <!-- Year -->
                            <div class="col-md-2">
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

                            <!-- Buttons -->
                            <div class="col-md-3 d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary" title="Reset">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- HEADING GRID -->
                    <div class="d-flex justify-content-between align-items-end mb-4">
                        <h5 class="fw-bold text-dark m-0">Rekomendasi Buku</h5>

                        <div class="text-end">
                            <small class="text-muted d-block">Kuota Peminjaman Saat Ini:</small>
                            <span class="fw-bold fs-5 <?= ($current_borrows >= $max_borrow_limit) ? 'text-danger' : 'text-primary' ?>">
                                <?= $current_borrows ?> / <?= $max_borrow_limit ?> Buku
                            </span>

                            <?php if ($current_borrows >= $max_borrow_limit): ?>
                                <div class="text-danger small fw-bold mt-1">
                                    <i class="bi bi-exclamation-circle"></i> Limit Penuh
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- BOOKS GRID -->
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4">
                        <?php if (mysqli_num_rows($result) > 0) {
                            while ($b = mysqli_fetch_assoc($result)): 
                            
                            // CEK STATUS AKTIF (PENDING ATAU DIPINJAM)
                            $book_status = isset($active_book_status[$b['id']]) ? $active_book_status[$b['id']] : null;
                            ?>
                                <div class="col">
                                    <div class="book-card h-100 d-flex flex-column">
                                        <div class="book-img-wrapper">
                                            <?php if (!empty($b['gambar'])): ?>
                                                <img src="../uploads/<?= $b['gambar'] ?>" alt="<?= htmlspecialchars($b['judul']) ?>">
                                            <?php else: ?>
                                                <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                                                    <i class="bi bi-book" style="font-size: 4rem; opacity: 0.4;"></i>
                                                    <span class="small fw-bold mt-2 text-uppercase">No Cover</span>
                                                </div>
                                            <?php endif; ?>

                                            <div class="badge-custom <?= ($b['stok'] > 0) ? 'badge-available' : 'badge-empty' ?>">
                                                <?= ($b['stok'] > 0) ? 'Tersedia' : 'Habis' ?>
                                            </div>
                                        </div>

                                        <div class="card-body p-3 d-flex flex-column flex-grow-1">
                                            <h6 class="fw-bold text-dark text-truncate mb-1" title="<?= htmlspecialchars($b['judul']) ?>">
                                                <?= htmlspecialchars($b['judul']) ?>
                                            </h6>
                                            <p class="text-secondary small mb-1 text-truncate"><?= htmlspecialchars($b['penulis']) ?></p>
                                            <p class="text-muted small mb-2"><?= $b['tahun'] ?></p>

                                            <?php if (!empty($b['kode_buku'])): ?>
                                                <div class="mb-2">
                                                    <span class="badge bg-secondary small">
                                                        <?= htmlspecialchars($b['kode_buku']) ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($b['kategori'])): ?>
                                                <span class="small fw-semibold" style="color: var(--bs-primary);">
                                                    <?= htmlspecialchars($b['kategori']) ?>
                                                </span>
                                            <?php endif; ?>

                                            <div class="mt-auto d-flex justify-content-end align-items-center">
                                                <!-- LOGIKA BARU: PEMISAHAN TOMBOL -->
                                                <?php if ($book_status === 'pending_pinjam'): ?>
                                                    <!-- Status: Menunggu Admin -> DIKUNCI -->
                                                    <div class="text-warning small fw-bold d-flex align-items-center gap-1" style="cursor: not-allowed;" title="Menunggu persetujuan admin.">
                                                        <i class="bi bi-hourglass-split"></i> Sedang Diajukan
                                                    </div>

                                                <?php elseif ($book_status === 'dipinjam'): ?>
                                                    <!-- Status: Sudah Disetujui -> BISA LIHAT DETAIL (Tapi tidak bisa pinjam lagi di halaman detail) -->
                                                    <a href="detail_buku.php?id=<?= $b['id'] ?>" class="text-primary small fw-bold text-decoration-none d-flex align-items-center gap-1">
                                                        Detail <i class="bi bi-arrow-right-circle"></i>
                                                    </a>

                                                <?php elseif (!$can_borrow): ?>
                                                    <!-- Status: Kuota Penuh -->
                                                    <div class="text-danger small fw-bold d-flex align-items-center gap-1" style="cursor: not-allowed;" title="Limit peminjaman penuh.">
                                                        <i class="bi bi-lock-fill"></i> Limit Penuh
                                                    </div>

                                                <?php else: ?>
                                                    <!-- Status: Bisa Pinjam -->
                                                    <a href="detail_buku.php?id=<?= $b['id'] ?>" class="text-primary small fw-bold text-decoration-none d-flex align-items-center gap-1">
                                                        Detail <i class="bi bi-arrow-right-circle"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile;
                        } else { ?>
                            <div class="col-12 text-center py-5 text-muted">
                                <i class="bi bi-search display-4 d-block mb-3"></i>
                                <p>Tidak ada buku ditemukan.</p>
                            </div>
                        <?php } ?>
                    </div>

                    <!-- PAGINATION -->
                    <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-center mt-5">
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-lg">
                                <?php
                                // Parameter URL
                                $params = http_build_query([
                                    'search' => $search_text, 
                                    'year' => $search_year, 
                                    'category' => $search_category
                                ]);
                                $prefix = $params ? "?$params&page=" : "?page=";
                                ?>

                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link text-dark" href="<?= $prefix . ($page - 1) ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                        <?php if ($i == $page): ?>
                                            <span class="page-link bg-primary border-primary"><?= $i ?></span>
                                        <?php else: ?>
                                            <a class="page-link text-dark" href="<?= $prefix . $i ?>"><?= $i ?></a>
                                        <?php endif; ?>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link text-dark" href="<?= $prefix . ($page + 1) ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled"><span class="page-link">&raquo;</span></li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </main>
    </div>

    <script src="../asset/js/bootstrap.bundle.min.js"></script>
</body>
</html>