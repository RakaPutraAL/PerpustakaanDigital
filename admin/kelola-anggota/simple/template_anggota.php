<?php
session_start();

// ================= CEK AKSES =================
if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
    die("Akses ditolak");
}

// Bersihkan output buffer
if (ob_get_level()) {
    ob_end_clean();
}

// Load library
require __DIR__ . '/../../src/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSXGen;

/* =====================================================
   DATA TEMPLATE
===================================================== */

// Sheet 1: Template Import Anggota (TANPA KOLOM STATUS)
$template = [
    ['No', 'Username', 'Password', 'Kelas', 'Alamat', 'Tanggal Lahir', 'Level'],
    [1, 'john_doe', 'pass123', 'XII RPL 1', 'Jl. Merdeka No. 10', '2005-05-15', 'user'],
    [2, 'jane_smith', 'pass456', 'XII TKJ 2', 'Jl. Sudirman No. 20', '2005-08-20', 'user'],
    [3, 'admin_user', 'admin123', '-', 'Jl. Admin No. 1', '1990-01-01', 'admin']
];

// Sheet 2: Keterangan
$keterangan = [
    ['TEMPLATE IMPORT ANGGOTA'],
    [''],
    ['Kolom', 'Keterangan'],
    ['No', 'Nomor urut (opsional, hanya untuk memudahkan)'],
    ['Username', 'Username login (WAJIB, harus unik)'],
    ['Password', 'Password login (WAJIB)'],
    ['Kelas', 'Kelas siswa (opsional, bisa dikosongkan atau isi -)'],
    ['Alamat', 'Alamat lengkap (opsional)'],
    ['Tanggal Lahir', 'Format: YYYY-MM-DD atau DD/MM/YYYY (opsional)'],
    ['Level', 'Pilihan: admin atau user (default: user)'],
    [''],
    ['CATATAN PENTING:'],
    ['1', 'Username dan Password tidak boleh kosong'],
    ['2', 'Username harus unik (tidak boleh sama dengan yang sudah ada)'],
    ['3', 'Format tanggal: YYYY-MM-DD (contoh: 2005-05-15) atau DD/MM/YYYY (15/05/2005)'],
    ['4', 'Level selain "admin" akan otomatis menjadi "user"'],
    ['5', 'Status anggota OTOMATIS "aktif" saat diimport oleh admin'],
    ['6', 'HAPUS SEMUA BARIS CONTOH (baris 2-4) sebelum mengisi data Anda'],
    ['7', 'Kolom A (No) boleh dikosongkan'],
    [''],
    ['CONTOH PENGISIAN:'],
    ['No', 'Username', 'Password', 'Kelas', 'Alamat', 'Tgl Lahir', 'Level'],
    ['1', 'siswa001', 'pass123', 'XII RPL 1', 'Jl. Merdeka 1', '2005-01-15', 'user'],
    ['2', 'siswa002', 'pass456', 'XII TKJ 1', 'Jl. Sudirman 2', '2005-02-20', 'user'],
    ['3', 'guru_andi', 'guru123', '-', 'Jl. Guru 10', '1985-05-10', 'admin']
];

/* =====================================================
   GENERATE EXCEL
===================================================== */

try {
    // 1. Buat object dengan SHEET PERTAMA
    $xlsx = SimpleXLSXGen::fromArray($template);
    
    // 2. Tambahkan SHEET KEDUA secara manual
    $xlsx->addSheet($keterangan, 'Keterangan');
    
    // Nama file
    $filename = 'Template_Import_Anggota_' . date('Ymd') . '.xlsx';
    
    // 3. Download file
    $xlsx->downloadAs($filename);
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

exit;
?>