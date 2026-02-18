<?php
session_start();
include __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
    die("Akses ditolak");
}

if (isset($_POST['pinjam'])) {
    // Ambil data dari form
    $id_buku = $_POST['id_buku'];
    $nama_peminjam = mysqli_real_escape_string($conn, $_POST['nama_peminjam']);
    $tanggal_pinjam = $_POST['tanggal_pinjam'];
    $tanggal_kembali = $_POST['tanggal_kembali'];
    
    // 1. Ambil Kode Spesifik (Hasil dari JS cek_kode_terkecil.php)
    $kode_spesifik = $_POST['kode_spesifik'];

    // Validasi: Pastikan kode terisi
    if (empty($kode_spesifik) || $kode_spesifik == '-') {
        echo "<script>alert('Gagal mengambil kode buku. Mohon pilih buku kembali.'); window.history.back();</script>";
        exit;
    }

    // Cek ketersediaan stok buku
    $cek_buku = mysqli_query($conn, "SELECT stok FROM buku WHERE id = '$id_buku'");
    $b = mysqli_fetch_assoc($cek_buku);

    if ($b['stok'] > 0) {
        // 2. Masukkan data transaksi (KODE SPESIFIK DITAMBAHKAN DISINI)
        $insert = mysqli_query($conn, "
            INSERT INTO transaksi (id_buku, nama_peminjam, tanggal_pinjam, tanggal_kembali, status, kode_spesifik)
            VALUES ('$id_buku', '$nama_peminjam', '$tanggal_pinjam', '$tanggal_kembali', 'dipinjam', '$kode_spesifik')
        ");

        if ($insert) {
            // Kurangi stok buku
            $update_stok = mysqli_query($conn, "UPDATE buku SET stok = stok - 1 WHERE id = '$id_buku'");
            
            // Set pesan sukses dengan kode buku
            $_SESSION['message'] = "Peminjaman berhasil. Kode Buku: $kode_spesifik";
            header("Location: transaksi.php");
            exit;
        } else {
            die("Gagal menyimpan transaksi: " . mysqli_error($conn));
        }
    } else {
        // Jika stok habis
        echo "<script>alert('Stok buku tidak mencukupi!'); window.history.back();</script>";
        exit;
    }
} else {
    header("Location: transaksi.php");
    exit;
}
?>