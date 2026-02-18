<?php
session_start();
include "../../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
    die("Akses ditolak");
}

$id = $_GET['id'];
mysqli_query($conn, "DELETE FROM buku WHERE id='$id'");

header("Location: kelola_buku.php");
exit;
