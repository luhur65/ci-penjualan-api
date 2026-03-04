<?php

namespace App\Models;

use App\Models\UserRole;
use App\Models\Role;

class User extends CustomModel
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'fullname',
        'email',
        'username',
        'password',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'id'       => 'permit_empty|is_natural_no_zero',
        'fullname' => 'required|max_length[254]|min_length[3]|alpha_space',
        'email'    => 'required|max_length[254]|valid_email|is_unique[users.email,id,{id}]',
        'username' => 'required|max_length[30]|alpha_numeric_space|min_length[3]|is_unique[users.username,id,{id}]',
    ];
    protected $validationMessages   = [
        'id' => [
            'is_natural_no_zero' => 'ID must be a positive integer',
        ],
        'fullname' => [
            'required' => 'Fullname is required',
            'alpha_space' => 'Fullname only contains alphabet and space'
        ],
        'email' => [
            'required' => 'Email is required',
            'valid_email' => 'Email is invalid',
            'is_unique' => 'Sorry. That email has already been taken. Please choose another.',
        ],
        'username' => [
            'required' => 'Username is required',
            'is_unique' => 'Sorry. That username has already been taken. Please choose another.',
        ],
        'password' => [
            'required' => 'Password is required',
        ],
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = false;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    // function untuk menentukan method mana yang tidak perlu auth
    protected $exceptAuth = [
        'class'  => [],
        'method' => []
    ];

    // Model
    protected $userRoleModel;
    protected $roleModel;
    protected $menuModel;

    public function __construct()
    { 
        parent::__construct();
        $this->userRoleModel = new UserRole();
        $this->roleModel = new Role();
        $this->menuModel = new Menu();
    }

    // public function withRole($username)
    // {
    //     return $this->select('users.*, roles.rolename as role, roles.id as roleid')
    //         ->join('userroles', 'userroles.user_id = users.id', 'left')
    //         ->join('roles', 'roles.id = userroles.role_id', 'left')
    //         ->where('users.username', $username)
    //         ->first();
    // }

    public function withRoles($username)
    {
        $rows = $this->select('users.*, roles.rolename as role, roles.id as roleid')
            ->join('userroles', 'userroles.user_id = users.id', 'left')
            ->join('roles', 'roles.id = userroles.role_id', 'left')
            ->where('users.username', $username)
            ->get()
            ->getResultArray();
        // dd($rows);

        if (empty($rows)) return null;

        // Ambil data user dari baris pertama, hapus kolom role sementara
        $user = $rows[0];
        unset($user['role'], $user['roleid']);

        // Filter hanya baris yang punya role (bukan NULL), lalu mapping
        $user['roles'] = array_values(
            array_map(
                fn($row) => [
                    'id'       => $row['roleid'],
                    'role' => $row['role'],
                ],
                array_filter($rows, fn($row) => !empty($row['roleid']))
            )
        );

        return $user;
    }

    public function getAll()
    {
        $this->setRequestParameters();

        // ===== QUERY COUNT (CLONE BUILDER) =====
        // $countBuilder = $this->builder();
        // $countBuilder->select('id');

        // $this->filter($countBuilder);
        
        // ===== QUERY DATA =====
        $query = $this->builder();
        $query->select([
            'id',
            'fullname',
            'email',
            'username',
            'created_at',
            'updated_at'
        ]);

        // $query->where('deleted_at', NULL);

        $this->filter($query);
        $this->sort($query);
        $this->pagination($query);

        $this->totalRows = $query->countAllResults(false);
        $this->totalPages = ceil($this->totalRows / $this->params['limit']);

        // return $query->get()->getResult();
        return $query->get()->getResult();
    }


    public function findOne($id = null)
    {
        // Ambil data user
        $user = $this->db->table('users u')
            ->select([
                'u.id',
                'u.fullname',
                'u.email',
                'u.username',
            ])
            ->where('u.id', $id)
            ->get()
            ->getRow();

        if (!$user) {
            return null; // user tidak ditemukan
        }

        // Ambil roles user
        $roles = $this->db->table('roles r')
            ->select([
                'r.id as role_id',
                'r.rolename as role',
                'r.created_at',
                'r.updated_at'
            ])
            ->join('userroles ur', 'ur.role_id = r.id')
            ->where('ur.user_id', $id)
            ->get()
            ->getResult();

        // Return sebagai array
        return [
            'data' => $user,
            'roles' => $roles
        ];
    }

    public function sort($query) 
    {
        return $query->orderBy($this->params['sidx'], $this->params['sord']);
    }

    public function pagination($query)
    {
        return $query->limit($this->params['limit'], $this->params['offset']);
    }

    public function filter(&$query)
    {
        $filters = $this->params['filters'] ?? [];

        if (
            empty($filters) ||
            empty($filters['rules']) ||
            $filters['rules'][0]['data'] === ''
        ) {
            return $query;
        }

        $groupOp = strtoupper($filters['groupOp']);

        foreach ($filters['rules'] as $rule) {

            $field = $rule['field'];
            $value = trim($rule['data']);
            $isDate = in_array($field, ['created_at', 'updated_at']);

            // untuk field text
            $likeText = "%{$value}%";

            // untuk field DATE_FORMAT
            $likeDate = "'%{$value}%'";  // WAJIB STRING LITERAL

            $dateExpr = "DATE_FORMAT({$this->table}.{$field}, '%d-%m-%Y %H:%i:%s')";

            if ($groupOp === 'AND') {

                if ($isDate) {
                    // LIKE untuk date
                    $query->where("$dateExpr LIKE $likeDate", null, false);
                } else {
                    // LIKE normal CI4
                    $query->like("{$this->table}.{$field}", $value);
                }

            } else { // OR

                if ($isDate) {
                    $query->orWhere("$dateExpr LIKE $likeDate", null, false);
                } else {
                    $query->orLike("{$this->table}.{$field}", $value);
                }
            }
        }

        // $this->totalRows = $query->countAllResults(false);
        // $limit = $this->params['limit'] ?? 10;
        // $this->totalPages = ceil($this->totalRows / $limit);

        return $query;
    }

    public function proccesStore($data)
    {
        if(!$this->insert($data)) {
            throw new \Exception("Error inserting user.");
        }

        return true;
    }

    public function proccesUpdate($data)
    {
        $roleIds = $data['role_ids'] ?? [];
        unset($data['role_ids']);

        // Update user utama
        if(!$this->update($data['id'], $data)) {
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

    public function updatePasswordById(int $id, string $hash): bool
    {
        return (bool) $this->builder()
            ->where('id', $id)
            ->update(['password' => $hash]);
    }

    public function proccesDelete($id)
    {
        if(!$this->delete($id)) {
            throw new \Exception("Error deleting user.");
        }

        $this->userRoleModel
            ->where('user_id', $id)
            ->delete();

        return true;
    }

    public function printRecursiveMenu(array $menus, bool $hasParent = false, $currentMenu = null)
    {
        $string = $hasParent ? '<ul class="ml-4 nav nav-treeview">' : '';
        $url = env('frontend.baseURL'); // Menggunakan fungsi bawaan CI4

        foreach ($menus as $menu) {
            if ((count($menu['child']) > 0 || $menu['link'] != '' || $menu['aco_id'] != 0) && $this->hasClickableChild($menu)) {
                if ($menu['menuname'] == "DASHBOARD") {
                    $menu['class'] = "dashboard";
                }

                // Menyusun string URL
                $linkHref = count($menu['child']) > 0
                    ? 'javascript:void(0)'
                    : ($menu['link'] != '' ? strtolower($url . $menu['link']) : strtolower($url . $menu['menuexe']));

                // Validasi menu aktif
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

    public function getMenu($userid, $induk = 0)
    {
        // 1. Ambil semua role ID milik user dalam satu array datar
        $roleIds = $this->db->table('userroles')
            ->where('user_id', $userid)
            ->get()
            ->getResultArray();

        // Jika user tidak punya role sama sekali, langsung stop
        if (empty($roleIds)) return [];

        $ids = array_column($roleIds, 'role_id');

        // 2. Ambil menu yang terkait dengan kumpulan role tersebut dalam SATU query
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
            // Rekursi untuk child
            $childMenu = $this->getMenu($userid, $row->id);

            // Pengecekan permission
            $hasPermission = $this->hasPermission($row->class, $row->method, $userid);

            // Jika punya akses atau ini adalah menu folder (tanpa class/aco)
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

    public function hasPermission($class, $method, $userid)
    {
        $class = strtolower($class);
        $method = strtolower($method);

        return $this->_validatePermission($class, $method, $userid);
    }

    private function _validatePermission($class = null, $method = null, $userid)
    {
        if (in_array(strtolower($class), $this->exceptAuth['class'])) {
            return true;
        }

        // Builder untuk union
        $builderUnion = $this->db->table('acos')
            ->select('acos.id, acos.class, acos.method')
            ->join('acl', 'acos.id = acl.aco_id')
            ->join('userroles', 'acl.role_id = userroles.role_id')
            ->where('acos.class', $class)
            ->where('userroles.user_id', $userid);

        // Builder utama
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
                // Di CI4, getResult() mengembalikan array of objects
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
            // return $menu['aco_id'] != 0;
        }
    }
}
