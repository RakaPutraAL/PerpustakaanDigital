<?php
// PENTING: Jangan ada spasi atau karakter apapun sebelum <?php

session_start();
// Load library SimpleXLSXGen
require __DIR__ . '/../../src/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSXGen;

// Cek akses admin
if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
    die("Akses ditolak");
}

/* =====================================================
   SHEET 1: TEMPLATE DATA
===================================================== */
// Data Header
 $header = [
    'No', 
    'Gambar', 
    'Judul Buku*', 
    'Penulis*', 
    'Penerbit*', 
    'Tahun*', 
    'Stok*', 
    'Kategori', 
    'Prefix Kode', 
    'Deskripsi', 
    'Nama File Gambar'
];

// Data Contoh
 $contoh = [
    [1, '(lihat kolom K)', 'Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', 2005, 10, 'Fiksi', 'LP-', 'Novel tentang perjuangan anak-anak Belitung', 'laskar_pelangi.jpg'],
    [2, '(lihat kolom K)', 'Bumi Manusia', 'Pramoedya Ananta Toer', 'Hasta Mitra', 1980, 8, 'Sejarah', 'BM-', 'Tetralogi Buru Jilid 1', 'bumi_manusia.jpg'],
];

// Gabungkan Header dan Contoh
 $template = [];
 $template[] = $header;
foreach ($contoh as $row) {
    $template[] = $row;
}

/* =====================================================
   SHEET 2: PANDUAN / INSTRUKSI
===================================================== */
// Kita buat sheet terpisah untuk instruksi karena SimpleXLSXGen tidak bisa merge cell
 $panduan = [
    ['PANDUAN IMPORT BUKU'],
    [''],
    ['1. Kolom dengan tanda * wajib diisi'],
    ['2. Hapus contoh data di baris 2-3 sebelum mengisi data Anda'],
    ['3. Format Tahun: Angka 4 digit (contoh: 2024)'],
    ['4. Format Stok: Angka positif (contoh: 10)'],
    ['5. Kategori: Boleh kosong (default: Umum)'],
    ['6. Prefix Kode: Misal LP-, BM-, SJ- (opsional)'],
    ['7. Nama File Gambar: Tulis nama file gambar yang sudah ada di folder uploads/'],
    ['   Contoh: laskar_pelangi.jpg, bumi_manusia.png (opsional, boleh kosong)'],
    ['8. Pastikan file gambar sudah diupload ke folder uploads/ sebelum import'],
    ['9. Kolom Gambar (B) tidak perlu diisi, hanya untuk referensi saat ekspor'],
    ['10. Simpan file dalam format .xlsx'],
    ['11. Upload file melalui menu Import di web']
];

/* =====================================================
   GENERATE EXCEL
===================================================== */
try {
    // 1. Buat Sheet pertama (Data)
    $xlsx = SimpleXLSXGen::fromArray($template);
    
    // 2. Tambahkan Sheet kedua (Panduan)
    $xlsx->addSheet($panduan, 'Panduan');
    
    // Nama File
    $filename = 'Template_Import_Buku.xlsx';
    
    // Download File
    $xlsx->downloadAs($filename);

} catch (Exception $e) {
    die('Gagal generate template: ' . $e->getMessage());
}

exit;
?>