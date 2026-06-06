<?php

namespace App\Models;

class TestingMasterDetail extends CustomModel
{
    protected $table            = 'tbl_penjualan';
    protected $primaryKey       = 'uuid';
    protected $sortDirection    = 'ASC';
    protected $useAutoIncrement = false; // UUID - tidak pakai auto increment
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'uuid',
        'no_bukti',
        'tgl_bukti',
        'pelanggan_id',
        'modifiedby',
    ];

    protected $fieldMap = [
        'no_bukti'       => 'tbl_penjualan.no_bukti',
        'tgl_bukti'      => 'tbl_penjualan.tgl_bukti',
        'pelanggan_id'   => 'tbl_penjualan.pelanggan_id',
        'nama_pelanggan' => 'tbl_pelanggan.nama_pelanggan',
        'modifiedby'     => 'tbl_penjualan.modifiedby',
        'created_at'     => 'tbl_penjualan.created_at',
        'updated_at'     => 'tbl_penjualan.updated_at',
    ];

    protected $searchableFields = [
        'no_bukti',
        'tgl_bukti',
        'nama_pelanggan',
        'modifiedby',
        'created_at',
        'updated_at',
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
            'tbl_penjualan.uuid as id',
            'tbl_penjualan.no_bukti',
            'tbl_penjualan.tgl_bukti',
            'tbl_penjualan.pelanggan_id',
            'tbl_pelanggan.nama_pelanggan',
            'tbl_penjualan.modifiedby',
            'tbl_penjualan.created_at',
            'tbl_penjualan.updated_at',
        ]);
        $query->join('tbl_pelanggan', 'tbl_pelanggan.id = tbl_penjualan.pelanggan_id', 'left');

        return $this->datatable($query);
    }

    public function findOne($id = null)
    {
        return $this->db->table('tbl_penjualan')
            ->select([
                'tbl_penjualan.uuid as id',
                'tbl_penjualan.no_bukti',
                'tbl_penjualan.tgl_bukti',
                'tbl_penjualan.pelanggan_id',
                'tbl_pelanggan.nama_pelanggan',
                'tbl_penjualan.modifiedby',
                'tbl_penjualan.created_at',
                'tbl_penjualan.updated_at',
            ])
            ->join('tbl_pelanggan', 'tbl_pelanggan.id = tbl_penjualan.pelanggan_id', 'left')
            ->where('tbl_penjualan.uuid', $id)
            ->get()->getRowObject();
    }

    /**
     * Override getPosition untuk UUID primary key
     */
    public function getPosition($id, array $params = [], bool $isDeleting = false)
    {
        $this->params = $params;

        $page  = (int) ($this->params['page']      ?? 1);
        $limit = (int) ($this->params['limit']     ?? 10);
        $sidx  = $this->params['sortIndex'] ?? $this->primaryKey;
        $sord  = strtoupper($this->params['sortOrder'] ?? $this->sortDirection);

        if (!in_array($sidx, $this->searchableFields)) {
            $sidx = 'tbl_penjualan.' . $this->primaryKey;
        } else {
            $sidx = $this->mapField($sidx);
        }

        if (!in_array($sord, ['ASC', 'DESC'])) {
            $sord = $this->sortDirection;
        }

        $builder = $this->db->table('tbl_penjualan');
        $builder->select('tbl_penjualan.uuid as id');
        $builder->join('tbl_pelanggan', 'tbl_pelanggan.id = tbl_penjualan.pelanggan_id', 'left');

        // Apply filters via raw search
        $this->applySearchFilter($builder);

        $builder->orderBy($sidx, $sord);
        $builder->orderBy('tbl_penjualan.uuid', 'ASC');

        $records = $builder->get()->getResultArray();
        $ids     = array_column($records, 'id');

        if ($isDeleting) {
            $currentIndex = array_search((string) $id, array_map('strval', $ids));
            if ($currentIndex === false) {
                return ['id' => null, 'position' => 1, 'page' => 1, 'offset' => 0];
            }

            $totalRows = count($ids);
            if ($currentIndex < $totalRows - 1) {
                $neighborIndex = $currentIndex;
                $neighborId    = $ids[$currentIndex + 1];
            } elseif ($currentIndex > 0) {
                $neighborIndex = $currentIndex - 1;
                $neighborId    = $ids[$currentIndex - 1];
            } else {
                return ['id' => null, 'position' => 1, 'page' => 1, 'offset' => 0];
            }

            $finalPos = $neighborIndex + 1;
            return [
                'id'       => $neighborId,
                'position' => $finalPos,
                'page'     => ceil($finalPos / $limit),
                'offset'   => max(0, $finalPos - 1),
            ];
        }

        $rowIndex = array_search((string) $id, array_map('strval', $ids));
        if ($rowIndex === false) {
            return ['id' => $id, 'position' => 0, 'page' => $page, 'offset' => max(0, $page - 1)];
        }

        $rowNumber = $rowIndex + 1;
        return [
            'id'       => $id,
            'position' => $rowNumber,
            'page'     => ceil($rowNumber / $limit),
            'offset'   => max(0, $rowNumber - 1),
        ];
    }

    /**
     * Apply search filter manually (karena memakai join khusus)
     */
    protected function applySearchFilter(&$builder)
    {
        $filtersRaw = $this->params['filters'] ?? [];

        if (is_string($filtersRaw)) {
            $filters = json_decode($filtersRaw, true);
        } else {
            $filters = is_array($filtersRaw) ? $filtersRaw : [];
        }

        if (empty($filters['rules'])) {
            return;
        }

        $db   = \Config\Database::connect();
        $conds = [];

        foreach ($filters['rules'] as $rule) {
            $field = $rule['field'];
            $value = $rule['data'];

            if ($value === '' || $value === '0') continue;
            if (!in_array($field, $this->searchableFields)) continue;

            $escaped  = $db->escapeLikeString($value);
            $dbField  = $this->mapField($field);
            $conds[]  = "{$dbField} LIKE '%{$escaped}%' ESCAPE '!'";
        }

        if (!empty($conds)) {
            $builder->where('(' . implode(' OR ', $conds) . ')', null, false);
        }
    }
}
