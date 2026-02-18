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
$sheet->setTitle('Template Import Buku');

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

// Set Header
$headers = ['No', 'Gambar', 'Judul Buku*', 'Penulis*', 'Penerbit*', 'Tahun*', 'Stok*', 'Kategori', 'Prefix Kode', 'Deskripsi', 'Nama File Gambar'];
$sheet->fromArray($headers, NULL, 'A1');
$sheet->getStyle('A1:K1')->applyFromArray($headerStyle);
$sheet->getRowDimension(1)->setRowHeight(25);

// Set lebar kolom
$sheet->getColumnDimension('A')->setWidth(6);   // No
$sheet->getColumnDimension('B')->setWidth(15);  // Gambar (placeholder)
$sheet->getColumnDimension('C')->setWidth(35);  // Judul
$sheet->getColumnDimension('D')->setWidth(25);  // Penulis
$sheet->getColumnDimension('E')->setWidth(25);  // Penerbit
$sheet->getColumnDimension('F')->setWidth(10);  // Tahun
$sheet->getColumnDimension('G')->setWidth(8);   // Stok
$sheet->getColumnDimension('H')->setWidth(20);  // Kategori
$sheet->getColumnDimension('I')->setWidth(15);  // Prefix Kode
$sheet->getColumnDimension('J')->setWidth(50);  // Deskripsi
$sheet->getColumnDimension('K')->setWidth(25);  // Nama File Gambar

// Contoh data (baris 2-3)
$contoh = [
    [1, '', 'Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', 2005, 10, 'Fiksi', 'LP-', 'Novel tentang perjuangan anak-anak Belitung', 'laskar_pelangi.jpg'],
    [2, '', 'Bumi Manusia', 'Pramoedya Ananta Toer', 'Hasta Mitra', 1980, 8, 'Sejarah', 'BM-', 'Tetralogi Buru Jilid 1', 'bumi_manusia.jpg'],
];

$rowNum = 2;
foreach ($contoh as $data) {
    $sheet->fromArray($data, NULL, 'A' . $rowNum);
    $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('G' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('J' . $rowNum)->getAlignment()->setWrapText(true);
    
    // Tambahkan keterangan di kolom Gambar
    $sheet->setCellValue('B' . $rowNum, '(lihat kolom K)');
    $sheet->getStyle('B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('B' . $rowNum)->getFont()->setItalic(true)->setSize(9);
    
    $rowNum++;
}

// Border untuk contoh data
$sheet->getStyle('A1:K3')->applyFromArray([
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
    '1. Kolom dengan tanda * wajib diisi',
    '2. Hapus contoh data di baris 2-3 sebelum mengisi data Anda',
    '3. Format Tahun: Angka 4 digit (contoh: 2024)',
    '4. Format Stok: Angka positif (contoh: 10)',
    '5. Kategori: Boleh kosong (default: Umum)',
    '6. Prefix Kode: Misal LP-, BM-, SJ- (opsional)',
    '7. Nama File Gambar: Tulis nama file gambar yang sudah ada di folder uploads/',
    '   Contoh: laskar_pelangi.jpg, bumi_manusia.png (opsional, boleh kosong)',
    '8. Pastikan file gambar sudah diupload ke folder uploads/ sebelum import',
    '9. Kolom Gambar (B) tidak perlu diisi, hanya untuk referensi saat ekspor',
    '10. Simpan file dalam format .xlsx',
    '11. Upload file melalui menu Import di web'
];

$row = 6;
foreach ($instruksi as $ins) {
    $sheet->setCellValue('A' . $row, $ins);
    $sheet->getStyle('A' . $row)->getFont()->setSize(10);
    $sheet->mergeCells('A' . $row . ':K' . $row);
    $row++;
}

// Filename
$filename = 'Template_Import_Buku.xlsx';

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