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
    protected $useSoftDeletes   = true;
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
        'fullname' => 'required|max_length[254]|min_length[3]|alpha_space',
        'email' => 'required|max_length[254]|valid_email|is_unique[users.email]',
        'username' => 'required|max_length[30]|alpha_numeric_space|min_length[3]|is_unique[users.username]',
    ];
    protected $validationMessages   = [
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

    public function role()
    {
        return $this->hasMany(UserRole::class, 'role_id', 'id');
    }

    public function get()
    {
        $this->setRequestParameters();

        // Base query
        $builder = $this->builder();
        $builder->select([
            'id',
            'fullname',
            'email',
            'username',
            'created_at',
            'updated_at'
        ]);

        $this->filter($builder);
        $this->sort($builder);
        $this->pagination($builder);

        $data = $builder->get()->getResult();

        $this->totalRows = $builder->countAllResults(false);
        $this->totalPages = ($this->totalRows > 0) ? ceil($this->totalRows / $this->params['limit']) : 1;

        return $data;
    }

    public function find1($id = null)
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

        $this->totalRows = $query->countAllResults(false);
        $limit = $this->params['limit'] ?? 10;
        $this->totalPages = ceil($this->totalRows / $limit);

        return $query;
    }

    public function proccesStore($data)
    {
        // Inserts data and returns inserted row's primary key
        // $this->insert($data);
        // Inserts data and returns true on success and false on failure
        return $this->insert($data, false);
    }


}
