<?php
// PENTING: Jangan ada spasi atau karakter apapun sebelum <?php

// Start output buffering untuk menangkap semua output tidak diinginkan
ob_start();

session_start();
require __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . "/../../config/database.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

// Cek akses admin
if (!isset($_SESSION['login']) || $_SESSION['level'] !== 'admin') {
    ob_end_clean();
    die("Akses ditolak");
}

// Query untuk mengambil semua data buku
$query = "SELECT buku.*, detail_buku.kode_buku as prefix_kode, detail_buku.kategori, detail_buku.deskripsi, detail_buku.gambar
          FROM buku
          LEFT JOIN detail_buku ON buku.id = detail_buku.id_buku
          ORDER BY buku.id ASC";
$result = mysqli_query($conn, $query);

if (!$result) {
    ob_end_clean();
    die("Error query: " . mysqli_error($conn));
}

// Buat Spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Data Buku');

// Header Style - Background biru, teks putih, bold
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
$headers = ['No', 'Gambar', 'Judul Buku', 'Penulis', 'Penerbit', 'Tahun', 'Stok', 'Kategori', 'Prefix Kode', 'Deskripsi', 'Nama File Gambar'];
$sheet->fromArray($headers, NULL, 'A1');
$sheet->getStyle('A1:K1')->applyFromArray($headerStyle);
$sheet->getRowDimension(1)->setRowHeight(25);

// Set lebar kolom
$sheet->getColumnDimension('A')->setWidth(6);   // No
$sheet->getColumnDimension('B')->setWidth(15);  // Gambar
$sheet->getColumnDimension('C')->setWidth(35);  // Judul
$sheet->getColumnDimension('D')->setWidth(25);  // Penulis
$sheet->getColumnDimension('E')->setWidth(25);  // Penerbit
$sheet->getColumnDimension('F')->setWidth(10);  // Tahun
$sheet->getColumnDimension('G')->setWidth(8);   // Stok
$sheet->getColumnDimension('H')->setWidth(20);  // Kategori
$sheet->getColumnDimension('I')->setWidth(15);  // Prefix Kode
$sheet->getColumnDimension('J')->setWidth(50);  // Deskripsi
$sheet->getColumnDimension('K')->setWidth(25);  // Nama File Gambar

// Fill data
$rowNum = 2;
$no = 1;
while ($row = mysqli_fetch_assoc($result)) {
    $sheet->setCellValue('A' . $rowNum, $no++);
    
    // Kolom B untuk gambar (akan diisi dengan Drawing object)
    
    $sheet->setCellValue('C' . $rowNum, $row['judul']);
    $sheet->setCellValue('D' . $rowNum, $row['penulis']);
    $sheet->setCellValue('E' . $rowNum, $row['penerbit']);
    $sheet->setCellValue('F' . $rowNum, $row['tahun']);
    $sheet->setCellValue('G' . $rowNum, $row['stok']);
    $sheet->setCellValue('H' . $rowNum, $row['kategori'] ?: 'Umum');
    $sheet->setCellValue('I' . $rowNum, $row['prefix_kode'] ?: '');
    $sheet->setCellValue('J' . $rowNum, $row['deskripsi'] ?: '');
    $sheet->setCellValue('K' . $rowNum, $row['gambar'] ?: ''); // Nama file gambar
    
    // Set tinggi baris untuk gambar
    $sheet->getRowDimension($rowNum)->setRowHeight(80);
    
    // Insert gambar jika ada
    if (!empty($row['gambar'])) {
        $imagePath = __DIR__ . '/../../uploads/' . $row['gambar'];
        
        if (file_exists($imagePath)) {
            try {
                $drawing = new Drawing();
                $drawing->setName('Gambar Buku');
                $drawing->setDescription('Cover Buku');
                $drawing->setPath($imagePath);
                $drawing->setCoordinates('B' . $rowNum);
                $drawing->setOffsetX(5);
                $drawing->setOffsetY(5);
                
                // Set ukuran gambar (width x height dalam pixels)
                $drawing->setWidth(60);
                $drawing->setHeight(70);
                
                $drawing->setWorksheet($sheet);
            } catch (Exception $e) {
                // Jika gagal insert gambar, skip saja (tidak error)
                // Bisa jadi format gambar tidak didukung
            }
        }
    }
    
    // Alignment untuk data
    $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('B' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('F' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('G' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('J' . $rowNum)->getAlignment()->setWrapText(true);
    
    $rowNum++;
}

// Border untuk semua data
$lastRow = $rowNum - 1;
if ($lastRow > 0) {
    $sheet->getStyle('A1:K' . $lastRow)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'CCCCCC']
            ]
        ]
    ]);
}

// Set filename dengan timestamp
$filename = 'Data_Buku_' . date('Y-m-d_His') . '.xlsx';

// PENTING: Hapus semua output sebelumnya
ob_end_clean();

// Set headers untuk download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1'); // For IE
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

// Tulis ke output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

// Bersihkan memori
$spreadsheet->disconnectWorksheets();
unset($spreadsheet);

exit;