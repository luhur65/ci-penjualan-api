<?php

namespace App\Models;

class UserRole extends CustomModel
{
    protected $table            = 'userroles';
    protected $primaryKey       = 'id';
    protected $sortDirection    = 'ASC';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'role_id',
        'modified_by'
    ];

    protected $fieldMap = [
        'rolename' => 'roles.rolename',
        'user' => 'users.fullname',
        'modifiedby' => 'userroles.modified_by',
        'created_at' => 'userroles.created_at',
        'updated_at' => 'userroles.updated_at'
    ];

    protected $searchableFields = [
        'rolename',
        'user',
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
        'user_id' => 'required',
        'role_id' => 'required',
    ];
    protected $validationMessages   = [
        'user_id' => [
            'required' => 'User ID is required',
        ],
        'role_id' => [
            'required' => 'Role ID is required',
        ],
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

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

    public function getRoleByUserId($id)
    {
        $query = $this->builder()
            ->select([
                "$this->table.id",
                "$this->table.user_id",
                "$this->table.role_id",
                "roles.rolename",
                "users.fullname as user",
                "$this->table.modified_by as modifiedby",
                "$this->table.created_at",
                "$this->table.updated_at"
            ])
            ->join("roles", "roles.id = $this->table.role_id", "left")
            ->join("users", "users.id = $this->table.user_id", "left")
            ->where("$this->table.user_id", $id);
        // ->get()
        // ->getResultArray();

        return $this->datatable($query);
    }
}
