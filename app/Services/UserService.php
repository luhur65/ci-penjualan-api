<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserRole;
use App\Models\UserAcl;
use App\Services\ParameterService;
use Config\Database;

/**
 * Service class for handling User business logic.
 */
class UserService
{
    protected $userModel;
    protected $parameterService;
    protected $userRoleModel;
    protected $userAclModel;
    protected $db;
    protected $exceptAuth = [
        'class'  => [],
        'method' => []
    ];

    public function __construct()
    {
        $this->userModel = new User();
        $this->parameterService = new ParameterService();
        $this->userRoleModel = new UserRole();
        $this->userAclModel = new UserAcl();
        $this->db = Database::connect();
    }

    public function getAllUsers(array $params)
    {
        $users = $this->userModel->setRequestParameters($params)->getAll();

        return [
            'data' => $users['data'],
            'attributes' => [
                'totalRows' => $users['totalRows'],
                'totalPages' => $users['totalPages']
            ]
        ];
    }

    /**
     * Retrieves a user detail by ID.
     *
     * @param int|string $id User ID to retrieve.
     * @return array on success.
     */
    public function getUserDetail($id, array $params = []): array
    {
        $user = $this->userModel->findOne($id);
        if (!$user) {
            return [];
        }
        
        $roles = $this->getRoleByUserId($id, $params);
        $acls = $this->getAclByUserId($id, $params);
        
        return [
            'data'  => $user,
            'roles' => $roles['data'],
            'acls'  => $acls['data']
        ];
    }

    /**
     * Retrieves a user by ID.
     *
     * @param int|string $id User ID to retrieve.
     * @return array on success.
     */
    public function getUserById($id)
    {
        return $this->userModel->findOne($id);
    }

    /**
     * Retrieves a user role by user ID.
     *
     * @param int|string $id User ID to retrieve.
     * @return array on success.
     */
    public function getRoleByUserId($id, array $params)
    {
        return $this->userRoleModel->setRequestParameters($params)->getRoleByUserId($id);
    }

    /**
     * Retrieves a user ACL by user ID.
     *
     * @param int|string $id User ID to retrieve.
     * @return array on success.
     */
    public function getAclByUserId($id, array $params)
    {
        return $this->userAclModel->setRequestParameters($params)->getAclByUserId($id);
    }

    /**
     * Creates a new user.
     *
     * @param array $data Data for creating the user.
     * @return array on success.
     * @throws \Exception If inserting user fails.
     */
    public function create(array $data, array $params): array
    {
        $this->userModel->db->transBegin();

        try {
            $parameter = $this->parameterService->getParameterById($data['statusaktif']);
            $data['status_aktif'] = $parameter['id'] ?? 1;
            if (!$this->userModel->insert($data)) {
                throw new \Exception("Error inserting user.");
            }

            $newId = $this->userModel->getInsertID();
            $position = $this->userModel->getPosition($newId, $params);

            $this->userModel->db->transCommit();

            return $position;
            
        } catch (\Throwable $th) {
            $this->userModel->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
        }
    }

    /**
     * Updates an existing user and their roles.
     *
     * @param array $data Data for updating the user, including role_ids.
     * @return array True on success.
     * @throws \Exception If updating user fails.
     */
    public function update(array $data, array $params): array
    {
        $roleIds = $data['role_ids'] ?? [];
        $aclIds = $data['acls'] ?? [];
        unset($data['role_ids']);
        unset($data['acls']);

        // Mulai pelindung transaksi
        $this->userModel->db->transBegin();

        try {

            $parameter = $this->parameterService->getParameterById($data['statusaktif']);
            $data['status_aktif'] = $parameter['id'] ?? 1;

            if (!$this->userModel->update($data['id'], $data)) {
                throw new \Exception("Error updating user.");
            }

            // \dd($params);
            $position = $this->userModel->getPosition($data['id'], $params);

            // Audit Log Bulk Delete untuk Role dan ACL lama sebelum didelete
            helper('audit');
            $oldRoles = $this->userRoleModel->where('user_id', $data['id'])->findAll();
            if (!empty($oldRoles)) {
                audit_log('userroles', 'BULK_DELETE', $data['id'], $oldRoles, null);
            }
            $this->userRoleModel->where('user_id', $data['id'])->delete();

            // Mematikan log otomatis saat looping, dan merekap data
            $this->userRoleModel->allowCallbacks(false);
            $newRolesData = [];
            foreach ($roleIds as $roleId) {
                $rolePayload = [
                    'user_id' => $data['id'],
                    'role_id' => $roleId,
                    'modified_by' => $data['modified_by'] ?? null,
                ];
                $this->userRoleModel->insert($rolePayload);
                $newRolesData[] = $rolePayload;
            }
            if (!empty($newRolesData)) {
                audit_log('userroles', 'BULK_CREATE', $data['id'], null, $newRolesData);
            }
            $this->userRoleModel->allowCallbacks(true);

            // Audit Log Bulk Delete ACL
            $oldAcls = $this->userAclModel->where('user_id', $data['id'])->findAll();
            if (!empty($oldAcls)) {
                audit_log('useracl', 'BULK_DELETE', $data['id'], $oldAcls, null);
            }
            $this->userAclModel->where('user_id', $data['id'])->delete();

            // Mematikan log otomatis saat looping ACL
            $this->userAclModel->allowCallbacks(false);
            $newAclsData = [];
            foreach ($aclIds as $acoId) {
                $aclPayload = [
                    'user_id' => $data['id'],
                    'aco_id' => $acoId,
                    'modified_by' => $data['modified_by'] ?? null,
                ];
                $this->userAclModel->insert($aclPayload);
                $newAclsData[] = $aclPayload;
            }
            if (!empty($newAclsData)) {
                audit_log('useracl', 'BULK_CREATE', $data['id'], null, $newAclsData);
            }
            $this->userAclModel->allowCallbacks(true);

            // Komit transaksi jika semua perintah sukses
            $this->userModel->db->transCommit();
            return $position;

        } catch (\Throwable $th) {
            // Batalkan seluruh perubahan jika ada satu saja yang gagal
            $this->userModel->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
        }
    }

    /**
     * Deletes a user and their associated roles.
     *
     * @param int|string $id User ID to delete.
     * @return array on success.
     * @throws \Exception If deleting user fails.
     */
    public function delete($id, array $params): array
    {
        $this->userModel->db->transBegin();

        try {
            // 1. Cari tetangga DULU sebelum delete
            $position = $this->userModel->getPosition($id, $params, true);

            // 2. Baru delete user
            if (!$this->userModel->delete($id)) {
                throw new \Exception("Error deleting user.");
            }

            // Audit Log Bulk Delete untuk Role dan ACL sebelum didelete dari database
            helper('audit');
            $oldRoles = $this->userRoleModel->where('user_id', $id)->findAll();
            if (!empty($oldRoles)) {
                audit_log('userroles', 'BULK_DELETE', $id, $oldRoles, null);
            }
            $oldAcls = $this->userAclModel->where('user_id', $id)->findAll();
            if (!empty($oldAcls)) {
                audit_log('useracl', 'BULK_DELETE', $id, $oldAcls, null);
            }

            // 3. Hapus roles dan acls
            $this->userRoleModel->where('user_id', $id)->delete();
            $this->userAclModel->where('user_id', $id)->delete();

            $this->userModel->db->transCommit();
            return $position;
        } catch (\Throwable $th) {
            $this->userModel->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
        }
    }

    
    // public function printRecursiveMenu(array $menus, bool $hasParent = false, $currentMenu = null): string
    // {
    //     $string = $hasParent ? '<ul class="ml-4 nav nav-treeview">' : '';
    //     $url = env('frontend.baseURL');

    //     foreach ($menus as $menu) {
    //         if ((count($menu['child']) > 0 || $menu['link'] != '' || $menu['aco_id'] != 0) && $this->hasClickableChild($menu)) {
    //             if ($menu['menuname'] == "DASHBOARD") {
    //                 $menu['class'] = "dashboard";
    //             }

    //             $linkHref = count($menu['child']) > 0
    //                 ? 'javascript:void(0)'
    //                 : ($menu['link'] != '' ? strtolower($url . $menu['link']) : strtolower($url . $menu['menuexe']));

    //             $isActive = (isset($currentMenu->id) && $currentMenu->id == $menu['menuid']) ? 'active hover' : '';
    //             $icon = strtolower($menu['menu_icon']) ?? 'far fa-circle';
    //             $linkId = count($menu['child']) > 0 ? '' : 'link-' . $menu['class'];
    //             $childIcon = count($menu['child']) > 0 ? '<i class="right fas fa-angle-left"></i>' : '';
    //             $childMenu = count($menu['child']) > 0 ? $this->printRecursiveMenu($menu['child'], true, $currentMenu) : '';

    //             $string .= '
    //             <li class="nav-item">
    //               <a id="' . $linkId . '" href="' . $linkHref . '" class="nav-link ' . $isActive . '">
    //                 <i class="nav-icon ' . $icon . '"></i>
    //                 <p>
    //                   ' . $menu['menuname'] . '
    //                   ' . $childIcon . '
    //                 </p>
    //               </a>
    //               ' . $childMenu . '
    //             </li>
    //           ';
    //         }
    //     }

    //     $string .= $hasParent ? '</ul>' : '';
    //     return $string;
    // }

    /**
     * Retrieves the menu for a given user.
     *
     * @param int $userid The user ID.
     * @param int $induk The parent menu ID.
     * @return array The menu structure.
     */
    public function getMenu($userid, $induk = 0)
    {
        // 1. Ambil semua role ID milik user
        $roleIds = $this->db->table('userroles')
            ->where('user_id', $userid)
            ->get()
            ->getResultArray();

        // Jika user tidak punya role sama sekali, langsung stop
        if (empty($roleIds)) return [];

        $ids = array_column($roleIds, 'role_id');

        // 2. Ambil semua menu sekaligus tanpa memfilter menu_parent (N+1 fix)
        // Kita juga join ke 'acl' untuk role yang dimiliki
        $menuData = $this->db->table('menus m')
            ->select('m.id, m.aco_id, m.menu_seq, m.menuname, m.menu_icon, a.class, a.method, m.link, m.menukode, m.menu_parent')
            ->join('acos a', 'm.aco_id = a.id', 'left')
            ->join('acl l', 'l.aco_id = a.id AND l.role_id IN (' . implode(',', array_map('intval', $ids)) . ')', 'left', false)
            ->groupBy('m.id')
            ->orderBy('m.menu_parent', 'ASC')
            ->orderBy('m.menu_seq', 'ASC')
            ->get()
            ->getResult();

        // 3. Pre-load permission methods for user to avoid calling hasPermission in loop
        $userPermissions = [];
        // Role permissions
        $rolePerms = $this->db->table('acos a')
            ->select('a.method, a.class')
            ->join('acl l', 'a.id = l.aco_id')
            ->whereIn('l.role_id', $ids)
            ->get()
            ->getResult();
        foreach ($rolePerms as $perm) {
            $userPermissions[strtolower($perm->class)][strtolower($perm->method)] = true;
        }

        // User permissions (useracl)
        $userPerms = $this->db->table('acos a')
            ->select('a.method, a.class')
            ->join('useracl u', 'a.id = u.aco_id')
            ->where('u.user_id', $userid)
            ->get()
            ->getResult();
        foreach ($userPerms as $perm) {
            $userPermissions[strtolower($perm->class)][strtolower($perm->method)] = true;
        }

        // 4. Kelompokkan menu berdasarkan menu_parent
        $groupedMenus = [];
        foreach ($menuData as $row) {
            $groupedMenus[$row->menu_parent][] = $row;
        }

        // 5. Bangun tree menu menggunakan fungsi in-memory recursif
        return $this->_buildMenuTree($induk, $groupedMenus, $userPermissions);
    }

    private function _buildMenuTree($parentId, array &$groupedMenus, array &$userPermissions)
    {
        $menus = [];
        if (!isset($groupedMenus[$parentId])) {
            return $menus;
        }

        foreach ($groupedMenus[$parentId] as $row) {
            // Rekursi untuk child (in-memory)
            $childMenu = $this->_buildMenuTree($row->id, $groupedMenus, $userPermissions);

            // Pengecekan permission (in-memory)
            $class = strtolower((string)$row->class);
            $method = strtolower((string)$row->method);

            $hasPermission = false;
            if (in_array($class, $this->exceptAuth['class']) || in_array($method, $this->exceptAuth['method'])) {
                $hasPermission = true;
            } else if (isset($userPermissions[$class][$method])) {
                $hasPermission = true;
            }

            // Jika punya akses atau ini adalah menu folder (tanpa class/aco)
            if ($hasPermission || $row->aco_id == 0 || $row->class == null) {
                $menus[] = [
                    'menuid'      => $row->id,
                    'aco_id'      => $row->aco_id,
                    'menuname'    => $row->menuname,
                    'menu_icon'   => $row->menu_icon,
                    'link'        => $row->link,
                    'menuno'      => substr((string)$row->menukode, -1),
                    'menukode'    => $row->menukode,
                    'menuexe'     => $row->class,
                    'class'       => $row->class,
                    'child'       => $childMenu,
                    'menu_parent' => $row->menu_parent,
                ];
            }
        }

        return $menus;
    }

    /**
     * Checks if a user has permission for a specific class and method.
     *
     * @param string $class The class name.
     * @param string $method The method name.
     * @param int $userid The user ID.
     * @return bool True if permitted, false otherwise.
     */
    public function hasPermission($class, $method, $userid): bool
    {
        $class = strtolower($class);
        $method = strtolower($method);

        return $this->_validatePermission($class, $method, $userid);
    }

    private function _validatePermission($class = null, $method = null, $userid): bool
    {
        if (in_array(strtolower($class), $this->exceptAuth['class'])) {
            return true;
        }

        $builderUnion = $this->db->table('acos')
            ->select('acos.id, acos.class, acos.method')
            ->join('acl', 'acos.id = acl.aco_id')
            ->join('userroles', 'acl.role_id = userroles.role_id')
            ->where('acos.class', $class)
            ->where('userroles.user_id', $userid);

        $builder = $this->db->table('acos')
            ->select('acos.id, acos.class, acos.method')
            ->join('useracl', 'acos.id = useracl.aco_id')
            ->where('acos.class', $class)
            ->where('useracl.user_id', $userid)
            ->unionAll($builderUnion);

        $data = $builder->get()->getResult();

        if ($this->in_array_custom($method, $data) == false && in_array($method, $this->exceptAuth['method']) == false) {
            return false;
        }

        return true;
    }

    public function in_array_custom($item, $array): bool
    {
        $found = array_search(
            $item,
            array_map(function ($v) {
                return strtolower($v->method);
            }, $array)
        );

        return empty($found) && $found !== 0 ? false : true;
    }
}
