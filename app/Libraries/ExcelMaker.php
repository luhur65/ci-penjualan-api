<?php

namespace App\Libraries;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ExcelMaker
{
  /**
   * @param string $fileName Nama file output (tanpa ekstensi .xlsx)
   * @param array $headers Array 1D untuk judul kolom (cth: ['ID', 'NAMA', 'EMAIL'])
   * @param array $data Array 2D berisi data mentah
   */
  public function generate(string $fileName, array $headers, array $data)
  {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // 1. Tulis Header Kolom
    $col = 'A';
    foreach ($headers as $header) {
      $sheet->setCellValue($col . '1', $header);
      $col++;
    }

    // 2. Beri Gaya pada Header (Bold, Background Abu-abu, Rata Tengah)
    $lastCol = chr(ord('A') + count($headers) - 1);
    $headerStyle = [
      'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
      'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4472C4']
      ],
      'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
      ],
      'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
      ],
    ];
    $sheet->getStyle("A1:{$lastCol}1")->applyFromArray($headerStyle);

    // 3. Masukkan Data Secara Massal (Lebih cepat daripada looping baris per baris)
    if (!empty($data)) {
      $sheet->fromArray($data, null, 'A2');
    }

    // 4. Otomatis Sesuaikan Lebar Kolom (Auto-size)
    foreach (range('A', $lastCol) as $columnID) {
      $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // 5. Simpan ke buffer memory (Temp File) agar bisa dikirim sebagai Blob ke AJAX JQGrid
    $writer = new Xlsx($spreadsheet);
    $tempFile = tempnam(sys_get_temp_dir(), 'excel');
    $writer->save($tempFile);
    $content = file_get_contents($tempFile);
    unlink($tempFile); // Hapus file temp setelah dibaca

    // 6. Kembalikan Response Binary khusus untuk CodeIgniter 4
    return response()
      ->download($fileName . '.xlsx', $content)
      ->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  }

  /**
   * Generate Excel dengan kustomisasi total dari Controller
   */
  public function generateCustom(string $fileName, callable $customLogic)
  {
    $spreadsheet = new Spreadsheet();

    // Eksekusi fungsi kustom yang dikirim dari Controller
    $customLogic($spreadsheet);

    // Proses penyimpanan dan download standar
    $writer = new Xlsx($spreadsheet);
    $tempFile = tempnam(sys_get_temp_dir(), 'excel');
    $writer->save($tempFile);
    $content = file_get_contents($tempFile);
    unlink($tempFile);

    return response()
      ->download($fileName . '.xlsx', $content)
      ->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  }

  /**
   * Membaca template Excel fisik, mengisi data, lalu mendownloadnya
   */
  public function generateFromTemplate(string $templateName, string $outputName, callable $fillData)
  {
    // Path ke folder template Anda (misal: writable/templates/)
    $templatePath = WRITEPATH . 'templates/' . $templateName;

    if (!file_exists($templatePath)) {
      throw new \Exception("Template Excel tidak ditemukan di: " . $templatePath);
    }

    // Load template Excel yang sudah dihias cantik
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);

    // Isi datanya lewat Controller
    $fillData($spreadsheet);

    // Download hasilnya (template asli tidak akan berubah)
    $writer = new Xlsx($spreadsheet);
    $tempFile = tempnam(sys_get_temp_dir(), 'excel');
    $writer->save($tempFile);
    $content = file_get_contents($tempFile);
    unlink($tempFile);

    return response()
      ->download($outputName . '.xlsx', $content)
      ->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  }

  
}
