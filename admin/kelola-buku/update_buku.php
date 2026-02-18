<?php
session_start();
include __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
    die("Akses ditolak");
}

if (isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $penulis = mysqli_real_escape_string($conn, $_POST['penulis']);
    $penerbit = mysqli_real_escape_string($conn, $_POST['penerbit']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $tahun = (int)$_POST['tahun'];
    $stok = (int)$_POST['stok'];
    $prefix_kode_baru = mysqli_real_escape_string($conn, $_POST['prefix_kode']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);

    // ================================================================
    // STEP 1: AMBIL PREFIX KODE LAMA DARI DATABASE
    // ================================================================
    $query_old_prefix = mysqli_query($conn, "SELECT kode_buku FROM detail_buku WHERE id_buku = $id");
    $data_old = mysqli_fetch_assoc($query_old_prefix);
    $prefix_kode_lama = $data_old['kode_buku'] ?? '';

    // ================================================================
    // STEP 2: UPDATE TABEL BUKU (Data Utama)
    // ================================================================
    $query_buku = "UPDATE buku SET 
                    judul = '$judul', 
                    penulis = '$penulis', 
                    penerbit = '$penerbit', 
                    tahun = $tahun, 
                    stok = $stok 
                   WHERE id = $id";

    if (!mysqli_query($conn, $query_buku)) {
        $_SESSION['error'] = "Gagal update data buku: " . mysqli_error($conn);
        header("Location: kelola_buku.php");
        exit;
    }

    // ================================================================
    // STEP 3: HANDLE UPLOAD GAMBAR BARU (Jika Ada)
    // ================================================================
    $nama_file_baru = '';

    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $nama_asli = $_FILES['gambar']['name'];
        $lokasi_tmp = $_FILES['gambar']['tmp_name'];
        $ukuran_file = $_FILES['gambar']['size'];
        $ekstensi = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));

        $ekstensi_diperbolehkan = array('jpg', 'jpeg', 'png', 'webp', 'gif');

        // Validasi Ekstensi & Ukuran (Max 2MB)
        if (in_array($ekstensi, $ekstensi_diperbolehkan) && $ukuran_file <= 2048000) {
            $nama_file_baru = "buku-" . time() . "-" . rand(1000, 9999) . "." . $ekstensi;
            $folder_upload = __DIR__ . "/../../uploads/";

            // Pindahkan file baru
            if (move_uploaded_file($lokasi_tmp, $folder_upload . $nama_file_baru)) {
                // Hapus gambar lama dari folder fisik (Hemat Storage)
                $query_gambar_lama = mysqli_query($conn, "SELECT gambar FROM detail_buku WHERE id_buku = $id");
                $data_lama = mysqli_fetch_assoc($query_gambar_lama);

                if ($data_lama && !empty($data_lama['gambar'])) {
                    $file_lama_path = $folder_upload . $data_lama['gambar'];
                    if (file_exists($file_lama_path)) {
                        unlink($file_lama_path); // Hapus file lama
                    }
                }
            } else {
                $_SESSION['error'] = "Gagal upload gambar baru.";
                header("Location: kelola_buku.php");
                exit;
            }
        } else {
            $_SESSION['error'] = "Format gambar tidak valid atau ukuran terlalu besar (Max 2MB).";
            header("Location: kelola_buku.php");
            exit;
        }
    }

    // ================================================================
    // STEP 4: UPDATE TABEL DETAIL_BUKU (Kategori, Deskripsi, Prefix, Gambar)
    // ================================================================
    $sql_gambar = "";
    if (!empty($nama_file_baru)) {
        $sql_gambar = ", gambar = '$nama_file_baru'";
    }

    $query_detail = "UPDATE detail_buku SET 
                        kategori = '$kategori', 
                        deskripsi = '$deskripsi',
                        kode_buku = '$prefix_kode_baru' 
                        $sql_gambar
                     WHERE id_buku = $id";

    if (!mysqli_query($conn, $query_detail)) {
        $_SESSION['error'] = "Gagal update detail buku: " . mysqli_error($conn);
        header("Location: kelola_buku.php");
        exit;
    }

    // ================================================================
    // STEP 5: UPDATE KODE SPESIFIK DI TABEL TRANSAKSI (FITUR BARU!)
    // ================================================================
    // Jika prefix berubah, update semua transaksi yang menggunakan prefix lama
    if (!empty($prefix_kode_lama) && $prefix_kode_lama !== $prefix_kode_baru) {
        
        // Ambil semua transaksi yang menggunakan prefix lama
        $query_transaksi = mysqli_query($conn, "
            SELECT id, kode_spesifik 
            FROM transaksi 
            WHERE id_buku = $id 
              AND kode_spesifik LIKE '$prefix_kode_lama%'
        ");

        $jumlah_update = 0;
        
        while ($t = mysqli_fetch_assoc($query_transaksi)) {
            $kode_lama = $t['kode_spesifik'];
            
            // Ekstrak nomor urut dari kode lama (misal: SJ-001 → 001)
            $nomor_urut = str_replace($prefix_kode_lama, '', $kode_lama);
            
            // Buat kode baru dengan prefix baru (misal: BIO-001)
            $kode_baru = $prefix_kode_baru . $nomor_urut;
            
            // Update transaksi
            $update_transaksi = mysqli_query($conn, "
                UPDATE transaksi 
                SET kode_spesifik = '$kode_baru' 
                WHERE id = " . $t['id']
            );
            
            if ($update_transaksi) {
                $jumlah_update++;
            }
        }
        
        $_SESSION['success'] = "✅ Data buku berhasil diperbarui! Prefix kode berubah dari <strong>$prefix_kode_lama</strong> menjadi <strong>$prefix_kode_baru</strong>. ($jumlah_update transaksi terupdate)";
        
    } else {
        $_SESSION['success'] = "✅ Data buku berhasil diperbarui!";
    }

    header("Location: kelola_buku.php");
    exit;

} else {
    $_SESSION['error'] = "Akses tidak valid.";
    header("Location: kelola_buku.php");
    exit;
}
?>