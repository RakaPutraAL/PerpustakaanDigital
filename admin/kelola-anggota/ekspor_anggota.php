<?php
session_start();
require __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

// Cek akses admin
if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
    die("Akses ditolak");
}

include __DIR__ . "/../../config/database.php";

// Ambil parameter filter dari URL (sama seperti di halaman anggota.php)
$search = $_GET['search'] ?? '';
$filter_level = $_GET['filter_level'] ?? 'all';
$filter_status = $_GET['filter_status'] ?? 'all';

// Sanitasi input
$search_sql = mysqli_real_escape_string($conn, $search);
$filter_level_sql = mysqli_real_escape_string($conn, $filter_level);
$filter_status_sql = mysqli_real_escape_string($conn, $filter_status);

// Query Data (Tanpa LIMIT agar semua data yang sesuai filter terekspor)
$query = "SELECT id, username, level, kelas, alamat, tanggal_lahir, status FROM users";
$conditions = [];

// Filter otomatis: Exclude admin
$conditions[] = "level != 'admin'";

if (!empty($search_sql)) {
    $conditions[] = "username LIKE '%$search_sql%'";
}
if ($filter_level_sql != 'all') {
    $conditions[] = "level='$filter_level_sql'";
}
if ($filter_status_sql != 'all') {
    $conditions[] = "status='$filter_status_sql'";
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(' AND ', $conditions);
}

$query .= " ORDER BY id ASC"; // Export biasanya diurutkan ASC (ID terkecil) agar rapi
$result = mysqli_query($conn, $query);

// Buat Spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Data Anggota');

// --- Style Header ---
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 11
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '0d6efd']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];

// Judul Laporan (Merge Cells)
$sheet->mergeCells('A1:G1');
$sheet->setCellValue('A1', 'Laporan Data Anggota SIPERDI');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Tanggal Ekspor
$sheet->mergeCells('A2:G2');
$sheet->setCellValue('A2', 'Dicetak pada: ' . date('d-m-Y H:i:s'));
$sheet->getStyle('A2')->getFont()->setSize(10);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Header Tabel (Dimulai Baris 4) - Tanpa ID User
$headers = ['No', 'Username', 'Level', 'Kelas / Jabatan', 'Status', 'Alamat', 'Tanggal Lahir'];
$sheet->fromArray($headers, NULL, 'A4');
$sheet->getStyle('A4:G4')->applyFromArray($headerStyle);
$sheet->getRowDimension(4)->setRowHeight(25);

// --- Set Lebar Kolom ---
$sheet->getColumnDimension('A')->setWidth(6);   // No
$sheet->getColumnDimension('B')->setWidth(25);  // Username
$sheet->getColumnDimension('C')->setWidth(12);  // Level
$sheet->getColumnDimension('D')->setWidth(20);  // Kelas
$sheet->getColumnDimension('E')->setWidth(12);  // Status
$sheet->getColumnDimension('F')->setWidth(40);  // Alamat
$sheet->getColumnDimension('G')->setWidth(20);  // Tgl Lahir

// --- Isi Data ---
$no = 1;
$row = 5;

if (mysqli_num_rows($result) > 0) {
    while ($row_data = mysqli_fetch_assoc($result)) {
        // Nomor
        $sheet->setCellValue('A' . $row, $no++);
        // Username
        $sheet->setCellValue('B' . $row, $row_data['username']);
        // Level
        $sheet->setCellValue('C' . $row, ucfirst($row_data['level']));
        // Kelas
        $sheet->setCellValue('D' . $row, $row_data['kelas'] ?? '-');
        // Status
        $sheet->setCellValue('E' . $row, ucfirst($row_data['status']));
        
        // Alamat (Wrap Text agar panjang)
        $sheet->setCellValue('F' . $row, $row_data['alamat'] ?? '-');
        $sheet->getStyle('F' . $row)->getAlignment()->setWrapText(true);
        
        // Tanggal Lahir (Format Excel)
        $sheet->setCellValue('G' . $row, $row_data['tanggal_lahir'] ?? '-');

        // Set border untuk setiap baris data
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ]
        ]);

        // Alignment Center untuk No, Level, Status
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row++;
    }
} else {
    $sheet->mergeCells('A5:G5');
    $sheet->setCellValue('A5', 'Tidak ada data ditemukan.');
    $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}

// Footer Info (Total Data)
$sheet->setCellValue('F' . $row, 'Total Data:');
$sheet->setCellValue('G' . $row, $no - 1);
$sheet->getStyle('F' . $row . ':G' . $row)->getFont()->setBold(true);

// Filename
$filename = 'Data_Anggota_' . date('Ymd_His') . '.xlsx';

// Output file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

// Cleanup
$spreadsheet->disconnectWorksheets();
unset($spreadsheet);
exit;