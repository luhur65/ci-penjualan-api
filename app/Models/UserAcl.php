<?php

namespace App\Models;

class UserAcl extends CustomModel
{
    protected $table            = 'useracl';
    protected $primaryKey       = 'id';
    protected $sortDirection    = 'ASC';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'acl_id',
        'modified_by',
    ];

    protected $fieldMap = [
        'class' => 'acos.class',
        'method' => 'acos.method',
        'nama' => 'acos.nama',
        'keterangan' => 'acos.keterangan',
        'modifiedby' => 'useracls.modified_by',
        'created_at' => 'useracls.created_at',
        'updated_at' => 'useracls.updated_at'
    ];

    protected $searchableFields = [
        'class',
        'method',
        'nama',
        'keterangan',
        'modifiedby',
        'created_at',
        'updated_at'
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

    public function getAclByUserId($id)
    {
        $query = $this->builder()
            ->select([
                "$this->table.id",
                "$this->table.user_id",
                "$this->table.aco_id",
                "acos.class",
                "acos.method",
                "acos.nama",
                "acos.keterangan",
                "$this->table.modified_by as modifiedby",
                "$this->table.created_at",
                "$this->table.updated_at"
            ])
            ->join("acos", "acos.id = $this->table.aco_id", "left")
            ->where("$this->table.user_id", $id);
            
        return $this->datatable($query);
    }
}
