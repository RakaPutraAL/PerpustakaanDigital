<?php
session_start();
include __DIR__ . "/../../config/database.php";
require __DIR__ . '/../../src/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

// ================= CEK AKSES =================
if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
    die("Akses ditolak");
}

// ================= PROSES IMPORT =================
if (isset($_POST['import'])) {

    /* =====================================================
       1. VALIDASI FILE
    ===================================================== */
    if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "File tidak valid atau gagal diupload.";
        header("Location: anggota.php");
        exit;
    }

    $file_tmp  = $_FILES['file_excel']['tmp_name'];
    $file_name = $_FILES['file_excel']['name'];
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if ($ext !== 'xlsx') {
        $_SESSION['error'] = "Format file harus Excel (.xlsx)";
        header("Location: anggota.php");
        exit;
    }

    /* =====================================================
       2. LOAD EXCEL
    ===================================================== */
    if ($xlsx = SimpleXLSX::parse($file_tmp)) {
        
        // --- PERBAIKAN WARNING VS CODE (P1006) ---
        // Kita tambahkan komentar PHPDoc di bawah ini untuk memberitahu VS Code 
        // bahwa variabel $rows ini adalah Array, bukan Generator.
        // Ini membuat garis merah/error di hilangkan, tapi kode tetap jalan di server.

        /** @var array $rows */
        $rows = $xlsx->rows();
    } else {
        $_SESSION['error'] = "Gagal membaca Excel: " . SimpleXLSX::parseError();
        header("Location: anggota.php");
        exit;
    }

    // Validasi: File harus punya minimal header
    if (count($rows) < 1) {
        $_SESSION['error'] = "File Excel kosong.";
        header("Location: anggota.php");
        exit;
    }

    // Hapus Baris Pertama (Header) agar sisanya adalah data
    $header = array_shift($rows);

    /* =====================================================
       3. AMBIL USERNAME YANG SUDAH ADA (Cek Duplikat)
    ===================================================== */
    $existing_users = [];
    $query = mysqli_query($conn, "SELECT username FROM users");

    if ($query) {
        while ($data = mysqli_fetch_assoc($query)) {
            $existing_users[] = strtolower(trim($data['username']));
        }
    }

    /* =====================================================
       4. PROSES DATA BARIS PER BARIS
    ===================================================== */
    $success_count = 0;
    $failed_count  = 0;
    $error_messages = [];

    foreach ($rows as $index => $row) {

        // Nomor baris sebenarnya di Excel (karena header di-shift, index mulai dari 0 berarti baris ke-2 Excel)
        $row_number = $index + 2;

        // Skip baris yang benar-benar kosong
        if (empty(array_filter($row))) {
            continue;
        }

        /* 
           MAPPING KOLOM (Sesuai Template TANPA STATUS):
           [0] => No (Kolom A)
           [1] => Username (Kolom B) - WAJIB
           [2] => Password (Kolom C) - WAJIB
           [3] => Kelas (Kolom D)
           [4] => Alamat (Kolom E)
           [5] => Tanggal Lahir (Kolom F)
           [6] => Level (Kolom G)
        */

        $no       = trim($row[0] ?? '');
        $username = trim($row[1] ?? '');
        $password = trim($row[2] ?? '');
        $kelas    = trim($row[3] ?? '');
        $alamat   = trim($row[4] ?? '');
        $raw_tgl  = $row[5] ?? '';
        $raw_level  = strtolower(trim($row[6] ?? ''));

        /* ---------- VALIDASI DATA KOSONG ---------- */
        if (empty($username)) {
            $error_messages[] = "Baris $row_number: Username tidak boleh kosong.";
            $failed_count++;
            continue;
        }

        if (empty($password)) {
            $error_messages[] = "Baris $row_number: Password tidak boleh kosong.";
            $failed_count++;
            continue;
        }

        /* ---------- CEK DUPLIKAT USERNAME ---------- */
        if (in_array(strtolower($username), $existing_users)) {
            $error_messages[] = "Baris $row_number: Username '<b>$username</b>' sudah ada di database.";
            $failed_count++;
            continue;
        }

        /* ---------- NORMALISASI LEVEL ---------- */
        // Jika isi kolom bukan 'admin', otomatis jadi 'user'
        $level = ($raw_level === 'admin') ? 'admin' : 'user';

        /* ---------- STATUS OTOMATIS AKTIF ---------- */
        // Status otomatis aktif saat import oleh admin
        $status = 'aktif';

        /* ---------- HANDLE TANGGAL LAHIR ---------- */
        $tanggal_lahir = NULL;

        if (!empty($raw_tgl)) {
            // Cek jika Excel menyimpan tanggal sebagai Angka (Serial Date)
            if (is_numeric($raw_tgl)) {
                // Rumus konversi Excel Serial ke Unix Timestamp
                $unix_timestamp = ($raw_tgl - 25569) * 86400;
                $tanggal_lahir = date('Y-m-d', $unix_timestamp);
            } else {
                // Jika berupa string text
                $timestamp = strtotime($raw_tgl);
                if ($timestamp !== false) {
                    $tanggal_lahir = date('Y-m-d', $timestamp);
                }
            }
        }

        /* ---------- HASH PASSWORD ---------- */
        // PILIH SALAH SATU metode hashing sesuai sistem login Anda:
        
        // Opsi 1: Bcrypt (RECOMMENDED - paling aman)
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        
        // Opsi 2: MD5 (jika sistem lama Anda pakai MD5)
        // $password_hashed = md5($password);
        
        // Opsi 3: SHA1 (jika sistem lama Anda pakai SHA1)
        // $password_hashed = sha1($password);

        /* ---------- KEAMANAN SQL INJECTION ---------- */
        $username_safe = mysqli_real_escape_string($conn, $username);
        $password_safe = mysqli_real_escape_string($conn, $password_hashed);
        $kelas_safe    = mysqli_real_escape_string($conn, $kelas);
        $alamat_safe   = mysqli_real_escape_string($conn, $alamat);
        $level_safe    = mysqli_real_escape_string($conn, $level);
        $status_safe   = mysqli_real_escape_string($conn, $status);
        $tgl_safe      = $tanggal_lahir ? "'$tanggal_lahir'" : "NULL";

        /* ---------- EKSEKUSI INSERT ---------- */
        $sql = "INSERT INTO users 
                (username, password, kelas, alamat, tanggal_lahir, level, status) 
                VALUES 
                ('$username_safe', '$password_safe', '$kelas_safe', '$alamat_safe', $tgl_safe, '$level_safe', '$status_safe')";

        if (mysqli_query($conn, $sql)) {
            $success_count++;
            // Masukkan username ke array existing agar cek duplikat berlaku juga untuk file ini
            $existing_users[] = strtolower($username);
        } else {
            $error_messages[] = "Baris $row_number: " . mysqli_error($conn);
            $failed_count++;
        }
    }

    /* =====================================================
       5. FEEDBACK HASIL IMPORT
    ===================================================== */
    if (!empty($error_messages)) {
        $_SESSION['import_errors'] = array_slice($error_messages, 0, 10);
    }

    if ($success_count > 0) {
        $_SESSION['success'] = "Berhasil mengimpor " . $success_count . " data anggota.";
    }

    if ($failed_count > 0) {
        $msg = isset($_SESSION['success']) ? "Sebagian gagal." : "Import gagal.";
        $_SESSION['warning'] = "$msg " . $failed_count . " data tidak masuk. Cek detail error.";
    }

    if ($success_count == 0 && $failed_count == 0) {
        $_SESSION['error'] = "Tidak ada data valid untuk diimport.";
    }

    header("Location: anggota.php");
    exit;
}
?>