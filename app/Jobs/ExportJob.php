<?php

namespace App\Jobs;

use CodeIgniter\Queue\BaseJob;

use App\Services\UserService;
use App\Libraries\ExcelMaker;
use App\Libraries\EmailSender;
use App\Services\NotificationService;

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

        // Create notification and Emit WebSocket
        $notificationService = new NotificationService();
        $notificationService->sendNotificationWithWebSocket($userId, $title, $message, $downloadUrl);

        // if ($email) {
        //     // Point the email URL to the frontend so it can make an authenticated request.
        //     $frontendBaseUrl = getenv('frontend.baseURL') ?: 'https://projects.karaya.site/belajarci4/';
        //     $frontendUrl = rtrim($frontendBaseUrl, '/') . '/download?file=' . $fileName . '.xlsx';

        //     $mailer = new EmailSender();
        //     $mailer->sendEmail(
        //         $email,
        //         'Export Selesai',
        //         'export_info',
        //         [
        //             'username' => $email,
        //             'downloadUrl' => $frontendUrl,
        //             'title' => 'Export Selesai',
        //             'appName' => 'Admin Sistem'
        //         ]
        //     );
        // }
    }
}
