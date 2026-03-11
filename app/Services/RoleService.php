<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Acl;
use Config\Database;
use App\Services\UserService;

/**
 * Service class for handling Role business logic.
 */
class RoleService
{
    protected $roleModel;
    protected $aclModel;
    protected $userService;
    protected $db;

    public function __construct()
    {
        $this->roleModel = new Role();
        $this->aclModel = new Acl();
        $this->userService = new UserService();
        $this->db = Database::connect();
    }

    /**
     * Stores a new role.
     *
     * @param array $data Data for creating the role.
     * @return bool True on success.
     * @throws \Exception If inserting role fails.
     */
    public function create(array $data): bool
    {
        if (!$this->roleModel->insert($data)) {
            throw new \Exception("Error storing role.");
        }

        return true;
    }

    /**
     * Updates an existing role and its associated ACLs.
     *
     * @param array $data Data for updating the role, including acosIds.
     * @return bool True on success.
     * @throws \Exception If updating role fails.
     */
    public function update(array $data): bool
    {
        if (!$this->roleModel->update($data['id'], $data)) {
            throw new \Exception("Error updating role.");
        }

        $this->aclModel->where('role_id', $data['id'])->delete();

        $acos = [];
        foreach ($data['acosIds'] as $acoId) {
            $acos[] = [
                'aco_id'  => $acoId,
                'role_id' => $data['id'],
            ];
        }

        if (!empty($acos)) {
            $this->aclModel->insertBatch($acos);
        }

        $queryUser = $this->db->table('userroles a')
            ->select('a.user_id')
            ->where('a.role_id', $data['id'])
            ->groupBy('a.user_id')
            ->get()
            ->getResultArray();

        foreach ($queryUser as $item) {
            $userId = $item['user_id'];

            $getMenu = $this->userService->getMenu($userId);
            $listMenu = $this->userService->printRecursiveMenu($getMenu, false);

            $this->db->table('users')
                ->where('id', $userId)
                ->update([
                    'menu' => $listMenu
                ]);
        }

        return true;
    }

    /**
     * Deletes a role.
     *
     * @param int|string $id Role ID to delete.
     * @return bool True on success.
     * @throws \Exception If deleting role fails or role is not found.
     */
    public function delete($id): bool
    {
        $role = $this->roleModel->find($id);

        if (empty($role)) {
            throw new \Exception("Role with ID {$id} not found.");
        }

        $this->roleModel->delete($id);

        return true;
    }
}
