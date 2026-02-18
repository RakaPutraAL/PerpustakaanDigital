<?php
session_start();
include "../config/database.php";

// Ambil data
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validasi kosong
if ($username === '' || $password === '') {
    $_SESSION['error'] = "Username dan password wajib diisi";
    header("Location: login.php");
    exit;
}

// Cari user berdasarkan username
$query = mysqli_query($conn, "
    SELECT id, username, password, level, status
    FROM users
    WHERE username = '$username'
    LIMIT 1
");

if (mysqli_num_rows($query) === 0) {
    $_SESSION['error'] = "Username tidak ditemukan";
    header("Location: login.php");
    exit;
}

$data = mysqli_fetch_assoc($query);

// 1. Verifikasi Password
if (!password_verify($password, $data['password'])) {
    $_SESSION['error'] = "Password salah";
    header("Location: login.php");
    exit;
}

// 2. CEK STATUS AKUN
// SEMUA user (termasuk admin) harus memiliki status aktif
if ($data['status'] !== 'aktif') {
    $_SESSION['error'] = "Akun Anda belum aktif. Silakan hubungi Admin untuk konfirmasi.";
    header("Location: login.php");
    exit;
}

// ===== LOGIN BERHASIL =====
$_SESSION['login'] = true;
$_SESSION['id']    = $data['id'];
$_SESSION['level'] = $data['level'];

// Redirect sesuai level
if ($data['level'] === 'admin') {
    header("Location: ../admin/kelola-buku/dashboard.php");
} else {
    header("Location: ../user/dashboard.php");
}
exit;
?>