<?php
session_start();

// Kalau sudah login, lempar ke dashboard
if (isset($_SESSION['login'])) {
    header("Location: dashboard.php");
    exit;
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Registrasi SIPERDI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- BOOTSTRAP 5 CSS (OFFLINE) -->
    <link href="../asset/css/bootstrap.min.css" rel="stylesheet">

    <!-- BOOTSTRAP ICONS (OFFLINE) -->
    <link rel="stylesheet" href="../asset/font/bootstrap-icons.css">

    <style>
        :root {
            --bs-primary: #0d6efd;
            --bg-soft: #f3f4f6;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-soft);
            margin: 0;
            min-height: 100vh;
            overflow: hidden;
            /* Mencegah scroll body */
        }

        /* Wrapper menutupi seluruh layar */
        .register-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Register Card - KOMPAK (Sama Tingginya dengan Login) */
        .register-card {
            background: rgba(255, 255, 255, 1);
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 500px;
            /* Lebar sedikit diperlebar agar muat 2 kolom */

            /* Fix tinggi agar tidak scroll */
            display: flex;
            flex-direction: column;
            padding: 2.5rem;
            overflow: hidden;
            /* Mencegah scroll di dalam kartu */
        }

        /* Custom Input Group */
        .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            border-right: none;
            border-radius: 6px 0 0 6px;
            color: #6c757d;
        }

        .form-control,
        .form-select {
            padding: 0.5rem 0.75rem;
            /* Padding disesuaikan agar muat */
            border-radius: 0 6px 6px 0;
            border: 1px solid #ced4da;
            font-size: 0.9rem;
            /* Font sedikit diperkecil */
            height: auto;
            /* Tinggi mengikuti padding */
        }

        .form-control:focus,
        .form-select:focus {
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
            border-color: #0d6efd;
        }

        /* Effect fokus icon */
        .form-control:focus+.input-group-text,
        .input-group:focus-within .input-group-text {
            border-color: #0d6efd;
            background-color: #eef5ff;
        }

        .form-label {
            margin-bottom: 0.25rem;
            font-size: 0.75rem;
            /* Label lebih kecil agar hemat tempat */
        }

        /* Layout Grid Form */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            /* 2 Kolom Sama Rata */
            gap: 10px;
            /* Jarak antar kolom */
        }

        /* Kolom Penuh untuk Alamat & Button */
        .col-full {
            grid-column: span 2;
        }

        /* Mengurangi margin antar group form agar pas satu layar */
        .mb-custom {
            margin-bottom: 0.8rem !important;
        }
    </style>
</head>

<body>

    <div class="register-wrapper">

        <!-- Register Card -->
        <div class="register-card">

            <!-- Header (Dikecilkan sedikit marginnya) -->
            <div class="text-center mb-3">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-2 bg-light" style="width: 90px; height: 90px;">
                    <!-- Logo disesuaikan sedikit lebih kecil agar proporsional dengan form grid -->
                    <img src="../asset/img/logo.png" alt="Logo" style="width: 65%; height: auto; object-fit: contain;">
                </div>

                <h5 class="fw-bold mb-0" style="color: #0f172a; font-size: 1.1rem;">SIPERDI</h5>
                <p class="text-muted small mb-0" style="font-size: 0.75rem;">Sistem Informasi Perpustakaan Digital</p>
            </div>

            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="alert alert-danger border-0 mb-2 p-2 text-center" style="background-color: #f8d7da; color: #842029; font-size: 0.8rem;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form action="proses_register.php" method="POST">

                <!-- GRID CONTAINER -->
                <div class="form-grid">

                    <!-- KOLOM KIRI: Username -->
                    <div class="mb-custom">
                        <label class="form-label fw-bold text-secondary">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                            <input type="text" name="username" class="form-control" placeholder="Username" required autofocus>
                        </div>
                    </div>

                    <!-- KOLOM KANAN: Password -->
                    <div class="mb-custom">
                        <label class="form-label fw-bold text-secondary">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="Password" required minlength="2">
                        </div>
                    </div>

                    <!-- KOLOM KIRI: Tanggal Lahir -->
                    <div class="mb-custom">
                        <label class="form-label fw-bold text-secondary">Tgl Lahir</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                            <input type="date" name="tanggal_lahir" class="form-control" required>
                        </div>
                    </div>

                
                    <!-- KOLOM KANAN: Kelas -->
                    <div class="mb-custom">
                        <label class="form-label fw-bold text-secondary">Kelas</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-mortarboard-fill"></i></span>
                            <input type="text" name="kelas" class="form-control" placeholder="Contoh: XII RPL 1" required>
                        </div>
                    </div>

                    <!-- ALAMAT: MEMBUKA 2 KOLOM (Full Width) -->
                    <div class="mb-custom col-full">
                        <label class="form-label fw-bold text-secondary">Alamat</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-geo-alt-fill"></i></span>
                            <!-- Rows disesuaikan jadi 1 saja agar pas -->
                            <textarea name="alamat" class="form-control" rows="1" placeholder="Alamat Lengkap" required style="resize: none; height: 38px;"></textarea>
                        </div>
                    </div>

                    <!-- TOMBOL: MEMBUKA 2 KOLOM -->
                    <div class="col-full">
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm" style="font-size: 0.9rem;">
                            <i class="bi bi-check-circle me-1"></i> Daftar
                        </button>
                    </div>

                </div>
            </form>

            <!-- Footer -->
            <div class="text-center mt-2">
                <p class="text-muted small mb-0" style="font-size: 0.75rem;">
                    Sudah punya akun? <a href="login.php" class="fw-bold text-primary text-decoration-none">Login</a>
                </p>
            </div>

        </div>
    </div>

    <!-- BOOTSTRAP 5 JS -->
    <script src="../script/bootstrap.bundle.min.js"></script>

</body>

</html>