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
        header("Location: anggota.php");
        exit;
    }

    $file = $_FILES['file_excel']['tmp_name'];
    $allowed = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
    
    if (!in_array($_FILES['file_excel']['type'], $allowed)) {
        $_SESSION['error'] = "Format file harus Excel (.xlsx atau .xls)";
        header("Location: anggota.php");
        exit;
    }

    try {
        // Load Excel file
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(); // Mengubah excel menjadi array biasa

        // Skip header (baris pertama)
        array_shift($rows);

        // Ambil semua username yang sudah ada
        $existing_users = [];
        $res_users = mysqli_query($conn, "SELECT username FROM users");
        while($u = mysqli_fetch_assoc($res_users)) {
            $existing_users[] = strtolower(trim($u['username']));
        }

        $success_count = 0;
        $error_count = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $row_number = $index + 2; 
            
            // Skip baris kosong total
            if (empty(array_filter($row))) {
                continue;
            }

            // Mapping: 0=No, 1=Username, 2=Password, 3=Kelas, 4=Alamat, 5=Tgl Lahir, 6=Level
            
            // 1. Ambil dan Bersihkan Data (Trim & Lowercase untuk Level)
            $username = trim($row[1] ?? '');
            $password = trim($row[2] ?? '');
            $kelas    = trim($row[3] ?? '');
            $alamat   = trim($row[4] ?? '');
            $raw_tgl  = $row[5];
            $raw_level = strtolower(trim($row[6] ?? ''));

            // 2. Validasi Data Wajib (Username & Password)
            if (empty($username) || empty($password)) {
                $errors[] = "Baris $row_number: Username atau Password kosong.";
                $error_count++;
                continue;
            }

            // 3. Cek Duplikat Username
            if (in_array(strtolower($username), $existing_users)) {
                $errors[] = "Baris $row_number: Username '$username' sudah ada.";
                $error_count++;
                continue;
            }

            // 4. Normalisasi Level (Flexible: Admin, admin, ADMIN -> admin)
            // Jika isinya bukan 'admin', otomatis jadi 'user'
            if ($raw_level == 'admin') {
                $level_final = 'admin';
            } else {
                // Selain admin (kosong, user, member, dsb) dianggap user
                $level_final = 'user';
            }

            // 5. Status OTOMATIS AKTIF saat import oleh admin
            $status_final = 'aktif';

            // 6. Handle Tanggal Lahir
            $tgl_final = NULL;
            if (!empty($raw_tgl)) {
                if ($raw_tgl instanceof DateTime) {
                    $tgl_final = $raw_tgl->format('Y-m-d');
                } else {
                    // Coba parse string tanggal manual
                    $time = strtotime($raw_tgl);
                    if ($time) {
                        $tgl_final = date('Y-m-d', $time);
                    } else {
                        // Jika format tanggal aneh, kita abaikan atau set null, agar tidak error total
                        $tgl_final = NULL; 
                    }
                }
            }

            // ========================================
            // 🔐 Hash Password
            // ========================================
            $u_esc = mysqli_real_escape_string($conn, $username);
            $p_hash = password_hash($password, PASSWORD_DEFAULT); // Hash password sebelum disimpan
            $k_esc = mysqli_real_escape_string($conn, $kelas);
            $a_esc = mysqli_real_escape_string($conn, $alamat);
            $t_esc = $tgl_final ? "'$tgl_final'" : "NULL";

            // 7. Query Insert dengan password yang sudah di-hash dan status otomatis aktif
            $query = "INSERT INTO users (username, password, kelas, alamat, tanggal_lahir, level, status) 
                      VALUES ('$u_esc', '$p_hash', '$k_esc', '$a_esc', $t_esc, '$level_final', '$status_final')";
            
            if (mysqli_query($conn, $query)) {
                $success_count++;
                // Tambahkan username ke list existing untuk cek duplikat baris berikutnya di file yg sama
                $existing_users[] = strtolower($username);
            } else {
                $errors[] = "Baris $row_number: Gagal simpan database - " . mysqli_error($conn);
                $error_count++;
            }
        }

        // Feedback
        if ($success_count > 0) {
            $msg = "Berhasil import $success_count anggota. ";
            if (!empty($errors)) {
                 $msg .= "Detail error: " . implode('; ', array_slice($errors, 0, 2));
            }
            $_SESSION['success'] = $msg;
        } else {
            $_SESSION['error'] = "Tidak ada data yang berhasil diimport. " . (count($errors) > 0 ? implode('; ', array_slice($errors, 0, 3)) : '');
        }

        // Cleanup
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

    } catch (Exception $e) {
        $_SESSION['error'] = "Error Exception: " . $e->getMessage();
    }

    header("Location: anggota.php");
    exit;
}
?>