<?php
session_start();
include __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
    die("Akses ditolak");
}

// Ambil Parameter Filter
 $search = $_GET['search'] ?? '';
 $filter_level = $_GET['filter_level'] ?? 'all';

// Sanitasi input
 $search_sql = mysqli_real_escape_string($conn, $search);
 $filter_sql = mysqli_real_escape_string($conn, $filter_level);

// Bangun Query
 $query = "SELECT * FROM users";
 $conditions = [];

if (!empty($search_sql)) {
    $conditions[] = "username LIKE '%$search_sql%'";
}
if ($filter_sql != 'all') {
    $conditions[] = "level='$filter_sql'";
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(' AND ', $conditions);
}
 $query .= " ORDER BY id DESC";

 $result = mysqli_query($conn, $query);

// Set Header untuk Download CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Data_Anggota_'.date('Ymd_His').'.csv"');

 $output = fopen('php://output', 'w');

// 1. TAMBAHKAN BOM (Penting agar Excel membaca teks dengan benar)
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// 2. JUDUL LAPORAN (Agar rapi seperti laporan)
fputcsv($output, ['Laporan Data Anggota'], ';');
fputcsv($output, ['Dicetak pada: ' . date('d-m-Y H:i:s')], ';');
fputcsv($output, [], ';'); // Baris kosong

// 3. HEADER TABEL (Password sudah dihapus)
 $header = ['No', 'Username', 'Kelas', 'Alamat', 'Tanggal Lahir', 'Level', 'Status'];
fputcsv($output, $header, ';');

// 4. ISI DATA
 $no = 1;
while ($row = mysqli_fetch_assoc($result)) {
    
    // Format Tanggal Lahir (d-m-Y)
    $tgl_lahir = (!empty($row['tanggal_lahir'])) ? date('d-m-Y', strtotime($row['tanggal_lahir'])) : '-';

    // Siapkan baris data (Password sudah tidak ada di sini)
    $data = [
        $no++,
        $row['username'],
        $row['kelas'] ?? '-',
        $row['alamat'] ?? '-',
        $tgl_lahir,
        ucfirst($row['level'] ?? 'user'),  // Huruf depan besar
        ucfirst($row['status'] ?? 'active') // Huruf depan besar
    ];
    
    // Tulis ke CSV dengan pemisah titik koma (;)
    fputcsv($output, $data, ';');
}

fclose($output);
exit;
?>