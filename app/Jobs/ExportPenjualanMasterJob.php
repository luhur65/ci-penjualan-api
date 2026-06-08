<?php

namespace App\Jobs;

use CodeIgniter\Queue\BaseJob;
use App\Services\TestingMasterDetailService;
use App\Services\NotificationService;

/**
 * Job untuk export data master penjualan ke CSV secara background.
 *
 * Menggunakan fputcsv (stream) — tidak load semua data ke memori,
 * cocok untuk jutaan baris sekalipun.
 *
 * File yang dihasilkan: .csv (bisa dibuka langsung di Excel)
 */
class ExportPenjualanMasterJob extends BaseJob
{
    public function process()
    {
        set_time_limit(0);           // Tidak ada timeout
        ini_set('memory_limit', '512M');

        $payload   = $this->data;
        $userId    = $payload['userId']  ?? null;
        $filters   = $payload['filters'] ?? [];

        $service   = new TestingMasterDetailService();

        // Ambil semua data tanpa paginasi
        $filters['rows'] = 999999;
        unset($filters['page']);
        $result = $service->getAllTestingMasterDetail($filters);

        // === Tulis ke CSV (stream, hemat memory) ===
        $fileName  = 'Laporan_Penjualan_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
        $exportDir = WRITEPATH . 'uploads/exports';

        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0777, true);
        }

        $filePath = $exportDir . '/' . $fileName . '.csv';
        $handle   = fopen($filePath, 'w');

        if ($handle === false) {
            throw new \RuntimeException("Gagal membuat file: {$filePath}");
        }

        // BOM UTF-8 supaya Excel auto-detect encoding
        fwrite($handle, "\xEF\xBB\xBF");

        // Header row
        fputcsv($handle, ['No', 'No. Bukti', 'Tanggal', 'Pelanggan', 'Modified By', 'Created At', 'Updated At'], ';');

        $offset = (int)($filters['offset'] ?? 0);
        foreach ($result['data'] as $index => $item) {
            fputcsv($handle, [
                $offset + $index + 1,
                $item->no_bukti       ?? '',
                $item->tgl_bukti      ?? '',
                $item->nama_pelanggan ?? '',
                $item->modifiedby     ?? '',
                $item->created_at     ?? '',
                $item->updated_at     ?? '',
            ], ';');
        }

        fclose($handle);

        $totalRows   = count($result['data']);
        $downloadUrl = base_url('api/notifications/download/' . $fileName . '.csv');

        $notificationService = new NotificationService();
        $notificationService->sendNotificationWithWebSocket(
            $userId,
            'Export Penjualan Selesai',
            "File laporan data penjualan ({$totalRows} baris) sudah selesai. Klik untuk unduh.",
            $downloadUrl
        );
    }
}
