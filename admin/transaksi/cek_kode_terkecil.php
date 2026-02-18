<?php
include __DIR__ . "/../../config/database.php";

 $id_buku = isset($_GET['id_buku']) ? intval($_GET['id_buku']) : 0;

if ($id_buku > 0) {
    // 1. Ambil Stok Buku dari tabel buku
    $q_buku = mysqli_query($conn, "SELECT stok FROM buku WHERE id = $id_buku");
    $data_buku = mysqli_fetch_assoc($q_buku);
    $stok = $data_buku['stok'];

    // 2. Ambil Prefix Kode dari detail_buku
    $q_detail = mysqli_query($conn, "SELECT kode_buku FROM detail_buku WHERE id_buku = $id_buku");
    $detail = mysqli_fetch_assoc($q_detail);
    $prefix = $detail['kode_buku'] ?? '';

    if (!empty($prefix)) {
        // 3. Ambil semua Kode yang SEDANG DIPINJAM
        $q_transaksi = mysqli_query($conn, "SELECT kode_spesifik FROM transaksi WHERE id_buku = $id_buku AND status = 'dipinjam'");
        $borrowed_codes = [];
        while ($t = mysqli_fetch_assoc($q_transaksi)) {
            $borrowed_codes[] = $t['kode_spesifik'];
        }

        // 4. Loop mencari kode kosong terkecil
        for ($i = 1; $i <= $stok; $i++) {
            // Buat nomor 001, 002 dst
            $num = str_pad($i, 3, '0', STR_PAD_LEFT); 
            $code_full = $prefix . $num; // SJ-001

            // Jika kode ini TIDAK ada di list dipinjam, maka dia yang tersedia
            if (!in_array($code_full, $borrowed_codes)) {
                echo $code_full;
                exit; // Berhenti begitu ketemu
            }
        }
    }
}

// Jika tidak ada kode tersedia atau error
echo "-";
?>