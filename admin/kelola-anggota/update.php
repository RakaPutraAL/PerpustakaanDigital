<?php
session_start();
include __DIR__ . "/../../config/database.php";

// Cek login admin
if(!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin'){
    die("Akses ditolak");
}

if(isset($_POST['id'])){
    $id = (int)$_POST['id'];
    $username_baru = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // Ambil data baru dari form
    $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $level = mysqli_real_escape_string($conn, $_POST['level']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // AMBIL USERNAME LAMA SEBELUM DIUPDATE
    $query_old = mysqli_query($conn, "SELECT username FROM users WHERE id=$id");
    $data_old = mysqli_fetch_assoc($query_old);
    $username_lama = $data_old['username'];

    // Cek username sudah ada di user lain
    $cek = mysqli_query($conn, "SELECT * FROM users WHERE username='$username_baru' AND id!=$id");
    if(mysqli_num_rows($cek) > 0){
        $_SESSION['error'] = "Username sudah digunakan!";
        header("Location: anggota.php");
        exit;
    }

    // Query Dasar (tanpa password dulu)
    $query = "UPDATE users SET 
              username='$username_baru', 
              kelas='$kelas', 
              alamat='$alamat', 
              tanggal_lahir='$tanggal_lahir', 
              level='$level',
              status='$status'
              WHERE id=$id";

    // Cek apakah password diisi
    if(!empty($password)){
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $query = "UPDATE users SET 
                  username='$username_baru', 
                  password='$password_hash', 
                  kelas='$kelas', 
                  alamat='$alamat', 
                  tanggal_lahir='$tanggal_lahir', 
                  level='$level',
                  status='$status'
                  WHERE id=$id";
    }

    // Eksekusi Query Update User
    if(mysqli_query($conn, $query)){
        
        // UPDATE NAMA_PEMINJAM DI TABEL TRANSAKSI
        if($username_lama !== $username_baru){
            $update_transaksi = mysqli_query($conn, 
                "UPDATE transaksi 
                 SET nama_peminjam='$username_baru' 
                 WHERE nama_peminjam='$username_lama'"
            );
            
            if($update_transaksi){
                $_SESSION['success'] = "Data anggota dan riwayat transaksi berhasil diperbarui.";
            } else {
                $_SESSION['success'] = "Data anggota berhasil diperbarui, tetapi ada masalah saat update transaksi.";
            }
        } else {
            $_SESSION['success'] = "Data anggota berhasil diperbarui.";
        }
        
    } else {
        $_SESSION['error'] = "Gagal memperbarui data: " . mysqli_error($conn);
    }

    header("Location: anggota.php");
    exit;
}

// Jika akses langsung tanpa submit
header("Location: anggota.php");
exit;
?>