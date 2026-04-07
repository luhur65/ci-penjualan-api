<?php

namespace App\Models;

use App\Models\CustomModel;

class Error extends CustomModel
{
    protected $table            = 'errors';
    protected $primaryKey       = 'id';
    protected $sortDirection    = 'ASC';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'kodeerror',
        'keterangan',
        'modified_by',
        'created_at',
        'updated_at'
    ];

    protected $fieldMap = [
        'kodeerror' => 'errors.kodeerror',
        'keterangan' => 'errors.keterangan',
        'modifiedby' => 'errors.modified_by',
        'created_at' => 'errors.created_at',
        'updated_at' => 'errors.updated_at'
    ];

    protected $searchableFields = [
        'kodeerror',
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

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll()
    {
        $query = $this->builder();
        $query->select([
            'errors.id',
            'errors.kodeerror',
            'errors.keterangan',
            'errors.modified_by as modifiedby',
            'errors.created_at',
            'errors.updated_at'
        ]);

        return $this->datatable($query);
    }

    public function findOne($id = null)
    {
        return $this->where('id', $id)->first();
    }

    public function getPosition(int $id, array $params = [], bool $isDeleting = false)
    {
        $this->params = $params;

        $page = (int) ($this->params['page'] ?? 1);
        $limit = (int) ($this->params['limit'] ?? 10);
        $sidx = $this->params['sortIndex'] ?? $this->primaryKey;
        $sord = strtoupper($this->params['sortOrder'] ?? $this->sortDirection);

        if (!in_array($sidx, $this->searchableFields)) {
            $sidx = $this->primaryKey;
        }

        $builder = $this->builder();
        $builder->select($this->primaryKey);

        if (!empty($this->params['search'])) {
            $search = $this->params['search'];
            $builder->groupStart();
            foreach ($this->searchableFields as $field) {
                $builder->orLike($this->fieldMap[$field] ?? $field, $search);
            }
            $builder->groupEnd();
        }

        $builder->orderBy($sidx, $sord);
        $rows = $builder->get()->getResultArray();

        $position = array_search($id, array_column($rows, $this->primaryKey));

        if ($position === false) {
            return [
                'position' => 0,
                'page' => 1
            ];
        }

        $position++;
        if ($isDeleting) {
            if ($position > 1) {
                $position--;
            }
        }

        $page = ceil($position / $limit);

        return [
            'position' => $position,
            'page' => $page
        ];
    }
}
