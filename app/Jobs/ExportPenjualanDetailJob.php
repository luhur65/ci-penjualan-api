<?php

namespace App\Jobs;

use CodeIgniter\Queue\BaseJob;
use App\Services\TestingMasterDetailService;
use App\Services\NotificationService;

/**
 * Job untuk export data detail penjualan ke CSV secara background.
 *
 * Menggunakan fputcsv (stream) — tidak load semua data ke memori,
 * cocok untuk jutaan baris sekalipun.
 */
class ExportPenjualanDetailJob extends BaseJob
{
    public function process()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $payload      = $this->data;
        $userId       = $payload['userId']      ?? null;
        $penjualanId  = $payload['penjualanId'] ?? null;
        $noBukti      = $payload['noBukti']     ?? $penjualanId;
        $filters      = $payload['filters']     ?? [];

        $service = new TestingMasterDetailService();

        // Ambil semua detail tanpa paginasi
        $filters['rows'] = 999999;
        unset($filters['page']);
        $result = $service->getAllDetail($penjualanId, $filters);

        // === Tulis ke CSV (stream, hemat memory) ===
        $safeNoBukti = preg_replace('/[^A-Za-z0-9_-]/', '_', $noBukti);
        $fileName    = 'Laporan_Detail_' . $safeNoBukti . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
        $exportDir   = WRITEPATH . 'uploads/exports';

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
        fputcsv($handle, ['No', 'Nama Barang', 'Qty', 'Harga Satuan', 'Subtotal', 'Modified By', 'Created At'], ';');

        $totalRows = 0;
        foreach ($result['data'] as $index => $item) {
            fputcsv($handle, [
                $index + 1,
                $item->nama_barang ?? '',
                $item->qty         ?? 0,
                number_format((float)($item->harga    ?? 0), 2, ',', '.'),
                number_format((float)($item->subtotal ?? 0), 2, ',', '.'),
                $item->modifiedby  ?? '',
                $item->created_at  ?? '',
            ], ';');
            $totalRows++;
        }

        fclose($handle);

        $downloadUrl = base_url('api/notifications/download/' . $fileName . '.csv');

        $notificationService = new NotificationService();
        $notificationService->sendNotificationWithWebSocket(
            $userId,
            'Export Detail Selesai',
            "Detail penjualan ({$noBukti}) — {$totalRows} baris sudah siap. Klik untuk unduh.",
            $downloadUrl
        );
    }
}
