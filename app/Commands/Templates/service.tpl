<?php

namespace App\Services;

use App\Models\{name};
use Config\Database;

class {name}Service
{
    protected ${var}Model;
    protected $db;

    public function __construct()
    {
        $this->{var}Model = new {name}();
        $this->db = Database::connect();
    }

    public function getAll{name}(array $params)
    {
        $data = $this->{var}Model->setRequestParameters($params)->getAll();

        return [
            'data' => $data['data'],
            'attributes' => [
                'totalRows' => $data['totalRows'],
                'totalPages' => $data['totalPages']
            ]
        ];
    }

    /**
     * Retrieves a {var} by ID.
     *
     * @param int|string $id {var} ID to retrieve.
     * @return array on success.
     */
    public function getById{name}($id)
    {
        return $this->{var}Model->findOne($id);
    }

    /**
     * Creates a new {var}.
     *
     * @param array $data Data for creating the {var}.
     * @return array on success.
     * @throws \Exception If inserting {var} fails.
     */
    public function create(array $data, array $params): array
    {
        $this->{var}Model->db->transBegin();

        try {
            if (!$this->{var}Model->insert($data)) {
                throw new \Exception("Error inserting {var}.");
            }

            $newId = $this->{var}Model->getInsertID();
            $position = $this->{var}Model->getPosition($newId, $params);

            $this->{var}Model->db->transCommit();

            return $position;
            
        } catch (\Throwable $th) {
            $this->{var}Model->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
        }
    }

    /**
     * Updates an existing {var}.
     *
     * @param int|string $id {var} ID to update.
     * @param array $data Data for updating the {var}.
     * @return array on success.
     * @throws \Exception If updating {var} fails.
     */
    public function update(array $data, array $params): array
    {
        $this->{var}Model->db->transBegin();

        try {
            if (!$this->{var}Model->update($data['id'], $data)) {
                throw new \Exception("Error updating {var}.");
            }

            $position = $this->{var}Model->getPosition($data['id'], $params);

            $this->{var}Model->db->transCommit();

            return $position;
            
        } catch (\Throwable $th) {
            $this->{var}Model->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
        }
    }

    /**
     * Deletes a {var}.
     *
     * @param int|string $id {var} ID to delete.
     * @return array on success.
     * @throws \Exception If deleting {var} fails.
     */
    public function delete($id, array $params): array
    {
        $this->{var}Model->db->transBegin();

        try {
            if (!$this->{var}Model->delete($id)) {
                throw new \Exception("Error deleting {var}.");
            }

            $position = $this->{var}Model->getPosition($id, $params, true);

            $this->{var}Model->db->transCommit();

            return $position;
            
        } catch (\Throwable $th) {
            $this->{var}Model->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
        }
    }
}