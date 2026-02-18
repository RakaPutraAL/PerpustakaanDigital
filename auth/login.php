<?php
session_start();

// Kalau sudah login, langsung lempar ke dashboard sesuai level
if (isset($_SESSION['login'])) {
  if ($_SESSION['level'] === 'admin') {
    header("Location: ../admin/kelola-buku/dashboard.php");
  } else {
    header("Location: dashboard.php");
  }
  exit;
}

// Ambil pesan error & success
 $error   = $_SESSION['error'] ?? '';
 $success = $_SESSION['success'] ?? '';

unset($_SESSION['error']);
unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Login SIPERDI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- BOOTSTRAP 5 CSS (OFFLINE) -->
  <link href="../asset/css/bootstrap.min.css" rel="stylesheet">

  <!-- BOOTSTRAP ICONS (OFFLINE) -->
  <link rel="stylesheet" href="../asset/font/bootstrap-icons.css">

  <style>
    :root {
      --bs-primary: #0d6efd;
      /* Biru Utama */
      --bg-soft: #f3f4f6;
      /* Background lembut */
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: var(--bg-soft);
    }

    /* Background Login: Putih Bersih */
    .login-wrapper {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #ffffff;
      position: relative;
    }

    /* Card Login: Kompak & Rapi */
    .login-card {
      background: rgba(255, 255, 255, 1);
      border: 1px solid #e5e7eb;
      border-radius: 12px; /* Sedikit lebih tumpul agar rapi */
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      position: relative;
      z-index: 1;
      padding: 2.5rem; /* Padding sedikit ditambah */
      width: 100%;
      max-width: 380px; /* Lebar disesuaikan agar proporsional */
    }

    /* Custom Input Group */
    .input-group-text {
      background-color: #f8f9fa;
      border: 1px solid #ced4da;
      border-right: none;
      border-radius: 6px 0 0 6px;
      color: #6c757d;
    }

    .form-control {
      padding: 0.6rem 1rem; /* Padding input disesuaikan */
      border-radius: 0 6px 6px 0;
      border: 1px solid #ced4da;
      font-size: 0.95rem;
    }

    .form-control:focus {
      box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
      border-color: #0d6efd;
    }

    /* Focus effect pada icon */
    .form-control:focus+.input-group-text,
    .input-group:focus-within .input-group-text {
      border-color: #0d6efd;
      background-color: #eef5ff;
    }

    /* Atur jarak antar elemen agar rapi */
    .form-label {
      margin-bottom: 0.35rem; /* Label lebih dekat ke input */
      font-size: 0.85rem;
    }
    
    .mb-3 {
      margin-bottom: 1rem !important;
    }
    
    .mb-4 {
      margin-bottom: 1rem !important;
    }
  </style>
</head>

<body>

  <div class="login-wrapper">

    <!-- Login Card -->
    <div class="login-card">

      <!-- Header -->
      <div class="text-center mb-4">
        <!-- LOGO DIPERBESAR -->
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 bg-light" style="width: 110px; height: 110px;">
          <!-- Logo ikut menyesuaikan (70% dari container) -->
          <img src="../asset/img/logo.png" alt="Logo" style="width: 70%; height: auto; object-fit: contain;">
        </div>

        <h4 class="fw-bold mb-1" style="font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #0f172a;">SIPERDI</h4>
        <p class="text-muted small mb-0">Sistem Informasi Perpustakaan Digital</p>
      </div>

      <!-- Success Message -->
      <?php if ($success): ?>
        <div class="alert alert-success border-0 mb-3 p-2 text-center" style="background-color: #d1e7dd; color: #0f5132; font-size: 0.9rem;">
          <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <!-- Error Message -->
      <?php if ($error): ?>
        <div class="alert alert-danger border-0 mb-3 p-2 text-center" style="background-color: #f8d7da; color: #842029; font-size: 0.9rem;">
          <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <!-- Form -->
      <form action="proses_login.php" method="POST">

        <!-- Username -->
        <div class="mb-3">
          <label class="form-label fw-bold text-secondary">Username</label>
          <div class="input-group">
            <span class="input-group-text">
              <i class="bi bi-person-fill"></i>
            </span>
            <input
              type="text"
              name="username"
              class="form-control"
              placeholder="Masukkan username"
              required
              autofocus>
          </div>
        </div>

        <!-- Password -->
        <div class="mb-4">
          <label class="form-label fw-bold text-secondary">Password</label>
          <div class="input-group">
            <span class="input-group-text">
              <i class="bi bi-lock-fill"></i>
            </span>
            <input
              type="password"
              name="password"
              class="form-control"
              placeholder="Masukkan password"
              required>
          </div>
        </div>

        <button
          type="submit"
          class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
          <i class="bi bi-box-arrow-in-right me-2"></i> Login
        </button>

      </form>

      <!-- Footer -->
      <div class="text-center mt-4">
        <p class="text-muted small mb-0">
          Belum punya akun?
          <a href="register.php" class="fw-bold text-primary text-decoration-none">Daftar di sini</a>
        </p>
      </div>

    </div>
  </div>

  <!-- BOOTSTRAP 5 JS (OFFLINE) -->
  <script src="../asset/js/bootstrap.bundle.min.js"></script>

</body>

</html>