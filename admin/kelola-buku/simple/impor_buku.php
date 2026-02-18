<?php
session_start();
// Load library SimpleXLSX
require __DIR__ . '/../../src/SimpleXLSX.php';

// Load database
include __DIR__ . "/../../config/database.php";

use Shuchkin\SimpleXLSX;

// Cek akses admin
if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
    die("Akses ditolak");
}

if (isset($_POST['import'])) {
    // Validasi upload file
    if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] != 0) {
        $_SESSION['error'] = "File tidak valid atau gagal diupload.";
        header("Location: kelola_buku.php");
        exit;
    }

    $file_tmp = $_FILES['file_excel']['tmp_name'];
    $file_name = $_FILES['file_excel']['name'];
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Validasi ekstensi file
    if ($ext !== 'xlsx') {
        $_SESSION['error'] = "Format file harus Excel (.xlsx)";
        header("Location: kelola_buku.php");
        exit;
    }

    try {
        // Initialize rows variable
        $rows = [];
        
        // Load Excel menggunakan SimpleXLSX
        if ($xlsx = SimpleXLSX::parse($file_tmp)) {
            // Ambil semua baris
            /** @var array<int, array<int, mixed>> $rows */
            $rows = $xlsx->rows();
        } else {
            $_SESSION['error'] = "Gagal membaca file Excel: " . SimpleXLSX::parseError();
            header("Location: kelola_buku.php");
            exit;
        }

        // Validasi: Cek apakah ada data (minimal header)
        if (count($rows) < 1) {
            $_SESSION['error'] = "File Excel kosong.";
            header("Location: kelola_buku.php");
            exit;
        }

        // Hapus Header (baris pertama)
        array_shift($rows);

        $success_count = 0;
        $error_count = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $row_number = $index + 2; // +2 karena index 0 dan header di baris 1
            
            // Ensure $row is an array
            if (!is_array($row)) {
                continue;
            }
            
            // Skip baris kosong
            if (empty(array_filter($row))) {
                continue;
            }

            // MAPPING KOLOM (Sesuai Template)
            // [0] No, [1] Gambar, [2] Judul, [3] Penulis, [4] Penerbit, 
            // [5] Tahun, [6] Stok, [7] Kategori, [8] Prefix, [9] Deskripsi, [10] NamaFileGambar
            
            // Validasi kolom wajib
            if (empty($row[2]) || empty($row[3]) || empty($row[4]) || empty($row[5]) || empty($row[6])) {
                $errors[] = "Baris $row_number: Data wajib tidak lengkap (Judul/Penulis/Penerbit/Tahun/Stok)";
                $error_count++;
                continue;
            }

            // Ambil dan Escape Data
            $judul = mysqli_real_escape_string($conn, trim($row[2]));
            $penulis = mysqli_real_escape_string($conn, trim($row[3]));
            $penerbit = mysqli_real_escape_string($conn, trim($row[4]));
            $tahun = (int)$row[5];
            $stok = (int)$row[6];
            $kategori = mysqli_real_escape_string($conn, trim($row[7] ?? 'Umum'));
            $prefix_kode = mysqli_real_escape_string($conn, trim($row[8] ?? ''));
            $deskripsi = mysqli_real_escape_string($conn, trim($row[9] ?? ''));
            $nama_file_gambar = mysqli_real_escape_string($conn, trim($row[10] ?? ''));

            // Validasi tahun dan stok
            if ($tahun < 1900 || $tahun > 2100) {
                $errors[] = "Baris $row_number: Tahun tidak valid ($tahun)";
                $error_count++;
                continue;
            }

            if ($stok < 0) {
                $errors[] = "Baris $row_number: Stok tidak boleh negatif";
                $error_count++;
                continue;
            }

            // Validasi gambar jika ada nama file (Cek apakah file benar-benar ada di folder)
            if (!empty($nama_file_gambar)) {
                $gambar_path = __DIR__ . '/../../uploads/' . $nama_file_gambar;
                if (!file_exists($gambar_path)) {
                    $errors[] = "Baris $row_number: File gambar '$nama_file_gambar' tidak ditemukan di folder uploads";
                    $error_count++;
                    continue;
                }
            }

            // Insert ke tabel buku
            $query_buku = "INSERT INTO buku (judul, penulis, penerbit, tahun, stok) 
                          VALUES ('$judul', '$penulis', '$penerbit', $tahun, $stok)";
            
            if (mysqli_query($conn, $query_buku)) {
                $id_buku = mysqli_insert_id($conn);

                // Insert ke detail_buku
                $query_detail = "INSERT INTO detail_buku (id_buku, kode_buku, kategori, deskripsi, gambar) 
                                VALUES ($id_buku, '$prefix_kode', '$kategori', '$deskripsi', '$nama_file_gambar')";
                
                if (mysqli_query($conn, $query_detail)) {
                    $success_count++;
                } else {
                    $errors[] = "Baris $row_number: Gagal insert detail - " . mysqli_error($conn);
                    $error_count++;
                    // Rollback insert buku jika detail gagal
                    mysqli_query($conn, "DELETE FROM buku WHERE id = $id_buku");
                }
            } else {
                $errors[] = "Baris $row_number: Gagal insert buku - " . mysqli_error($conn);
                $error_count++;
            }
        }

        // Set pesan Session
        if ($success_count > 0) {
            $_SESSION['success'] = "Berhasil import $success_count buku!";
        }
        if ($error_count > 0) {
            // Tampilkan maksimal 3 error pertama agar pesan tidak terlalu panjang
            $error_msg = implode('; ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $error_msg .= "... dan lainnya.";
            }
            $_SESSION['error'] = "Gagal import $error_count buku. Detail: $error_msg";
        }

    } catch (Exception $e) {
        $_SESSION['error'] = "Error sistem: " . $e->getMessage();
    }

    header("Location: kelola_buku.php");
    exit;
}
?>