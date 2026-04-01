<?php

namespace App\Models;

class Penjualan extends CustomModel
{
    protected $table            = 'penjualans';
    protected $primaryKey       = 'id';
    protected $sortDirection    = 'ASC';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        // TODO: mapping data
    ];

    protected $fieldMap = [
        // TODO: mapping data
    ];

    protected $searchableFields = [
        // TODO: mapping data
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
        // TODO: mapping data
    ];
    protected $validationMessages   = [
        // TODO: mapping data
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

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll()
    {
        $query = $this->builder();
        $query->select([
            // TODO: mapping data
        ]);

        return $this->datatable($query);
    }

    public function findOne($id = null)
    {
        // TODO: mapping data
        return $this->where('id', $id)->first();
    }

}

