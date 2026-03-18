<?php

namespace App\Models;

use App\Models\CustomModel;

class Acos extends CustomModel
{
    protected $table            = 'acos';
    protected $primaryKey       = 'id';
    protected $sortDirection    = 'ASC';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'class',
        'method',
        'nama',
        'idheader',
        'keterangan',
        'modified_by'
    ];

    protected $fieldMap = [
        'class' => 'acos.class',
        'method' => 'acos.method',
        'nama' => 'acos.nama',
        'idheader' => 'acos.idheader',
        'keterangan' => 'acos.keterangan',
        'modified_by' => 'acos.modified_by'
    ];

    protected $searchableFields = [
        'class',
        'method',
        'nama',
        'idheader',
        'keterangan',
        'modified_by'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = false;
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

    public function get()
    {
        // Base query
        $query = $this->builder();
        $query->select([
            'id',
            'class',
            'method',
            'nama',
            'idheader',
            'keterangan'
        ]);

        return $this->datatable($query);
    }

    public function findOne($id = null)
    {
        $acos = $this->builder()
            ->select('id, class, method, nama, idheader, keterangan')
            ->where('id', $id)
            ->get()
            ->getRow();

        return [
            'data' => $acos
        ];
    }

    public function getAcosByClass($class)
    {
        return $this->where('class', $class)->first();
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
