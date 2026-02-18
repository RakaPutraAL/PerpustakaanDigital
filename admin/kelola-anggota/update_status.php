<?php
session_start();
include "../../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
    die("Akses ditolak");
}

 $id = $_GET['id'];
 $status = $_GET['status']; // Bisa 'aktif' atau 'pending'

mysqli_query($conn, "UPDATE users SET status = '$status' WHERE id = '$id'");

 $_SESSION['success'] = "Status akun berhasil diperbarui.";
header("Location: anggota.php");
exit;
?>