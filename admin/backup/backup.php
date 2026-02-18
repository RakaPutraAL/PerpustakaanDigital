<?php
session_start();
include __DIR__ . "/../../config/database.php";

// Cek Keamanan: Hanya Admin yang boleh akses
if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
  die("<div class='alert alert-danger text-center m-5'>Akses ditolak. Anda bukan Administrator.</div>");
}


// --- HITUNG BADGE NOTIFIKASI PENGAJUAN (TAMBAHKAN INI) ---
$count_pending_pinjam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status = 'pending_pinjam'"))['total'];
$count_pending_kembali = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status = 'pending_kembali'"))['total'];
$total_pending = $count_pending_pinjam + $count_pending_kembali;


// --- HITUNG BADGE NOTIFIKASI DENDA ---
 $count_denda_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi WHERE status = 'pending_payment'"))['total'];

// --- PASTIKA FOLDER DOWNLOAD (Simpan Backup Otomatis) ---
$download_dir = __DIR__ . "/sqldownload/";
if (!file_exists($download_dir)) {
  mkdir($download_dir, 0777, true); // Buat folder jika belum ada
}

// --- AMBIL DATA ADMIN (Untuk Header & Navigasi) ---
$data_admin = null;
$admin_name = 'Admin';

// Cek apakah ada ID di session
if (isset($_SESSION['id'])) {
  $id_session = (int)$_SESSION['id'];

  // PERCOBAAN 1: Coba ambil dari tabel 'users' (paling umum)
  // Cek dulu apakah tabel users ada
  $check_users = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
  if ($check_users && mysqli_num_rows($check_users) > 0) {
    // Filter hanya yang levelnya 'admin'
    $query_users = mysqli_query($conn, "SELECT * FROM users WHERE id = '$id_session' AND level = 'admin'");
    if ($query_users && mysqli_num_rows($query_users) > 0) {
      $data_admin = mysqli_fetch_assoc($query_users);
    }
  }

  // PERCOBAAN 2: Jika tidak ketemu di users, coba dari tabel 'admin' (khusus)
  if (!$data_admin) {
    // Cek dulu apakah tabel admin ada
    $check_admin = mysqli_query($conn, "SHOW TABLES LIKE 'admin'");
    if ($check_admin && mysqli_num_rows($check_admin) > 0) {
      $query_admin_table = mysqli_query($conn, "SELECT * FROM admin WHERE id = '$id_session'");
      if ($query_admin_table && mysqli_num_rows($query_admin_table) > 0) {
        $data_admin = mysqli_fetch_assoc($query_admin_table);
      }
    }
  }
}

// Jika query berhasil mendapatkan data, ambil usernamenya
if ($data_admin && isset($data_admin['username'])) {
  $admin_name = $data_admin['username'];
} else {
  // Fallback terakhir jika query gagal total: pakai data session
  $admin_name = $_SESSION['username'] ?? $_SESSION['nama'] ?? 'Admin';
}

// --- LOGIKA BACKUP (DOWNLOAD & SIMPAN) ---
if (isset($_GET['action']) && $_GET['action'] == 'backup') {
  $date = date('Y-m-d_H-i-s');
  $filename = "backup_perpustakaan_" . $date . ".sql";

  // 1. Ambil Nama Database
  $db_name = mysqli_fetch_assoc(mysqli_query($conn, "SELECT DATABASE() as db"))['db'];

  // 2. Ambil semua tabel
  $tables = array();
  $result = mysqli_query($conn, "SHOW TABLES");
  while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
  }

  // 3. Loop tiap tabel untuk generate SQL
  $sql_content = "";
  foreach ($tables as $table) {
    // Header tabel (DROP TABLE IF EXISTS + CREATE TABLE)
    $sql_content .= "DROP TABLE IF EXISTS `$table`;\n";
    $createTable = mysqli_fetch_row(mysqli_query($conn, "SHOW CREATE TABLE `$table`"));
    $sql_content .= $createTable[1] . ";\n\n";

    // Isi tabel (INSERT INTO)
    $result = mysqli_query($conn, "SELECT * FROM `$table`");
    $column_count = mysqli_num_fields($result);

    while ($row = mysqli_fetch_row($result)) {
      $sql_content .= "INSERT INTO `$table` VALUES(";
      for ($i = 0; $i < $column_count; $i++) {
        $row[$i] = $row[$i];
        if (isset($row[$i])) {
          // Escape string agar aman
          $row[$i] = mysqli_real_escape_string($conn, $row[$i]);
          $sql_content .= "'" . $row[$i] . "'";
        } else {
          $sql_content .= "NULL";
        }
        if ($i < ($column_count - 1)) {
          $sql_content .= ", ";
        }
      }
      $sql_content .= ");\n";
    }
    $sql_content .= "\n\n";
  }

  // 4. SIMPAN FILE KE FOLDER DOWNLOAD (Agar Tersimpan Permanen di Project)
  $file_path = $download_dir . $filename;
  file_put_contents($file_path, $sql_content);

  // 5. Paksa Download File
  header('Content-Type: text/plain');
  header('Content-Disposition: attachment; filename="' . $filename . '"');
  readfile($file_path); // Baca dari folder sqldownload
  exit; // Stop script agar tidak tampil HTML
}

// --- LOGIKA RESTORE (UPLOAD MANUAL & SIMPAN) ---
$message = "";
$msg_type = ""; // success atau danger

if (isset($_POST['restore'])) {
  $file = $_FILES['file_sql'];

  if ($file['error'] == 0) {
    $filename = $file['tmp_name']; // Nama file sementara di server
    $basename = basename($file['name']); // Nama asli file user

    // 1. SIMPAN FILE SQL KE FOLDER DOWNLOAD (Agar Masuk Koleksi Backup)
    $stored_filename = time() . "_" . $basename;
    $target_path = $download_dir . $stored_filename;

    if (move_uploaded_file($filename, $target_path)) {
      // CEK UKURAN FILE
      $file_size = filesize($target_path);

      if ($file_size === false || $file_size == 0) {
        $message = "File kosong atau tidak bisa dibaca. Ukuran: 0 byte.";
        $msg_type = "danger";
      } else {
        // BACA FILE MENGGUNAKAN file_get_contents (Lebih Reliable)
        $sql_content = file_get_contents($target_path);

        if ($sql_content === false || empty($sql_content)) {
          $message = "Gagal membaca isi file. Pastikan file .sql valid. Ukuran file: " . number_format($file_size) . " byte.";
          $msg_type = "danger";
        } else {
          // Pisahkan per baris (support berbagai line ending: \r\n, \n, \r)
          $lines = preg_split('/\r\n|\r|\n/', $sql_content);

          // Filter baris kosong
          $lines = array_filter($lines, function ($line) {
            return trim($line) != '';
          });

          if (count($lines) == 0) {
            $message = "File tidak mengandung query SQL yang valid.";
            $msg_type = "danger";
          } else {
            // Eksekusi per baris (Pisahkan per query)
            $templine = '';
            $success_count = 0;
            $error_count = 0;
            $error_messages = array();

            // MATIKAN FOREIGN KEY CHECK (Penting untuk restore)
            mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=0");

            mysqli_begin_transaction($conn); // Mulai transaksi

            foreach ($lines as $line) {
              // Abaikan komentar
              if (substr(trim($line), 0, 2) == '--' || substr(trim($line), 0, 1) == '#' || trim($line) == '') {
                continue;
              }

              $templine .= $line . " "; // Tambah spasi untuk penggabungan query multi-line

              // Jika ada semicolon di akhir, eksekusi query
              if (substr(trim($line), -1, 1) == ';') {
                $query = trim($templine);

                if (mysqli_query($conn, $query)) {
                  $success_count++;
                } else {
                  $error_count++;
                  // Simpan pesan error (max 5 error)
                  if (count($error_messages) < 5) {
                    $error_messages[] = mysqli_error($conn);
                  }
                }
                $templine = '';
              }
            }

            mysqli_commit($conn); // Commit transaksi

            // NYALAKAN KEMBALI FOREIGN KEY CHECK
            mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1");

            if ($error_count == 0) {
              $message = "✅ Database berhasil direstore! ($success_count Query berhasil dijalankan). File tersimpan di folder sqldownload.";
              $msg_type = "success";
            } else {
              $error_detail = !empty($error_messages) ? "<br><small>Contoh error: " . implode(", ", $error_messages) . "</small>" : "";
              $message = "⚠️ Restore selesai dengan $success_count sukses dan $error_count gagal. File tersimpan di folder sqldownload." . $error_detail;
              $msg_type = "warning";
            }
          }
        }
      }
    } else {
      $message = "Gagal memindahkan file ke folder sqldownload.";
      $msg_type = "danger";
    }
  } else {
    $error_msg = array(
      1 => "File terlalu besar (melebihi upload_max_filesize di php.ini)",
      2 => "File terlalu besar (melebihi MAX_FILE_SIZE di form)",
      3 => "File hanya terupload sebagian",
      4 => "Tidak ada file yang diupload",
      6 => "Folder temporary tidak ditemukan",
      7 => "Gagal menulis file ke disk",
      8 => "Upload dihentikan oleh ekstensi PHP"
    );

    $message = "Gagal upload file. " . ($error_msg[$file['error']] ?? "Error code: " . $file['error']);
    $msg_type = "danger";
  }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Backup & Restore Database</title>
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

    /* Menu Aktif (Backup & Restore) */
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
              <a class="nav-link" href="../laporan/laporan.php">
                <i class="bi bi-file-earmark-bar-graph"></i> Laporan
              </a>
            </li>

            <!-- === MENU BARU: BACKUP RESTORE (ACTIVE) === -->
            <li class="nav-item">
              <a class="nav-link active" href="backup.php">
                <i class="bi bi-database-add"></i> Backup & Restore
              </a>
            </li>
            <!-- ========================================= -->

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
          <h2 class="fw-bold" style="color: var(--primary-blue);">Backup & Restore Database</h2>
        </div>

        <!-- Notifikasi Pesan -->
        <?php if ($message): ?>
          <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <div class="row g-4">

          <!-- KARTU 1: BACKUP DATABASE -->
          <div class="col-md-6">
            <div class="card card-custom h-100 border-0 shadow-sm">
              <div class="card-body text-center p-5">
                <div class="mb-4">
                  <i class="bi bi-cloud-arrow-down text-primary" style="font-size: 4rem;"></i>
                </div>
                <h4 class="card-title fw-bold mb-3">Backup Database</h4>
                <p class="card-text text-muted mb-4">
                  Download seluruh data dari database saat ini ke dalam file format <strong>.sql</strong>.
                  File akan otomatis tersimpan di folder <strong>admin/backup/sqldownload/</strong>.
                </p>

                <a href="?action=backup" class="btn btn-primary btn-lg px-4 rounded-pill shadow-sm">
                  <i class="bi bi-download me-2"></i> Download Backup
                </a>
              </div>
            </div>
          </div>

          <!-- KARTU 2: RESTORE DATABASE -->
          <div class="col-md-6">
            <div class="card card-custom h-100 border-0 shadow-sm">
              <div class="card-body p-5">
                <div class="d-flex justify-content-between align-items-start mb-4">
                  <div>
                    <h4 class="card-title fw-bold mb-3">Restore Database</h4>
                    <p class="card-text text-muted mb-2">
                      Upload file <strong>.sql</strong> dari komputer Anda.
                      <br><span class="text-danger fw-bold">Perhatian: Data lama akan ditimpa!</span>
                      <br><small>File akan otomatis disimpan ke folder <strong>sqldownload</strong> agar riwayat restore tersimpan.</small>
                    </p>
                  </div>
                  <div class="bg-light rounded-circle p-3">
                    <i class="bi bi-cloud-arrow-up text-danger" style="font-size: 2rem;"></i>
                  </div>
                </div>

                <form method="post" enctype="multipart/form-data">
                  <div class="mb-3">
                    <label for="file_sql" class="form-label fw-bold">Upload File SQL</label>
                    <input class="form-control" type="file" id="file_sql" name="file_sql" accept=".sql" required>
                    <div class="form-text text-muted">
                      Hanya file .sql yang diperbolehkan.
                    </div>
                  </div>

                  <button type="submit" name="restore" class="btn btn-danger w-100 fw-bold" onclick="return confirm('Apakah Anda yakin ingin merestore database? Data lama akan ditimpa!')">
                    <i class="bi bi-upload me-2"></i> Upload & Restore
                  </button>
                </form>
              </div>
            </div>
          </div>

        </div>

        <div class="mt-5 mb-4 text-center text-muted small">
          &copy; <?= date('Y') ?> Sistem Perpustakaan - Backup & Restore Module
        </div>

      </main>
    </div>
  </div>

  <!-- BOOTSTRAP 5 JS -->
  <script src="../../asset/js/bootstrap.bundle.min.js"></script>
</body>

</html>