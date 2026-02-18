<?php
session_start();
include "../config/database.php";

// 1. Cek Sesi
if (!isset($_SESSION['login'])) {
    header("Location: ../login.php");
    exit;
}

// 2. Cek Level (Jangan biarkan Admin masuk ke halaman ini)
if (isset($_SESSION['level']) && $_SESSION['level'] === 'admin') {
    header("Location: ../admin/kelola-buku/dashboard.php");
    exit;
}

 $username = $_SESSION['username'] ?? 'Siswa';

// --- 2. KONFIGURASI PAGINATION ---
 $limit = 7;
 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
 $start = ($page > 1) ? ($page * $limit) - $limit : 0;

// Hitung Total Data
 $query_total = "SELECT COUNT(*) as total FROM transaksi WHERE nama_peminjam = '$username'";
 $result_total = mysqli_query($conn, $query_total);
 $row_total = mysqli_fetch_assoc($result_total);
 $total_data = $row_total['total'];
 $total_pages = ceil($total_data / $limit);

// 3. QUERY DATA
 $query = "
    SELECT transaksi.*, buku.judul, detail_buku.gambar 
    FROM transaksi 
    JOIN buku ON transaksi.id_buku = buku.id 
    LEFT JOIN detail_buku ON buku.id = detail_buku.id_buku
    WHERE transaksi.nama_peminjam = '$username' 
    ORDER BY transaksi.id DESC
    LIMIT $start, $limit
";
 $result = mysqli_query($conn, $query);

// Konfigurasi Denda
 $denda_per_hari = 1500;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Peminjaman Saya | Siswa</title>

    <!-- BOOTSTRAP 5 CSS -->
    <link href="../asset/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../asset/font/bootstrap-icons.css">

    <style>
        :root {
            --bs-primary: #0d6efd;
            --sidebar-bg: #1a1e21;
            --sidebar-text: #e9ecef;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f3f4f6;
            overflow: hidden;
        }

        .d-flex-wrapper {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        /* Sidebar Styling */
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
            text-decoration: none;
        }

        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(13, 110, 253, 0.15);
            border-left: 4px solid var(--bs-primary);
        }

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
            padding: 1.5rem;
        }

        /* Table Customization */
        .table-custom {
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        .table-custom thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            padding: 12px 16px;
            font-weight: 600;
        }

        .table-custom tbody td {
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
            padding: 12px 16px;
            color: #374151;
        }

        .table-custom tr:last-child td {
            border-bottom: none;
        }

        .table-custom tr:hover td {
            background-color: #f8f9fa;
        }

        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        /* Badge Khusus Info Denda */
        .badge-fine-info {
            background-color: #ffeeba;
            color: #856404;
            border: 1px solid #ffc107;
            display: block;
            margin-top: 4px;
            font-size: 0.7rem;
            text-align: center;
            border-radius: 50px;
        }

        .font-mono {
            font-family: 'Courier New', Courier, monospace;
        }
    </style>
</head>

<body>

    <div class="d-flex-wrapper">

        <!-- SIDEBAR -->
        <aside class="sidebar d-none d-md-flex">
            <div class="brand d-flex align-items-center gap-3">
                <div class="bg-white rounded p-2 shadow-sm d-flex align-items-center justify-content-center me-2" style="width: 48px; height: 48px;">
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
                <a class="nav-link active" href="peminjaman.php">
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
                <h5 class="fw-bold mb-0 text-dark">Peminjaman Saya</h5>

                <div class="d-flex align-items-center gap-3">
                    <div class="bg-white border rounded px-3 py-2 d-flex flex-column align-items-end shadow-sm">
                        <div id="live-clock" class="text-primary fw-bold font-mono small mb-0">Memuat waktu...</div>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <div class="text-end lh-1">
                                <div class="fw-bold text-dark small"><?= htmlspecialchars($username) ?></div>
                                <div class="text-muted" style="font-size: 0.7rem; text-transform: uppercase;">Siswa</div>
                            </div>
                            <div class="rounded-circle border border-2 border-primary overflow-hidden bg-primary d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                <i class="bi bi-person-fill text-white fs-6"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="content-scroll">
                <div class="container-fluid p-0">

                    <div class="card border-0 shadow-sm overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-custom mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 35%;">Buku</th>
                                        <th>Kode Buku</th>
                                        <th>Tgl Pinjam</th>
                                        <th>Tgl Kembali</th>
                                        <th>Status</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($result) > 0) {
                                        while ($t = mysqli_fetch_assoc($result)):

                                            // --- LOGIKA DENDA (HYBRID) ---
                                            $display_status = $t['status'];
                                            $db_denda = (int)$t['denda'];

                                            $is_late = false;
                                            $final_fine_amount = 0;

                                            // Jika status DIPINJAM, hitung real-time
                                            if ($display_status == 'dipinjam' && !empty($t['tanggal_kembali'])) {
                                                $today_timestamp = time();
                                                $due_timestamp = strtotime($t['tanggal_kembali'] . ' 23:59:59');
                                                $selisih_detik = $today_timestamp - $due_timestamp;

                                                if ($selisih_detik > 0) {
                                                    $is_late = true;
                                                    $selisih_hari = ceil($selisih_detik / (60 * 60 * 24));
                                                    $final_fine_amount = $selisih_hari * $denda_per_hari;
                                                }
                                            }

                                            // Jika status KEMBALI, gunakan denda DB
                                            if ($display_status == 'kembali') {
                                                $final_fine_amount = $db_denda;
                                            }

                                            // Variabel badge denda history
                                            $history_denda_badge = '';
                                            if ($display_status == 'kembali' && $final_fine_amount > 0) {
                                                $history_denda_badge = '<div class="badge-fine-info mt-1">Denda: Rp ' . number_format($final_fine_amount) . '</div>';
                                            }

                                    ?>
                                            <tr id="row-<?= $t['id'] ?>">
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="bg-light rounded border overflow-hidden d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 55px; flex-shrink: 0;">
                                                            <?php if (!empty($t['gambar'])): ?>
                                                                <img src="../uploads/<?= $t['gambar'] ?>" class="w-100 h-100" style="object-fit: cover;" alt="<?= htmlspecialchars($t['judul']) ?>">
                                                            <?php else: ?>
                                                                <i class="bi bi-book text-muted" style="font-size: 1.5rem;"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div>
                                                            <p class="fw-bold text-dark mb-0" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($t['judul']) ?></p>
                                                            <small class="text-muted">ID: #<?= $t['id'] ?></small>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td>
                                                    <span class="bg-light border rounded px-2 py-1 text-secondary small fw-bold font-mono">
                                                        <?= $t['kode_spesifik'] ?: '-' ?>
                                                    </span>
                                                </td>

                                                <td class="text-secondary small"><?= $t['tanggal_pinjam'] ?></td>
                                                <td class="text-secondary small">
                                                    <?= $t['tanggal_kembali'] ?? '-' ?>
                                                    <?php if ($is_late): ?>
                                                        <div class="text-danger fw-bold small mt-1">Telat <?= $selisih_hari ?> hari</div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <!-- LABEL STATUS -->
                                                    <?php if ($display_status == 'dipinjam' && !$is_late): ?>
                                                        <span class="status-badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25">Dipinjam</span>
                                                    <?php elseif ($display_status == 'dipinjam' && $is_late): ?>
                                                        <span class="status-badge bg-danger bg-opacity-10 text-dark border border-danger border-opacity-25 animate-pulse">Terlambat</span>
                                                    <?php elseif ($display_status == 'pending_pinjam'): ?>
                                                        <span class="status-badge bg-primary bg-opacity-10 text-dark border border-primary border-opacity-25">Menunggu Konfirmasi Pinjam</span>
                                                    <?php elseif ($display_status == 'pending_kembali'): ?>
                                                        <span class="status-badge bg-info bg-opacity-10 text-dark border border-info border-opacity-25">Menunggu Konfirmasi Kembali</span>
                                                    <?php elseif ($display_status == 'pending_payment'): ?>
                                                        <span class="status-badge bg-secondary bg-opacity-10 text-dark border border-secondary border-opacity-25">Verifikasi Bayar</span>
                                                    <?php else: ?>
                                                        <!-- SELESAI -->
                                                        <span class="status-badge bg-success bg-opacity-10 text-dark border border-success border-opacity-25">Selesai</span>
                                                        <?= $history_denda_badge ?>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- KOLOM AKSI -->
                                                <td class="text-end">
                                                    <?php if ($display_status == 'dipinjam' && !$is_late): ?>
                                                        <!-- TOMBOL KEMBALIKAN BARU -->
                                                        <button onclick="openReturnModal(<?= $t['id'] ?>, '<?= htmlspecialchars($t['judul']) ?>')" class="btn btn-primary btn-sm fw-bold">
                                                            <i class="bi bi-box-arrow-left"></i> Kembalikan
                                                        </button>

                                                    <?php elseif ($display_status == 'dipinjam' && $is_late): ?>
                                                        <!-- JIKA TERLAMBAT, TAMPILKAN DENDA -->
                                                        <div class="d-flex flex-column align-items-end gap-1">
                                                            <span class="text-danger fw-bold small">Denda: Rp <?= number_format($final_fine_amount) ?></span>
                                                            <button onclick="openFineModal(<?= $t['id'] ?>, <?= $final_fine_amount ?>)" class="btn btn-danger btn-sm fw-bold d-flex align-items-center gap-1">
                                                                <i class="bi bi-currency-dollar"></i> Bayar Denda
                                                            </button>
                                                        </div>

                                                    <?php elseif ($display_status == 'pending_pinjam' || $display_status == 'pending_kembali' || $display_status == 'pending_payment'): ?>
                                                        <!-- MENUNGGU ADMIN -->
                                                        <span class="text-muted small fst-italic d-flex align-items-center justify-content-end gap-1">
                                                            <i class="bi bi-arrow-clockwise"></i> Menunggu Admin...
                                                        </span>

                                                    <?php else: ?>
                                                        <!-- SELESAI -->
                                                        <span class="text-success small d-flex align-items-center justify-content-end gap-1">
                                                            <i class="bi bi-check-circle-fill"></i> Selesai
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile;
                                    } else { ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="bi bi-book display-4 text-muted d-block mb-3"></i>
                                                <p>Anda belum pernah meminjam buku.</p>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- PAGINATION -->
                        <?php if ($total_pages > 1): ?>
                            <div class="d-flex align-items-center justify-content-between p-3 bg-light border-top">
                                <div class="small text-muted">
                                    Menampilkan <span class="fw-bold text-dark"><?= $start + 1 ?></span> sampai <span class="fw-bold text-dark"><?= min($start + $limit, $total_data) ?></span> dari <span class="fw-bold text-dark"><?= $total_data ?></span> data
                                </div>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination pagination-sm mb-0">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link border" href="?page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i> Prev</a>
                                            </li>
                                        <?php endif; ?>

                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                                <a class="page-link border <?= ($i == $page) ? 'bg-primary text-white border-primary' : '' ?>" href="?page=<?= $i ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link border" href="?page=<?= $page + 1 ?>">Next <i class="bi bi-chevron-right"></i></a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- MODAL POP-UP DENDA (LAMA) -->
    <div class="modal fade" id="modalDenda" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="w-100 text-center">
                        <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-receipt fs-2"></i>
                        </div>
                        <h5 class="modal-title fw-bold">Konfirmasi Pembayaran Denda</h5>
                    </div>
                </div>
                <div class="modal-body text-center pt-0">
                    <p class="text-muted small">
                        Anda terlambat mengembalikan buku. <br>
                        Total denda yang harus dibayarkan:
                    </p>
                    <div class="h2 text-danger fw-bold mb-3" id="modalDendaAmount">Rp 0</div>

                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" id="btnConfirmFine" onclick="processFinePayment()" class="btn btn-danger fw-bold d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill"></i> Ya, Bayar Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL KONFIRMASI KEMBALIKAN BARU -->
    <div class="modal fade" id="modalKembali" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="w-100 text-center">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-box-arrow-in-left fs-2"></i>
                        </div>
                        <h5 class="modal-title fw-bold">Ajukan Pengembalian?</h5>
                    </div>
                </div>
                <div class="modal-body text-center pt-0">
                    <p class="text-muted">
                        Apakah Anda yakin ingin mengembalikan buku:<br>
                        <strong id="modalBukuTitle" class="text-dark">Judul Buku</strong>?
                    </p>
                    <p class="small text-muted">Status akan berubah menjadi "Menunggu Konfirmasi Admin".</p>

                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="button" id="btnConfirmReturn" onclick="processReturn()" class="btn btn-primary fw-bold">
                            Ya, Ajukan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BOOTSTRAP 5 JS -->
    <script src="../asset/js/bootstrap.bundle.min.js"></script>

    <!-- SCRIPT JAM & AJAX -->
    <script>
        let currentFineId = null;
        let currentReturnId = null;
        let dendaModal;
        let kembaliModal;

        document.addEventListener('DOMContentLoaded', function() {
            dendaModal = new bootstrap.Modal(document.getElementById('modalDenda'));
            kembaliModal = new bootstrap.Modal(document.getElementById('modalKembali'));
        });

        // 1. Jam Real-time
        function updateClock() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            const clockElement = document.getElementById('live-clock');
            if (clockElement) clockElement.innerText = now.toLocaleDateString('id-ID', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // 2. Buka Modal Denda (LAMA)
        function openFineModal(id, amount) {
            currentFineId = id;
            document.getElementById('modalDendaAmount').innerText = 'Rp ' + amount.toLocaleString('id-ID');
            dendaModal.show();
        }

        // 3. Buka Modal Kembalikan Buku (BARU)
        function openReturnModal(id, judul) {
            currentReturnId = id;
            document.getElementById('modalBukuTitle').innerText = judul;
            kembaliModal.show();
        }

        // 4. Proses Bayar Denda
        function processFinePayment() {
            if (!currentFineId) return;

            const btnConfirm = document.getElementById('btnConfirmFine');
            const originalText = `<i class="bi bi-check-circle-fill"></i> Ya, Bayar Sekarang`;

            btnConfirm.disabled = true;
            btnConfirm.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Memproses...`;

            const formData = new FormData();
            formData.append('id', currentFineId);
            formData.append('pay_fine', 'true');

            fetch('return_buku.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        dendaModal.hide();
                        alert('Berhasil! Pembayaran denda dikirim ke admin.');
                        window.location.reload();
                    } else {
                        btnConfirm.disabled = false;
                        btnConfirm.innerHTML = originalText;
                        alert('Gagal: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error(error);
                    btnConfirm.disabled = false;
                    btnConfirm.innerHTML = originalText;
                });
        }

        // 5. Proses Ajukan Kembali (BARU)
        function processReturn() {
            if (!currentReturnId) return;

            const btnConfirm = document.getElementById('btnConfirmReturn');
            const originalText = "Ya, Ajukan";

            btnConfirm.disabled = true;
            btnConfirm.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Mengajukan...`;

            const formData = new FormData();
            formData.append('id_transaksi', currentReturnId);

            // Pastikan file proses_kembali.php sudah dibuat
            fetch('proses_kembali.php', { 
                method: 'POST', 
                body: formData,
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    kembaliModal.hide();
                    alert('Berhasil! Menunggu konfirmasi admin.');
                    window.location.reload();
                } else {
                    btnConfirm.disabled = false;
                    btnConfirm.innerHTML = originalText;
                    alert('Gagal: ' + data.message);
                }
            })
            .catch(error => {
                console.error(error);
                btnConfirm.disabled = false;
                btnConfirm.innerHTML = originalText;
                alert('Terjadi kesalahan sistem.');
            });
        }
    </script>
</body>

</html>