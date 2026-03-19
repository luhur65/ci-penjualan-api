<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Acl;
use Config\Database;
use App\Services\UserService;
use App\Services\AclService;
use App\Libraries\MenuHTML;

/**
 * Service class for handling Role business logic.
 */
class RoleService
{
    protected $roleModel;
    protected $aclService;
    protected $userService;
    protected $db;

    public function __construct()
    {
        $this->roleModel = new Role();
        $this->aclService = new AclService();
        $this->userService = new UserService();
        $this->db = Database::connect();
    }

    /**
     * Retrieves all roles with pagination.
     *
     * @param array $params Request parameters for filtering and pagination.
     * @return array Array containing role data and pagination attributes.
     */
    public function getAllRoles(array $params)
    {
        $roles = $this->roleModel->setRequestParameters($params)->getAll();

        return [
            'data' => $roles['data'],
            'attributes' => [
                'totalRows' => $roles['totalRows'],
                'totalPages' => $roles['totalPages']
            ]
        ];
    }

    /**
     * Retrieves a role by ID.
     *
     * @param int|string $id Role ID to retrieve.
     * @return array on success.
     */
    public function getRoleById($id)
    {
        return $this->roleModel->findOne($id);
    }

    /**
     * Stores a new role.
     *
     * @param array $data Data for creating the role.
     * @param array $params Request parameters for filtering and pagination.
     * @return array on success.
     * @throws \Exception If inserting role fails.
     */
    public function create(array $data, array $params): array
    {
        $this->roleModel->db->transBegin();

        try {
            if (!$this->roleModel->insert($data)) {
                throw new \Exception("Error storing role.");
            }

            $newId = $this->roleModel->getInsertID();
            $position = $this->roleModel->getPosition($newId, $params);

            $this->roleModel->db->transCommit();
            return $position;
        } catch (\Exception $e) {
            $this->roleModel->db->transRollback();
            log_message('error', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Updates an existing role and its associated ACLs.
     *
     * @param array $data Data for updating the role, including acosIds.
     * @param array $params Request parameters for filtering and pagination.
     * @return array on success.
     * @throws \Exception If updating role fails.
     */
    public function update(array $data, array $params): array
    {
        $this->roleModel->db->transBegin();
        
        try {

            if (!$this->roleModel->update($data['id'], $data)) {
                throw new \Exception("Error updating role.");
            }
    
            $this->aclService->deleteByRoleId($data['id']);
    
            $acos = [];
            foreach ($data['acosIds'] as $acoId) {
                $acos[] = [
                    'aco_id'  => $acoId,
                    'role_id' => $data['id'],
                    'modified_by' => $data['modified_by'] ?? null,
                ];
            }
            
            if (!empty($acos)) {
                $this->aclService->insertBatch($acos);
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
                $listMenu = MenuHTML::render($getMenu, false);
    
                $this->db->table('users')
                    ->where('id', $userId)
                    ->update([
                        'menu' => $listMenu
                    ]);
            }

            $position = $this->roleModel->getPosition($data['id'], $params);

            $this->roleModel->db->transCommit();
            return $position;

        } catch (\Throwable $th) {
            $this->roleModel->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
            
        }

    }

    /**
     * Deletes a role.
     *
     * @param int|string $id Role ID to delete.
     * @param array $params Request parameters for filtering and pagination.
     * @return array on success.
     * @throws \Exception If deleting role fails or role is not found.
     */
    public function delete($id, array $params): array
    {
        $role = $this->roleModel->find($id);

        $this->roleModel->db->transBegin();    

        try {

            if (empty($role)) {
                throw new \Exception("Role with ID {$id} not found.");
            }
    
            $this->roleModel->delete($id);

            $position = $this->roleModel->getPosition($id, $params, true);
            $this->roleModel->db->transCommit();
            return $position;
            
        } catch (\Throwable $th) {
            $this->roleModel->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
        }

    }
}
