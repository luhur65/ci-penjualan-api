<?php

namespace App\Services;

use App\Models\Acl;

/**
 * Service class for handling ACL business logic.
 */
class AclService
{
    protected $aclModel;

    public function __construct()
    {
        $this->aclModel = new Acl();
    }

    /**
     * Stores a new ACL.
     *
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function create(array $data)
    {
        if (!$this->aclModel->insert($data)) {
            throw new \Exception("Error storing ACL.");
        }

        return true;
    }

    /**
     * Updates an existing ACL.
     *
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function update(array $data)
    {
        if (!$this->aclModel->update($data['id'], $data)) {
            throw new \Exception("Error updating ACL.");
        }

        return true;
    }

    /**
     * Deletes an ACL by ID.
     *
     * @param int|string $id
     * @return bool
     * @throws \Exception
     */
    public function delete($id)
    {
        if (!$this->aclModel->delete($id)) {
            throw new \Exception("Error deleting ACL.");
        }

        return true;
    }

    /**
     * Deletes all ACL records associated with a given Role ID.
     *
     * @param int|string $roleId
     * @return bool
     */
    public function deleteByRoleId($roleId)
    {
        $this->aclModel->where('role_id', $roleId)->delete();
        return true;
    }

    /**
     * Inserts multiple ACL records.
     *
     * @param array $acos
     * @return bool|int Number of rows inserted or false on failure
     */
    public function insertBatch(array $acos)
    {
        return $this->aclModel->insertBatch($acos);
    }
}
