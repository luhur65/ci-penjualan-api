<?php

namespace App\Jobs;

use CodeIgniter\Queue\BaseJob;

use App\Services\UserService;
use App\Libraries\ExcelMaker;
use App\Models\Notification as NotificationModel;

class ExportJob extends BaseJob
{
    public function process()
    {
        // Increase memory limit for generating large Excel files
        ini_set('memory_limit', '512M');

        $payload = $this->data;
        $userId  = $payload['userId'] ?? null;
        $filters = $payload['filters'] ?? [];
        $email   = $payload['email'] ?? null;

        $userService = new UserService();
        $users = $userService->getAllUsers($filters);

        $excelData = [];
        $offset = $filters['offset'] ?? 0;

        foreach ($users['data'] as $index => $user) {
            $statusData = json_decode($user->statusaktif, true);
            $statusMemo = $statusData['MEMO'] ?? '';

            $excelData[] = [
                $offset + $index + 1,
                $user->fullname ?? '',
                $user->email ?? '',
                $user->username ?? '',
                $statusMemo,
                $user->modifiedby ?? '',
                $user->created_at ?? '',
                $user->updated_at ?? ''
            ];
        }

        $headers = ['NO', 'NAMA LENGKAP', 'EMAIL', 'USERNAME', 'STATUS AKTIF', 'MODIFIED BY', 'CREATED AT', 'UPDATED AT'];

        $fileName = 'Laporan_User_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8));
        $exportDir = WRITEPATH . 'uploads/exports';

        $excelMaker = new ExcelMaker();
        $excelMaker->saveToFile($fileName, $headers, $excelData, $exportDir);

        $downloadUrl = base_url('api/notifications/download/' . $fileName . '.xlsx');

        $title = 'Export Selesai';
        $message = 'File laporan user sudah selesai digenerate.';

        // Create notification
        $notificationModel = new NotificationModel();
        $notificationModel->insert([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => 'success',
            'is_read' => 0,
            'action_url' => $downloadUrl,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Emit WebSocket Notification
        try {
            $client = \Config\Services::curlrequest();
            $client->post('http://localhost:3000/emit-notification', [
                'headers' => [
                    'Authorization' => 'Bearer my-secret-internal-token',
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'user_id' => $userId,
                    'title' => $title,
                    'message' => $message,
                    'action_url' => $downloadUrl
                ],
                'timeout' => 3
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to emit websocket notification: ' . $e->getMessage());
        }

        if ($email) {
            // Point the email URL to the frontend so it can make an authenticated request.
            $frontendBaseUrl = getenv('app.frontendURL') ?: 'http://localhost:3000';
            $frontendUrl = rtrim($frontendBaseUrl, '/') . '/download?file=' . $fileName . '.xlsx';

            $emailService = \Config\Services::email();
            $emailService->setTo($email);
            $emailService->setSubject('Export Laporan User Selesai');
            $emailService->setMessage("Laporan user yang Anda minta telah selesai digenerate. Silakan klik link berikut untuk mengunduh: <a href='{$frontendUrl}'>Download</a>");
            $emailService->send();
        }
    }
}
