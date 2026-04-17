<?php

namespace App\Services;

use App\Models\Notification;
use Config\Database;

class NotificationService
{
    protected $notificationModel;
    protected $db;

    public function __construct()
    {
        $this->notificationModel = new Notification();
        $this->db = Database::connect();
    }

    public function getAllNotification(array $params)
    {
        $data = $this->notificationModel->setRequestParameters($params)->getAll();

        return [
            'data' => $data['data'],
            'attributes' => [
                'totalRows' => $data['totalRows'],
                'totalPages' => $data['totalPages']
            ]
        ];
    }

    /**
     * Retrieves a notification by ID.
     *
     * @param int|string $id notification ID to retrieve.
     * @return array on success.
     */
    public function getByIdNotification($id)
    {
        return $this->notificationModel->findOne($id);
    }

    public function getUnreadNotifications($userId = null)
    {
        $query = $this->notificationModel->where('is_read', 0);
        if ($userId) {
            $query = $query->where('user_id', $userId);
        } else {
            $query = $query->where('user_id IS NULL', null, false);
        }
        return $query->findAll();
    }

    /**
     * Creates a new notification.
     *
     * @param array $data Data for creating the notification.
     * @return array on success.
     * @throws \Exception If inserting notification fails.
     */
    public function create(array $data, array $params): array
    {
        $this->notificationModel->db->transBegin();

        try {
            if (!$this->notificationModel->insert($data)) {
                throw new \Exception("Error inserting notification.");
            }

            $newId = $this->notificationModel->getInsertID();
            $position = $this->notificationModel->getPosition($newId, $params);

            $this->notificationModel->db->transCommit();

            return $position;

        } catch (\Throwable $th) {
            $this->notificationModel->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
        }
    }

    /**
     * Updates an existing notification.
     *
     * @param int|string $id notification ID to update.
     * @param array $data Data for updating the notification.
     * @return array on success.
     * @throws \Exception If updating notification fails.
     */
    public function update(array $data, array $params): array
    {
        $this->notificationModel->db->transBegin();

        try {
            if (!$this->notificationModel->update($data['id'], $data)) {
                throw new \Exception("Error updating notification.");
            }

            $position = $this->notificationModel->getPosition($data['id'], $params);

            $this->notificationModel->db->transCommit();

            return $position;

        } catch (\Throwable $th) {
            $this->notificationModel->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
        }
    }

    /**
     * Deletes a notification.
     *
     * @param int|string $id notification ID to delete.
     * @return array on success.
     * @throws \Exception If deleting notification fails.
     */
    public function delete($id, array $params): array
    {
        $this->notificationModel->db->transBegin();

        try {
            if (!$this->notificationModel->delete($id)) {
                throw new \Exception("Error deleting notification.");
            }

            $position = $this->notificationModel->getPosition($id, $params, true);

            $this->notificationModel->db->transCommit();

            return $position;

        } catch (\Throwable $th) {
            $this->notificationModel->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
        }
    }
}