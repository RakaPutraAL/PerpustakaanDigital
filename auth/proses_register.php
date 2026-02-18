<?php
session_start();
include "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Ambil data dari form dan bersihkan spasi
    $username  = trim($_POST['username']);
    $password  = $_POST['password'];
    $tgl_lahir = $_POST['tanggal_lahir'];
    $kelas     = $_POST['kelas'];
    $alamat    = $_POST['alamat'];

    // 2. Validasi dasar (Semua kolom wajib diisi)
    if ($username === '' || $password === '' || $tgl_lahir === '' || $kelas === '' || $alamat === '') {
        $_SESSION['error'] = "Semua kolom wajib diisi";
        header("Location: register.php");
        exit;
    }

    // 3. Cek apakah username sudah ada di database
    $cek = mysqli_query($conn, "SELECT id FROM users WHERE username='$username'");
    if (mysqli_num_rows($cek) > 0) {
        $_SESSION['error'] = "Username sudah digunakan";
        header("Location: register.php");
        exit;
    }

    // 4. Enkripsi password agar aman
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // 5. Simpan data ke database
    // PERUBAHAN: Saya menambahkan kolom 'status' dengan nilai 'pending' di urutan yang benar.
    // Pastikan urutan kolom (username, password, level, tanggal_lahir, kelas, alamat, status)
    // SAMA PERSIS dengan struktur tabel database Anda.
    $simpan = mysqli_query($conn, "
        INSERT INTO users (username, password, level, tanggal_lahir, kelas, alamat, status)
        VALUES ('$username', '$password_hash', 'user', '$tgl_lahir', '$kelas', '$alamat', 'pending')
    ");

    // 6. Cek keberhasilan query
    if ($simpan) {
        // PERUBAHAN: Pesan sukses diperbarui agar user tahu akun belum aktif
        $_SESSION['success'] = "Registrasi berhasil! Akun Anda dalam status Pending, Silakan tunggu Admin untuk mengaktifkan.";
        header("Location: login.php");
        exit;
    } else {
        // Jika gagal, tampilkan error dari MySQL (untuk debugging)
        $_SESSION['error'] = "Registrasi gagal, terjadi kesalahan sistem.";
        // Uncomment baris di bawah ini untuk melihat error MySQL jika perlu
        // $_SESSION['error'] = "Registrasi gagal: " . mysqli_error($conn); 
        header("Location: register.php");
        exit;
    }
} else {
    // Jika file ini diakses tidak lewat form POST
    header("Location: register.php");
    exit;
}
?>