<?php

namespace App\Services;

use App\Models\Acos;

/**
 * Service class for handling Acos business logic.
 */
class AcosService
{
    protected $acosModel;

    public function __construct()
    {
        $this->acosModel = new Acos();
    }

    /**
     * Stores a new ACOS.
     *
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function create(array $data)
    {
        if (!$this->acosModel->insert($data)) {
            throw new \Exception("Error storing ACOS.");
        }

        return true;
    }

    /**
     * Updates an existing ACOS.
     *
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function update(array $data)
    {
        if (!$this->acosModel->update($data['id'], $data)) {
            throw new \Exception("Error updating ACOS.");
        }

        return true;
    }

    /**
     * Deletes an ACOS by ID.
     *
     * @param int|string $id
     * @return bool
     * @throws \Exception
     */
    public function delete($id)
    {
        if (!$this->acosModel->delete($id)) {
            throw new \Exception("Error deleting ACOS.");
        }

        return true;
    }
}
