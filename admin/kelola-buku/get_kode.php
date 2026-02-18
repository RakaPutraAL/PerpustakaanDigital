<?php
include __DIR__ . "/../../config/database.php";

if (isset($_GET['id_buku'])) {
    $id_buku = intval($_GET['id_buku']);

    // Ambil Prefix Kode dari detail_buku
    $q_detail = mysqli_query($conn, "SELECT kode_buku FROM detail_buku WHERE id_buku = $id_buku");
    $detail = mysqli_fetch_assoc($q_detail);
    $prefix = $detail['kode_buku'] ?? '';

    // Ambil Kode yang sedang dipinjam (PERBAIKAN LOGIKA)
    // Status yang dianggap "sedang dipinjam":
    // - dipinjam: sedang dipinjam aktif
    // - pending_kembali: sedang proses pengembalian (buku masih di user, belum dikembalikan)
    // - terlambat: terlambat (masih dipinjam)
    // - pending_payment: menunggu pembayaran denda (masih dipinjam)
    // 
    // Status yang TIDAK dianggap dipinjam:
    // - pending_pinjam: belum disetujui (buku masih di perpus)
    // - kembali: sudah dikembalikan (selesai)
    
    $q_transaksi = mysqli_query($conn, "
        SELECT kode_spesifik 
        FROM transaksi 
        WHERE id_buku = $id_buku 
        AND status IN ('dipinjam', 'pending_kembali', 'terlambat', 'pending_payment')
    ");
    
    $borrowed_codes = [];
    while ($t = mysqli_fetch_assoc($q_transaksi)) {
        $borrowed_codes[] = $t['kode_spesifik'];
    }

    echo json_encode([
        'prefix' => $prefix,
        'borrowed' => $borrowed_codes
    ]);
}
?>