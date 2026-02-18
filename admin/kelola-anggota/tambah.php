<?php
session_start();
include __DIR__ . "/../../config/database.php";

// Cek login admin
if(!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin'){
    die("Akses ditolak");
}

if(isset($_POST['tambah'])){
    // Ambil dan sanitasi data input
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Data baru
    $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $tanggal_lahir = $_POST['tanggal_lahir']; // Format YYYY-MM-DD dari input type date
    $level = mysqli_real_escape_string($conn, $_POST['level']);
    $status = mysqli_real_escape_string($conn, $_POST['status']); // TAMBAHKAN INI

    // Cek username sudah ada atau belum
    $cek = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    if(mysqli_num_rows($cek) > 0){
        $_SESSION['error'] = "Username sudah digunakan!";
        header("Location: anggota.php");
        exit;
    }

    // Query Insert dengan kolom status
    $query = "INSERT INTO users (username, password, kelas, alamat, tanggal_lahir, level, status) 
              VALUES ('$username', '$password', '$kelas', '$alamat', '$tanggal_lahir', '$level', '$status')";
    
    if(mysqli_query($conn, $query)){
        $_SESSION['success'] = "Anggota berhasil ditambahkan dengan status $status.";
        header("Location: anggota.php");
        exit;
    } else {
        // Jika terjadi error database
        $_SESSION['error'] = "Gagal menambahkan data: " . mysqli_error($conn);
        header("Location: anggota.php");
        exit;
    }
}

// Jika akses langsung tanpa submit form
header("Location: anggota.php");
exit;
?>