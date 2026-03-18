<?php

namespace App\Models;

use App\Models\UserRole;
use App\Models\Role;

class User extends CustomModel
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $sortDirection    = 'ASC';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'fullname',
        'email',
        'username',
        'password',
        'modified_by',
        'status_aktif'
    ];

    protected $fieldMap = [
        'fullname' => 'users.fullname',
        'email' => 'users.email',
        'username' => 'users.username',
        'statusaktif' => 'users.status_aktif',
        'modifiedby' => 'users.modified_by',
        'created_at' => 'users.created_at',
        'updated_at' => 'users.updated_at'
    ];

    protected $searchableFields = [
        'fullname',
        'email',
        'username',
        'statusaktif',
        'modifiedby',
        'created_at',
        'updated_at'
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
        'statusaktif' => 'permit_empty|is_natural_no_zero',
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
        'statusaktif' => [
            'is_natural_no_zero' => 'Status aktif is invalid',
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

    public function __construct()
    {
        parent::__construct();
        $this->userRoleModel = new UserRole();
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
        // ===== QUERY COUNT (CLONE BUILDER) =====
        // $countBuilder = $this->builder();
        // $countBuilder->select('id');

        // $this->filter($countBuilder);

        // ===== QUERY DATA =====
        $query = $this->builder();
        $query->select([
            'users.id',
            'users.fullname',
            'users.email',
            'users.username',
            'users.modified_by as modifiedby',
            'parameters.memo as statusaktif',
            'users.created_at',
            'users.updated_at'
        ])
            ->join('parameters', 'parameters.id = users.status_aktif', 'left');

        // $query->where('deleted_at', NULL);

        // return $query->get()->getResult();
        return $this->datatable($query);
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
                'u.status_aktif as statusaktif',
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

    // kalau mau di overide pun bisa
    // public function sort(&$query)
    // {
    //     return $query->orderBy($this->params['sidx'], $this->params['sord']);
    // }

    // public function pagination(&$query)
    // {
    //     return $query->limit($this->params['limit'], $this->params['offset']);
    // }

    // public function filter(&$query)
    // {
    //     $filters = $this->params['filters'] ?? [];

    //     if (
    //         empty($filters) ||
    //         empty($filters['rules']) ||
    //         $filters['rules'][0]['data'] === ''
    //     ) {
    //         return $query;
    //     }

    //     $groupOp = strtoupper($filters['groupOp']);

    //     foreach ($filters['rules'] as $rule) {

    //         $field = $rule['field'];
    //         $value = trim($rule['data']);
    //         $isDate = in_array($field, ['created_at', 'updated_at']);

    //         // untuk field text
    //         $likeText = "%{$value}%";

    //         // untuk field DATE_FORMAT
    //         $likeDate = "'%{$value}%'";  // WAJIB STRING LITERAL

    //         $dateExpr = "DATE_FORMAT({$this->table}.{$field}, '%d-%m-%Y %H:%i:%s')";

    //         if ($groupOp === 'AND') {

    //             if ($isDate) {
    //                 // LIKE untuk date
    //                 $query->where("$dateExpr LIKE $likeDate", null, false);
    //             } else if ($field == 'statusaktif') {
    //                 $query->where('parameter.id', $value);
    //             } else {
    //                 // LIKE normal CI4
    //                 $query->like("{$this->table}.{$field}", $value);
    //             }
    //         } else { // OR

    //             if ($isDate) {
    //                 $query->orWhere("$dateExpr LIKE $likeDate", null, false);
    //             } else {
    //                 $query->orLike("{$this->table}.{$field}", $value);
    //             }
    //         }
    //     }

    //     // \dd($query->getCompiledSelect());

    //     // $this->totalRows = $query->countAllResults(false);
    //     // $limit = $this->params['limit'] ?? 10;
    //     // $this->totalPages = ceil($this->totalRows / $limit);

    //     return $query;
    // }

    public function updatePasswordById(int $id, string $hash): bool
    {
        return (bool) $this->builder()
            ->where('id', $id)
            ->update(['password' => $hash]);
    }
}
