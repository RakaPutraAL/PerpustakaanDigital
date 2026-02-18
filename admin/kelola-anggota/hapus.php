<?php
session_start();
include __DIR__ . "/../../config/database.php";

// Cek login admin
if(!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin'){
    die("Akses ditolak");
}

if(isset($_GET['id'])){
    $id = (int)$_GET['id'];
    
    // Ambil username dari ID
    $query_user = mysqli_query($conn, "SELECT username FROM users WHERE id = $id");
    $data_user = mysqli_fetch_assoc($query_user);
    
    if(!$data_user){
        $_SESSION['error'] = "Anggota tidak ditemukan!";
        header("Location: anggota.php");
        exit;
    }
    
    $username = mysqli_real_escape_string($conn, $data_user['username']);
    
    // CEK APAKAH ADA TRANSAKSI AKTIF (yang belum selesai)
    $cek_aktif = mysqli_query($conn, 
        "SELECT COUNT(*) as total FROM transaksi 
         WHERE nama_peminjam = '$username' 
         AND status IN ('dipinjam', 'pending_pinjam', 'pending_kembali', 'pending_payment')"
    );
    $data_aktif = mysqli_fetch_assoc($cek_aktif);
    
    // JANGAN HAPUS JIKA MASIH ADA TRANSAKSI AKTIF
    if($data_aktif['total'] > 0){
        $_SESSION['error'] = "Tidak bisa menghapus anggota '$username'! Anggota ini masih memiliki {$data_aktif['total']} transaksi aktif. Selesaikan transaksi terlebih dahulu.";
        header("Location: anggota.php");
        exit;
    }
    
    // HAPUS SEMUA RIWAYAT TRANSAKSI (yang sudah selesai)
    mysqli_query($conn, "DELETE FROM transaksi WHERE nama_peminjam = '$username'");
    
    // HAPUS ANGGOTA
    $delete = mysqli_query($conn, "DELETE FROM users WHERE id = $id");
    
    if($delete){
        $_SESSION['success'] = "Anggota '$username' dan riwayat transaksinya berhasil dihapus.";
    } else {
        $_SESSION['error'] = "Gagal menghapus anggota: " . mysqli_error($conn);
    }
    
    header("Location: anggota.php");
    exit;
}

header("Location: anggota.php");
exit;
?>