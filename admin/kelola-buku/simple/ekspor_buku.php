<?php
// PENTING: Jangan ada spasi atau karakter apapun sebelum <?php

// Start output buffering
ob_start();

session_start();
// Load library SimpleXLSXGen
require __DIR__ . '/../../src/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSXGen;

// Load koneksi database
include __DIR__ . "/../../config/database.php";

// Cek akses admin
if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
    ob_end_clean();
    die("Akses ditolak");
}

// Query untuk mengambil semua data buku
 $query = "SELECT buku.*, detail_buku.kode_buku as prefix_kode, detail_buku.kategori, detail_buku.deskripsi, detail_buku.gambar
          FROM buku
          LEFT JOIN detail_buku ON buku.id = detail_buku.id_buku
          ORDER BY buku.id ASC";
 $result = mysqli_query($conn, $query);

if (!$result) {
    ob_end_clean();
    die("Error query: " . mysqli_error($conn));
}

/* =====================================================
   SIAPKAN DATA UNTUK EXCEL
===================================================== */
 $data = [];

// 1. Buat Header
// Kolom Gambar diisi nama file karena SimpleXLSXGen tidak support insert gambar
 $header = [
    'No', 
    'Nama File Gambar', 
    'Judul Buku', 
    'Penulis', 
    'Penerbit', 
    'Tahun', 
    'Stok', 
    'Kategori', 
    'Prefix Kode', 
    'Deskripsi'
];

 $data[] = $header;

// 2. Ambil Data dari Database
 $no = 1;
while ($row = mysqli_fetch_assoc($result)) {
    
    $rowData = [
        $no++,
        $row['gambar'] ?: '-', 
        $row['judul'],
        $row['penulis'],
        $row['penerbit'],
        $row['tahun'],
        $row['stok'],
        $row['kategori'] ?: 'Umum',
        $row['prefix_kode'] ?: '',
        $row['deskripsi'] ?: ''
    ];

    $data[] = $rowData;
}

/* =====================================================
   GENERATE EXCEL
===================================================== */
try {
    // Buat object SimpleXLSXGen dari data array
    $xlsx = SimpleXLSXGen::fromArray($data);

    // Nama File
    $filename = 'Data_Buku_' . date('Y-m-d_His') . '.xlsx';

    // Bersihkan output buffer
    ob_end_clean();

    // Download File
    $xlsx->downloadAs($filename);

} catch (Exception $e) {
    die('Gagal generate Excel: ' . $e->getMessage());
}

exit;
?>