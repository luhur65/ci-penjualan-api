<?php

namespace App\Models;

use App\Models\CustomModel;

class Role extends CustomModel
{
    protected $table            = 'roles';
    protected $primaryKey       = 'id';
    protected $sortDirection    = 'ASC';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'rolename',
        'modified_by',
    ];

    protected $fieldMap = [
        'rolename' => 'roles.rolename',
        'modifiedby' => 'roles.modified_by',
        'updated_at' => 'roles.updated_at',
        'created_at' => 'roles.created_at'
    ];

    protected $searchableFields = [
        'rolename',
        'modifiedby',
        'updated_at',
        'created_at'
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
    protected $validationRules      = [];
    protected $validationMessages   = [];
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


    public function getALl()
    {
        // Base query
        $query = $this->builder();
        $query->select('id, rolename, modified_by as modifiedby, updated_at, created_at');

        return $this->datatable($query);
    }

    public function findOne($id = null)
    {
        $role = $this->builder()
            ->select('id, rolename, updated_at, created_at')
            ->where('id', $id)
            ->get()
            ->getRow();

        $acos = $this->db->table('acl')
            ->select('aco_id')
            ->where('role_id', $id)
            ->get()
            ->getResultArray();

        return [
            'data' => $role,
            'acos' => $acos
        ];
    }

    // public function sort($query) 
    // {
    //     $query->orderBy($this->params['sidx'], $this->params['sord']);
    // }

    // public function pagination($query)
    // {
    //     $query->limit($this->params['limit'], $this->params['offset']);
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

    //     $this->totalRows = $query->countAllResults(false);
    //     $limit = $this->params['limit'] ?? 10;
    //     $this->totalPages = ceil($this->totalRows / $limit);

    //     return $query;
    // }

}
