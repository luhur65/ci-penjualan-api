<?php

namespace App\Services;

use App\Models\Penjualan;
use Config\Database;

class PenjualanService
{
    protected $penjualanModel;
    protected $db;

    public function __construct()
    {
        $this->penjualanModel = new Penjualan();
        $this->db = Database::connect();
    }

    public function getAllPenjualan(array $params)
    {
        $data = $this->penjualanModel->setRequestParameters($params)->getAll();

        return [
            'data' => $data['data'],
            'attributes' => [
                'totalRows' => $data['totalRows'],
                'totalPages' => $data['totalPages']
            ]
        ];
    }

    /**
     * Retrieves a penjualan by ID.
     *
     * @param int|string $id penjualan ID to retrieve.
     * @return array on success.
     */
    public function getByIdPenjualan($id)
    {
        return $this->penjualanModel->findOne($id);
    }

    /**
     * Creates a new penjualan.
     *
     * @param array $data Data for creating the penjualan.
     * @return array on success.
     * @throws \Exception If inserting penjualan fails.
     */
    public function create(array $data, array $params): array
    {
        $this->penjualanModel->db->transBegin();

        try {
            if (!$this->penjualanModel->insert($data)) {
                throw new \Exception("Error inserting penjualan.");
            }

            $newId = $this->penjualanModel->getInsertID();
            $position = $this->penjualanModel->getPosition($newId, $params);

            $this->penjualanModel->db->transCommit();

            return $position;
            
        } catch (\Throwable $th) {
            $this->penjualanModel->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
        }
    }

    /**
     * Updates an existing penjualan.
     *
     * @param int|string $id penjualan ID to update.
     * @param array $data Data for updating the penjualan.
     * @return array on success.
     * @throws \Exception If updating penjualan fails.
     */
    public function update(array $data, array $params): array
    {
        $this->penjualanModel->db->transBegin();

        try {
            if (!$this->penjualanModel->update($data['id'], $data)) {
                throw new \Exception("Error updating penjualan.");
            }

            $position = $this->penjualanModel->getPosition($data['id'], $params);

            $this->penjualanModel->db->transCommit();

            return $position;
            
        } catch (\Throwable $th) {
            $this->penjualanModel->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
        }
    }

    /**
     * Deletes a penjualan.
     *
     * @param int|string $id penjualan ID to delete.
     * @return array on success.
     * @throws \Exception If deleting penjualan fails.
     */
    public function delete($id, array $params): array
    {
        $this->penjualanModel->db->transBegin();

        try {
            if (!$this->penjualanModel->delete($id)) {
                throw new \Exception("Error deleting penjualan.");
            }

            $position = $this->penjualanModel->getPosition($id, $params, true);

            $this->penjualanModel->db->transCommit();

            return $position;
            
        } catch (\Throwable $th) {
            $this->penjualanModel->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
        }
    }
}