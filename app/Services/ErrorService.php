<?php

namespace App\Services;

use App\Models\Error;

class ErrorService
{
    protected $errorModel;

    public function __construct()
    {
        $this->errorModel = new Error();
    }

    /**
     * Get all errors.
     *
     * @param array $requestData
     * @return array
     */
    public function getAllErrors($requestData)
    {
        $errors = $this->errorModel->setRequestParameters($requestData)->getAll();

        return [
            'data' => $errors['data'],
            'attributes' => [
                'totalRows' => $errors['totalRows'],
                'totalPages' => $errors['totalPages']
            ]
        ];
    }

    public function getErrorById($id)
    {
        return $this->errorModel->findOne($id);
    }

    /**
     * Stores a new error.
     *
     * @param array $data
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function create(array $data, array $params = []): array
    {
        $this->errorModel->db->transBegin();

        try {
            if (!$this->errorModel->insert($data)) {
                throw new \Exception("Error storing error.");
            }

            $newId = $this->errorModel->getInsertID();
            helper('audit');
            audit_log('errors', 'CREATE', $newId, null, $data);
            $position = $this->errorModel->getPosition($newId, $params);

            $this->errorModel->db->transCommit();

            return $position;
        } catch (\Throwable $th) {
            $this->errorModel->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
        }
    }

    /**
     * Updates an existing error.
     *
     * @param array $data
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function update(array $data, array $params = []): array
    {
        $this->errorModel->db->transBegin();

        try {
            helper('audit');
            $oldData = $this->errorModel->find($data['id']);

            if (!$this->errorModel->update($data['id'], $data)) {
                throw new \Exception("Error updating error.");
            }

            audit_log('errors', 'UPDATE', $data['id'], $oldData, $data);

            $position = $this->errorModel->getPosition($data['id'], $params);

            $this->errorModel->db->transCommit();

            return $position;
        } catch (\Throwable $th) {
            $this->errorModel->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
        }
    }

    /**
     * Deletes an error by ID.
     *
     * @param int|string $id
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function delete($id, array $params = []): array
    {
        $this->errorModel->db->transBegin();

        try {
            $position = $this->errorModel->getPosition($id, $params, true);

            helper('audit');
            $oldData = $this->errorModel->find($id);

            if (!$this->errorModel->delete($id)) {
                throw new \Exception("Error deleting error.");
            }

            audit_log('errors', 'DELETE', $id, $oldData, null);

            $this->errorModel->db->transCommit();

            return $position;
        } catch (\Throwable $th) {
            $this->errorModel->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
        }
    }
}
