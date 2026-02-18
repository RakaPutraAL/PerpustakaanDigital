<?php
session_start();
include __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['level']!=='admin'){
    die("Akses ditolak");
}

if(isset($_GET['id']) && isset($_GET['status'])){
    $id = (int)$_GET['id'];
    $status = strtolower($_GET['status']); // pastikan lowercase

    // validasi ENUM
    $valid_status = ['dipinjam','kembali'];
    if(!in_array($status, $valid_status)){
        die("Status tidak valid");
    }

    // ambil data transaksi
    $t = mysqli_query($conn,"SELECT * FROM transaksi WHERE id=$id");
    $trans = mysqli_fetch_assoc($t);

    // jika status dikembalikan (kembali), tambahkan stok buku
    if($status=='kembali' && $trans['status']=='dipinjam'){
        mysqli_query($conn,"UPDATE buku SET stok = stok + 1 WHERE id=".$trans['id_buku']);
    }

    // update status transaksi
    mysqli_query($conn,"UPDATE transaksi SET status='$status' WHERE id=$id");
    header("Location: transaksi.php");
    exit;
} else {
    header("Location: transaksi.php");
    exit;
}
