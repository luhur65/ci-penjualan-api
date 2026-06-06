<?php

namespace App\Models;

class TestingMasterDetailItem extends CustomModel
{
    protected $table            = 'tbl_penjualan_detail';
    protected $primaryKey       = 'id';
    protected $sortDirection    = 'ASC';
    protected $useAutoIncrement = false; // UUID
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id',
        'penjualan_id',
        'nama_barang',
        'qty',
        'harga',
        'modifiedby',
    ];

    protected $fieldMap = [
        'id'           => 'tbl_penjualan_detail.id',
        'penjualan_id' => 'tbl_penjualan_detail.penjualan_id',
        'nama_barang'  => 'tbl_penjualan_detail.nama_barang',
        'qty'          => 'tbl_penjualan_detail.qty',
        'harga'        => 'tbl_penjualan_detail.harga',
        'modifiedby'   => 'tbl_penjualan_detail.modifiedby',
        'created_at'   => 'tbl_penjualan_detail.created_at',
        'updated_at'   => 'tbl_penjualan_detail.updated_at',
    ];

    protected $searchableFields = [
        'nama_barang',
        'qty',
        'harga',
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

    /**
     * Get all detail by penjualan_id (untuk lazy loading jqGrid)
     */
    public function getAllByPenjualan(string $penjualanId)
    {
        $query = $this->builder();
        $query->select([
            'tbl_penjualan_detail.id',
            'tbl_penjualan_detail.penjualan_id',
            'tbl_penjualan_detail.nama_barang',
            'tbl_penjualan_detail.qty',
            'tbl_penjualan_detail.harga',
            '(tbl_penjualan_detail.qty * tbl_penjualan_detail.harga) as subtotal',
            'tbl_penjualan_detail.modifiedby',
            'tbl_penjualan_detail.created_at',
            'tbl_penjualan_detail.updated_at',
        ]);
        $query->where('tbl_penjualan_detail.penjualan_id', $penjualanId);

        return $this->datatable($query);
    }

    public function findOne($id = null)
    {
        return $this->db->table('tbl_penjualan_detail')
            ->select([
                'id',
                'penjualan_id',
                'nama_barang',
                'qty',
                'harga',
                '(qty * harga) as subtotal',
                'modifiedby',
                'created_at',
                'updated_at',
            ])
            ->where('id', $id)
            ->get()->getRowObject();
    }

    /**
     * Hitung total penjualan (qty * harga) untuk satu penjualan
     */
    public function getTotalByPenjualan(string $penjualanId): float
    {
        $result = $this->db->table('tbl_penjualan_detail')
            ->select('SUM(qty * harga) as total')
            ->where('penjualan_id', $penjualanId)
            ->get()->getRowObject();

        return (float) ($result->total ?? 0);
    }

    /**
     * Override getPosition untuk UUID primary key
     */
    public function getPosition($id, array $params = [], bool $isDeleting = false)
    {
        $this->params = $params;

        $page       = (int) ($this->params['page']      ?? 1);
        $limit      = (int) ($this->params['limit']     ?? 10);
        $sidx       = $this->params['sortIndex'] ?? $this->primaryKey;
        $sord       = strtoupper($this->params['sortOrder'] ?? $this->sortDirection);
        $penjualanId = $this->params['penjualan_id'] ?? null;

        if (!in_array($sidx, $this->searchableFields)) {
            $sidx = 'tbl_penjualan_detail.id';
        } else {
            $sidx = $this->mapField($sidx);
        }

        if (!in_array($sord, ['ASC', 'DESC'])) {
            $sord = $this->sortDirection;
        }

        $builder = $this->db->table('tbl_penjualan_detail');
        $builder->select('id');
        if ($penjualanId) {
            $builder->where('penjualan_id', $penjualanId);
        }
        $builder->orderBy($sidx, $sord);
        $builder->orderBy('tbl_penjualan_detail.id', 'ASC');

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
}
