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

// Buat Spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Template Import Anggota');

// Header Style
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

// Set Header sesuai kolom tabel Anggota (tanpa Status)
$headers = ['No', 'Username*', 'Password*', 'Kelas', 'Alamat', 'Tanggal Lahir*', 'Level'];
$sheet->fromArray($headers, NULL, 'A1');
$sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
$sheet->getRowDimension(1)->setRowHeight(25);

// Set lebar kolom
$sheet->getColumnDimension('A')->setWidth(6);   // No
$sheet->getColumnDimension('B')->setWidth(25);  // Username
$sheet->getColumnDimension('C')->setWidth(20);  // Password
$sheet->getColumnDimension('D')->setWidth(15);  // Kelas
$sheet->getColumnDimension('E')->setWidth(40);  // Alamat
$sheet->getColumnDimension('F')->setWidth(18);  // Tanggal Lahir
$sheet->getColumnDimension('G')->setWidth(12);  // Level

// Contoh data (baris 2-3)
// Format Tanggal: Y-m-d (contoh: 2006-05-20)
$contoh = [
    [1, 'riki', '123', 'XII RPL 1', 'Jl. Merdeka No. 10, Jakarta', '2006-05-20', 'user'],
    [2, 'reyhan', '123', 'X TKR 2', 'Jl. Mawar No. 5, Bandung', '2007-08-15', 'user'],
];

$rowNum = 2;
foreach ($contoh as $data) {
    $sheet->fromArray($data, NULL, 'A' . $rowNum);
    $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    // Align center untuk Level
    $sheet->getStyle('G' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    // Wrap text untuk Alamat
    $sheet->getStyle('E' . $rowNum)->getAlignment()->setWrapText(true);
    $rowNum++;
}

// Border untuk contoh data
$sheet->getStyle('A1:G3')->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'CCCCCC']
        ]
    ]
]);

// Instruksi di bawah
$sheet->setCellValue('A5', 'INSTRUKSI:');
$sheet->getStyle('A5')->getFont()->setBold(true)->setSize(12);

$instruksi = [
    '1. Kolom dengan tanda * wajib diisi.',
    '2. Hapus contoh data di baris 2-3 sebelum mengisi data Anda.',
    '3. Username harus unik (tidak boleh sama dengan member lain).',
    '4. Password akan disimpan sesuai yang diisi di kolom ini.',
    '5. Tanggal Lahir Format: Tahun-Bulan-Tanggal (YYYY-MM-DD), contoh: 2006-05-20.',
    '6. Level isi dengan: "admin" atau "user".',
    '7. Kolom Kelas dan Alamat boleh dikosongkan.',
    '8. Status anggota otomatis "aktif" saat diimport.',
    '9. Simpan file dalam format .xlsx.',
    '10. Upload file melalui menu Import di web.'
];

$row = 6;
foreach ($instruksi as $ins) {
    $sheet->setCellValue('A' . $row, $ins);
    $sheet->getStyle('A' . $row)->getFont()->setSize(10);
    // Merge cell sesuai jumlah kolom header (A sampai G)
    $sheet->mergeCells('A' . $row . ':G' . $row);
    $row++;
}

// Filename
$filename = 'Template_Import_Anggota.xlsx';

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