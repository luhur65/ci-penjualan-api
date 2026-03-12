<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserRole;
use Config\Database;

/**
 * Service class for handling User business logic.
 */
class UserService
{
    protected $userModel;
    protected $userRoleModel;
    protected $db;
    public function __construct()
    {
        $this->userModel = new User();
        $this->userRoleModel = new UserRole();
        $this->db = Database::connect();
    }

    /**
     * Creates a new user.
     *
     * @param array $data Data for creating the user.
     * @return bool True on success.
     * @throws \Exception If inserting user fails.
     */
    public function create(array $data): bool
    {
        if (!$this->userModel->insert($data)) {
            throw new \Exception("Error inserting user.");
        }

        return true;
    }

    /**
     * Updates an existing user and their roles.
     *
     * @param array $data Data for updating the user, including role_ids.
     * @return bool True on success.
     * @throws \Exception If updating user fails.
     */
    public function update(array $data): bool
    {
        $roleIds = $data['role_ids'] ?? [];
        unset($data['role_ids']);

        // Update user utama
        if (!$this->userModel->update($data['id'], $data)) {
            throw new \Exception("Error updating user.");
        }

        // Hapus role lama
        $this->userRoleModel
            ->where('user_id', $data['id'])
            ->delete();

        // Insert role baru
        foreach ($roleIds as $roleId) {
            $this->userRoleModel->insert([
                'user_id' => $data['id'],
                'role_id' => $roleId,
            ]);
        }

        return true;
    }

    /**
     * Deletes a user and their associated roles.
     *
     * @param int|string $id User ID to delete.
     * @return bool True on success.
     * @throws \Exception If deleting user fails.
     */
    public function delete($id): bool
    {
        if (!$this->userModel->delete($id)) {
            throw new \Exception("Error deleting user.");
        }

        $this->userRoleModel
            ->where('user_id', $id)
            ->delete();

        return true;
    }

    /**
     * Prints a recursive menu structure as HTML.
     *
     * @param array $menus The menu array structure.
     * @param bool $hasParent Whether the current level has a parent.
     * @param object|null $currentMenu The current active menu.
     * @return string The generated HTML string.
     */
    public function printRecursiveMenu(array $menus, bool $hasParent = false, $currentMenu = null): string
    {
        $string = $hasParent ? '<ul class="ml-4 nav nav-treeview">' : '';
        $url = env('frontend.baseURL');

        foreach ($menus as $menu) {
            if ((count($menu['child']) > 0 || $menu['link'] != '' || $menu['aco_id'] != 0) && $this->hasClickableChild($menu)) {
                if ($menu['menuname'] == "DASHBOARD") {
                    $menu['class'] = "dashboard";
                }

                $linkHref = count($menu['child']) > 0
                    ? 'javascript:void(0)'
                    : ($menu['link'] != '' ? strtolower($url . $menu['link']) : strtolower($url . $menu['menuexe']));

                $isActive = (isset($currentMenu->id) && $currentMenu->id == $menu['menuid']) ? 'active hover' : '';
                $icon = strtolower($menu['menu_icon']) ?? 'far fa-circle';
                $linkId = count($menu['child']) > 0 ? '' : 'link-' . $menu['class'];
                $childIcon = count($menu['child']) > 0 ? '<i class="right fas fa-angle-left"></i>' : '';
                $childMenu = count($menu['child']) > 0 ? $this->printRecursiveMenu($menu['child'], true, $currentMenu) : '';

                $string .= '
                <li class="nav-item">
                  <a id="' . $linkId . '" href="' . $linkHref . '" class="nav-link ' . $isActive . '">
                    <i class="nav-icon ' . $icon . '"></i>
                    <p>
                      ' . $menu['menuname'] . '
                      ' . $childIcon . '
                    </p>
                  </a>
                  ' . $childMenu . '
                </li>
              ';
            }
        }

        $string .= $hasParent ? '</ul>' : '';
        return $string;
    }

    /**
     * Retrieves the menu for a given user.
     *
     * @param int $userid The user ID.
     * @param int $induk The parent menu ID.
     * @return array The menu structure.
     */
    public function getMenu($userid, $induk = 0): array
    {
        $roleIds = $this->db->table('userroles')
            ->where('user_id', $userid)
            ->get()
            ->getResultArray();

        if (empty($roleIds)) return [];

        $ids = array_column($roleIds, 'role_id');

        $menuData = $this->db->table('menus m')
            ->select('m.id, m.aco_id, m.menu_seq, m.menuname, m.menu_icon, a.class, a.method, m.link, m.menukode, m.menu_parent')
            ->join('acos a', 'm.aco_id = a.id', 'left')
            ->join('acl l', 'l.aco_id = a.id AND l.role_id IN (' . implode(',', array_map('intval', $ids)) . ')', 'left', false)
            ->where('m.menu_parent', $induk)
            ->groupBy('m.id')
            ->orderBy('m.menu_seq', 'ASC')
            ->get()
            ->getResult();

        $menus = [];
        foreach ($menuData as $row) {
            $childMenu = $this->getMenu($userid, $row->id);
            $hasPermission = $this->hasPermission($row->class, $row->method, $userid);

            if ($hasPermission || $row->aco_id == 0 || $row->class == null) {
                $menus[] = [
                    'menuid'      => $row->id,
                    'aco_id'      => $row->aco_id,
                    'menuname'    => $row->menuname,
                    'menu_icon'   => $row->menu_icon,
                    'link'        => $row->link,
                    'menuno'      => substr($row->menukode, -1),
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
        // Use reflection to access the protected $exceptAuth property on the User model
        $reflection = new \ReflectionClass($this->userModel);
        $exceptAuthProperty = $reflection->getProperty('exceptAuth');
        $exceptAuthProperty->setAccessible(true);
        $exceptAuth = $exceptAuthProperty->getValue($this->userModel);

        if (in_array(strtolower($class), $exceptAuth['class'])) {
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

        if ($this->in_array_custom($method, $data) == false && in_array($method, $exceptAuth['method']) == false) {
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

    public function hasClickableChild(array $menu): bool
    {
        if (count($menu['child']) > 0) {
            foreach ($menu['child'] as $menuChild) {
                if ($this->hasClickableChild($menuChild)) {
                    return true;
                }
            }
        } else {
            return $this->isClickableChild($menu);
        }

        return false;
    }

    public function isClickableChild(array $menu): bool
    {
        if ($menu['menu_parent'] == 0) {
            return true;
        } else {
            return $menu['aco_id'] != 0 && $menu['menuexe'] != '/';
        }
    }
}
