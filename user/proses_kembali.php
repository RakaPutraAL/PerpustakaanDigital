<?php
session_start();
// Sesuaikan path ini jika file berada di folder berbeda
// Asumsi: file ini ada di folder user (sama dengan peminjaman.php)
include "../config/database.php";

// 1. Cek apakah user sudah login
if (!isset($_SESSION['login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi habis, silakan login ulang.']);
    exit;
}

// 2. Cek apakah ada data ID transaksi yang dikirim
if (isset($_POST['id_transaksi'])) {
    
    // Ambil dan sanitasi input
    $id_transaksi = (int)$_POST['id_transaksi'];
    $username = $_SESSION['username']; // Ambil username user yang sedang login

    // 3. Validasi Data Transaksi
    // Pastikan transaksi tersebut MILIK user yang login DAN statusnya SEDANG DIPINJAM
    // Kita cek dulu sebelum update
    $cek = mysqli_query($conn, "SELECT id FROM transaksi WHERE id = $id_transaksi AND nama_peminjam = '$username' AND status = 'dipinjam'");
    
    if (mysqli_num_rows($cek) > 0) {
        // 4. Proses Update Status
        // Ubah status menjadi 'pending_kembali' menunggu konfirmasi admin
        $update = mysqli_query($conn, "UPDATE transaksi SET status = 'pending_kembali' WHERE id = $id_transaksi");
        
        if ($update) {
            // Respon Sukses
            echo json_encode([
                'status' => 'success', 
                'message' => 'Berhasil mengajukan pengembalian. Menunggu konfirmasi admin.'
            ]);
        } else {
            // Respon Gagal (Error Database)
            echo json_encode([
                'status' => 'error', 
                'message' => 'Gagal mengajukan pengembalian. Terjadi kesalahan pada database.'
            ]);
        }
    } else {
        // Respon Gagal (Data tidak valid atau buku bukan sedang dipinjam)
        echo json_encode([
            'status' => 'error', 
            'message' => 'Data tidak valid. Buku mungkin sudah dikembalikan atau bukan milik Anda.'
        ]);
    }

} else {
    // Respon Gagal (Tidak ada parameter ID)
    echo json_encode(['status' => 'error', 'message' => 'Permintaan tidak valid.']);
}
?>