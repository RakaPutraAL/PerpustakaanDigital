<?php
session_start();

// 1. MATIKAN ERROR REPORTING KE LAYAR
// Mencegah teks Warning/Error PHP merusak format JSON
ini_set('display_errors', 0);
error_reporting(0);

// 2. BERSIHKAN BUFFER OUTPUT
// Ini membersihkan spasi/teks sisa yang mungkin terjadi sebelum file ini dipanggil
if (ob_get_length()) ob_clean();

// 3. INCLUDE DATABASE
// Jika path salah, akan memunculkan pesan error JSON yang rapi
include "../config/database.php"; 

// 4. SET HEADER JSON
header('Content-Type: application/json');

// 5. CEK SESI LOGIN
if (!isset($_SESSION['login'])) {
    http_response_code(403);
    echo json_encode(['status'=>'error','message'=>'Sesi habis. Silakan login ulang.']);
    exit;
}

// 6. AMBIL DATA INPUT
 $username = $_SESSION['username'];
 $id = intval($_POST['id'] ?? 0);
 $is_fine_payment = isset($_POST['pay_fine']) ? true : false;

// 7. VALIDASI INPUT ID
if ($id <= 0) {
    echo json_encode(['status'=>'error','message'=>'ID Transaksi tidak valid.']);
    exit;
}

// 8. CEK TRANSAKSI DI DATABASE
// Memastikan transaksi ada DAN benar-benar milik user yang sedang login
 $check = mysqli_query($conn, "SELECT id, status, tanggal_kembali FROM transaksi WHERE id=$id AND nama_peminjam='$username'");

if (mysqli_num_rows($check) === 0) {
    echo json_encode(['status'=>'error','message'=>'Data transaksi tidak ditemukan atau bukan milik Anda.']);
    exit;
}

 $data_transaksi = mysqli_fetch_assoc($check);

// 9. LOGIKA PENENTUAN STATUS
 $status_asal = $data_transaksi['status'];
 $status_baru = '';
 $pesan_sukses = '';

// Jika statusnya sudah selesai, jangan proses lagi
if ($status_asal == 'kembali') {
    echo json_encode(['status'=>'error','message'=>'Transaksi ini sudah selesai.']);
    exit;
}

// Jika statusnya sedang dipinjam dan membayar denda (asumsi sistem mengizinkan)
// Biasanya denda hanya boleh bayar jika telat. Tapi di sini kita sesuaikan dengan request:
// User memicu pembayaran denda -> status jadi 'pending_payment'
if ($is_fine_payment) {
    $status_baru = 'pending_payment';
    $pesan_sukses = 'Permintaan pembayaran denda dikirim. Menunggu verifikasi admin.';
} 
// Jika kembalikan biasa
else {
    // Cek apakah terlambat. Jika tidak terlambat, status jadi 'pending'.
    // Jika terlambat, status juga jadi 'pending' (tapi admin nanti cek di verifikasi denda).
    $status_baru = 'pending';
    $pesan_sukses = 'Pengembalian diajukan. Menunggu konfirmasi admin.';
}

// 10. EKSEKUSI QUERY UPDATE
 $query = "UPDATE transaksi SET status='$status_baru' WHERE id=$id";

if (mysqli_query($conn, $query)) {
    // SUKSES
    echo json_encode([
        'status' => 'success', 
        'message' => $pesan_sukses
    ]);
} else {
    // GAGAL DATABASE
    echo json_encode([
        'status' => 'error', 
        'message' => 'Gagal Database: ' . mysqli_error($conn)
    ]);
}

// 11. PAKSA BERHENTI
// Mencegah script lain di bawah ini (jika ada) menambah teks ke JSON
exit;
?>