<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Libraries\ExcelMaker;
use App\Models\Notification as NotificationModel;
use App\Services\NotificationService;
use CodeIgniter\HTTP\IncomingRequest;

class NotificationController extends BaseController
{
    use ResponseTrait;

    protected $notificationModel;
    protected $notificationService;

    /** @var IncomingRequest $request */
    protected $request;

    public function __construct()
    {
        $this->notificationModel   = new NotificationModel();
        $this->notificationService = new NotificationService();
    }

    /**
     * @ClassName
     * @Keterangan TAMPILKAN DATA
     */
    public function index()
    {
        $params = $this->request->getGet();
        return $this->respond($this->notificationService->getAllNotification($params));
    }

    public function getUnread()
    {
        // Here we could extract user ID from auth. For now, we will assume user_id is passed or null.
        $userId = $this->authUserId() ?? null;
        $notifications = $this->notificationService->getUnreadNotifications($userId);
        return $this->respond($notifications);
    }

    public function markAsRead($id)
    {
        $data = $this->notificationModel->find($id);
        $userId = $this->authUserId() ?? null;

        if (!$data || $data['user_id'] != $userId) {
            return $this->failNotFound("Notification not found");
        }
        $this->notificationModel->update($id, ['is_read' => 1]);
        return $this->respondUpdated(['message' => 'Notification marked as read']);
    }

    public function download($fileName)
    {
        // Prevent Path Traversal
        $fileName = basename($fileName);
        $filePath = WRITEPATH . 'uploads/exports/' . $fileName;

        $userId = $this->authUserId() ?? null;

        // Find if this user has a notification with this file as action_url
        $notification = $this->notificationModel
            ->where('user_id', $userId)
            ->like('action_url', $fileName)
            ->first();

        // Verify the user owns this file and it exists
        if (!$notification || empty($fileName) || !file_exists($filePath) || !is_file($filePath)) {
            return $this->failNotFound("File not found or unauthorized");
        }
        $this->response->setHeader('Access-Control-Expose-Headers', 'Content-Disposition');
        return $this->response->download($filePath, null);
    }
}