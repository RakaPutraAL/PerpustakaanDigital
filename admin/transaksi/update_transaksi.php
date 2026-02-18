<?php
session_start();
include __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
    die("Akses ditolak");
}

if (isset($_POST['update'])) {
    // Ambil dan sanitasi input
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $id_buku_baru = isset($_POST['id_buku']) ? (int)$_POST['id_buku'] : 0;
    $nama_peminjam = isset($_POST['nama_peminjam']) ? trim(mysqli_real_escape_string($conn, $_POST['nama_peminjam'])) : '';
    $tanggal_pinjam = isset($_POST['tanggal_pinjam']) && $_POST['tanggal_pinjam'] !== '' ? $_POST['tanggal_pinjam'] : null;
    $tanggal_kembali = isset($_POST['tanggal_kembali']) && $_POST['tanggal_kembali'] !== '' ? $_POST['tanggal_kembali'] : null;
    $status = isset($_POST['status']) ? $_POST['status'] : 'dipinjam';
    $kode_spesifik = isset($_POST['kode_spesifik']) ? trim(mysqli_real_escape_string($conn, $_POST['kode_spesifik'])) : '';

    // Validasi ID transaksi
    if ($id <= 0) {
        $_SESSION['message'] = "ID transaksi tidak valid!";
        $_SESSION['msg_type'] = "danger";
        header("Location: transaksi.php");
        exit;
    }

    // Validasi input
    if (empty($nama_peminjam) || $id_buku_baru <= 0 || empty($kode_spesifik)) {
        $_SESSION['message'] = "Semua field harus diisi dengan benar!";
        $_SESSION['msg_type'] = "danger";
        header("Location: transaksi.php");
        exit;
    }

    // Validasi status (DITAMBAH pending_pinjam dan pending_kembali)
    $valid_status = ['pending_pinjam', 'dipinjam', 'pending_kembali', 'kembali', 'terlambat', 'pending_payment'];
    if (!in_array($status, $valid_status)) {
        $_SESSION['message'] = "Status tidak valid!";
        $_SESSION['msg_type'] = "danger";
        header("Location: transaksi.php");
        exit;
    }

    // Validasi tanggal
    if ($tanggal_pinjam && $tanggal_kembali && $tanggal_kembali < $tanggal_pinjam) {
        $_SESSION['message'] = "Tanggal kembali tidak boleh sebelum tanggal pinjam!";
        $_SESSION['msg_type'] = "danger";
        header("Location: transaksi.php");
        exit;
    }

    // Ambil data transaksi lama
    $query_lama = mysqli_query($conn, "SELECT id_buku, status, kode_spesifik FROM transaksi WHERE id = $id");
    if (mysqli_num_rows($query_lama) == 0) {
        $_SESSION['message'] = "Data transaksi tidak ditemukan!";
        $_SESSION['msg_type'] = "danger";
        header("Location: transaksi.php");
        exit;
    }
    
    $row_lama = mysqli_fetch_assoc($query_lama);
    $id_buku_lama = $row_lama['id_buku'];
    $status_lama = $row_lama['status'];
    $kode_lama = $row_lama['kode_spesifik'];

    // Cek apakah kode spesifik yang baru sudah digunakan oleh transaksi lain (kecuali transaksi ini sendiri)
    $query_cek_kode = mysqli_query($conn, "SELECT id FROM transaksi WHERE kode_spesifik = '$kode_spesifik' AND id != $id AND status IN ('dipinjam', 'pending_pinjam', 'pending_kembali')");
    if (mysqli_num_rows($query_cek_kode) > 0) {
        $_SESSION['message'] = "Kode buku '$kode_spesifik' sedang digunakan oleh transaksi lain!";
        $_SESSION['msg_type'] = "danger";
        header("Location: transaksi.php");
        exit;
    }

    // Mulai transaksi database
    mysqli_begin_transaction($conn);

    try {
        // ========================================
        // LOGIKA STOK BUKU
        // ========================================
        
        // Status yang MENGURANGI stok buku
        $status_pinjam = ['dipinjam', 'pending_kembali', 'terlambat', 'pending_payment'];
        
        // Status yang TIDAK mengurangi stok (belum approved atau sudah selesai)
        $status_tidak_pinjam = ['pending_pinjam', 'kembali'];

        // Jika status LAMA adalah status pinjam (buku sedang keluar), kembalikan stok buku lama
        if (in_array($status_lama, $status_pinjam)) {
            mysqli_query($conn, "UPDATE buku SET stok = stok + 1 WHERE id = $id_buku_lama");
        }

        // Jika status BARU adalah status pinjam (buku akan keluar), kurangi stok buku baru
        if (in_array($status, $status_pinjam)) {
            // Cek stok dulu
            $cek_stok = mysqli_query($conn, "SELECT stok FROM buku WHERE id = $id_buku_baru");
            $data_stok = mysqli_fetch_assoc($cek_stok);
            
            if ($data_stok['stok'] <= 0) {
                throw new Exception("Stok buku tidak mencukupi!");
            }
            
            mysqli_query($conn, "UPDATE buku SET stok = stok - 1 WHERE id = $id_buku_baru");
        }

        // ========================================
        // UPDATE TRANSAKSI
        // ========================================
        $sql = "UPDATE transaksi 
                SET id_buku = ?, 
                    nama_peminjam = ?, 
                    tanggal_pinjam = ?, 
                    tanggal_kembali = ?, 
                    status = ?, 
                    kode_spesifik = ? 
                WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            throw new Exception("Gagal mempersiapkan query database: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "isssssi", 
            $id_buku_baru, 
            $nama_peminjam, 
            $tanggal_pinjam, 
            $tanggal_kembali, 
            $status, 
            $kode_spesifik, 
            $id
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Gagal mengeksekusi query: " . mysqli_stmt_error($stmt));
        }
        
        mysqli_stmt_close($stmt);

        // Commit transaksi
        mysqli_commit($conn);
        
        $_SESSION['message'] = "Transaksi berhasil diupdate!";
        $_SESSION['msg_type'] = "success";
        
        header("Location: transaksi.php");
        exit;

    } catch (Exception $e) {
        // Rollback jika ada error
        mysqli_rollback($conn);
        
        $_SESSION['message'] = "Gagal update transaksi: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
        
        header("Location: transaksi.php");
        exit;
    }

} else {
    // Jika tidak ada POST data, redirect ke halaman transaksi
    header("Location: transaksi.php");
    exit;
}
?>
