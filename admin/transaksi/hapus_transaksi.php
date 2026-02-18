<?php
session_start();
include __DIR__ . "/../../config/database.php";
if (!isset($_SESSION['login']) || $_SESSION['level']!=='admin') die("Akses ditolak");

if(isset($_GET['id'])){
    $id = (int)$_GET['id'];
    $trans = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM transaksi WHERE id=$id"));

    // Kembalikan stok jika buku masih dipinjam
    if($trans['status']=='dipinjam'){
        mysqli_query($conn,"UPDATE buku SET stok = stok + 1 WHERE id=".$trans['id_buku']);
    }

    mysqli_query($conn,"DELETE FROM transaksi WHERE id=$id");
}
header("Location: transaksi.php"); exit;
