<?php
session_start();
require __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . "/../../config/database.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

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

    $file = $_FILES['file_excel']['tmp_name'];
    $allowed = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
    
    if (!in_array($_FILES['file_excel']['type'], $allowed)) {
        $_SESSION['error'] = "Format file harus Excel (.xlsx atau .xls)";
        header("Location: kelola_buku.php");
        exit;
    }

    try {
        // Load Excel file
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Skip header (baris pertama)
        array_shift($rows);

        $success_count = 0;
        $error_count = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $row_number = $index + 2; // +2 karena index 0 dan header di baris 1
            
            // Skip baris kosong
            if (empty(array_filter($row))) {
                continue;
            }

            // Validasi kolom minimal (Judul, Penulis, Penerbit, Tahun, Stok)
            // Kolom: No(0), Gambar(1), Judul(2), Penulis(3), Penerbit(4), Tahun(5), Stok(6), Kategori(7), Prefix(8), Deskripsi(9), NamaFileGambar(10)
            if (empty($row[2]) || empty($row[3]) || empty($row[4]) || empty($row[5]) || empty($row[6])) {
                $errors[] = "Baris $row_number: Data wajib tidak lengkap (Judul/Penulis/Penerbit/Tahun/Stok)";
                $error_count++;
                continue;
            }

            // Escape data
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
            if ($tahun < 1300 || $tahun > 2100) {
                $errors[] = "Baris $row_number: Tahun tidak valid ($tahun)";
                $error_count++;
                continue;
            }

            if ($stok < 0) {
                $errors[] = "Baris $row_number: Stok tidak boleh negatif";
                $error_count++;
                continue;
            }

            // Validasi gambar HANYA jika ada nama file yang diisi
            if (!empty($nama_file_gambar)) {
                $gambar_path = __DIR__ . '/../../uploads/' . $nama_file_gambar;
                if (!file_exists($gambar_path)) {
                    // Beri peringatan tapi tetap lanjutkan import tanpa gambar
                    $errors[] = "Baris $row_number: File gambar '$nama_file_gambar' tidak ditemukan, buku akan diimpor tanpa gambar";
                    $nama_file_gambar = ''; // Set kosong agar tidak error di database
                }
            }

            // Insert ke tabel buku
            $query_buku = "INSERT INTO buku (judul, penulis, penerbit, tahun, stok) 
                          VALUES ('$judul', '$penulis', '$penerbit', $tahun, $stok)";
            
            if (mysqli_query($conn, $query_buku)) {
                $id_buku = mysqli_insert_id($conn);

                // Insert ke detail_buku (gambar bisa kosong)
                $query_detail = "INSERT INTO detail_buku (id_buku, kode_buku, kategori, deskripsi, gambar) 
                                VALUES ($id_buku, '$prefix_kode', '$kategori', '$deskripsi', '$nama_file_gambar')";
                
                if (mysqli_query($conn, $query_detail)) {
                    $success_count++;
                } else {
                    $errors[] = "Baris $row_number: Gagal insert detail - " . mysqli_error($conn);
                    $error_count++;
                    // Rollback insert buku
                    mysqli_query($conn, "DELETE FROM buku WHERE id = $id_buku");
                }
            } else {
                $errors[] = "Baris $row_number: Gagal insert buku - " . mysqli_error($conn);
                $error_count++;
            }
        }

        // Set session message
        if ($success_count > 0) {
            $_SESSION['success'] = "Berhasil import $success_count buku!";
        }
        if ($error_count > 0) {
            $_SESSION['error'] = "Gagal import $error_count buku. " . (count($errors) > 0 ? implode('; ', array_slice($errors, 0, 3)) : '');
        }

        // Cleanup
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    header("Location: kelola_buku.php");
    exit;
}