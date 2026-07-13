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
        'no_bukti'       => 'no_bukti',
        'tgl_bukti'      => 'tgl_bukti',
        'pelanggan_id'   => 'pelanggan_id',
        'nama_pelanggan' => 'nama_pelanggan',
        'modifiedby'     => 'modifiedby',
        'created_at'     => 'created_at',
        'updated_at'     => 'updated_at',
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
        $query = $this->db->table('v_penjualan');

        return $this->datatable($query);
    }

    public function findOne($id = null)
    {
        $query = $this->db->table('v_penjualan')
            ->where('id', $id)
            ->get();
            
        return $query ? $query->getRowObject() : null;
    }

    /**
     * Override sort untuk CustomModel karena memakai View
     */
    public function sort(&$query)
    {
        $field = $this->params['sidx'] ?? 'id';
        $dir   = $this->params['sord'] ?? $this->sortDirection;

        if (in_array($field, $this->searchableFields) || $field === 'id' || $field === 'uuid') {
            $dbField = $this->mapField($field);
            if ($dbField === 'uuid') $dbField = 'id';
            $query->orderBy($dbField, $dir);
        } else {
            $query->orderBy('id', $this->sortDirection);
        }

        if ($field !== 'id' && $field !== 'uuid') {
            $query->orderBy('id', $this->sortDirection);
        }

        return $query;
    }

    /**
     * Override getPosition untuk UUID primary key
     */
    public function getPosition($id, array $params = [], bool $isDeleting = false)
    {
        $this->params = $params;

        $page  = (int) ($this->params['page']      ?? 1);
        $limit = (int) ($this->params['limit']     ?? 10);
        $sidx  = $this->params['sortIndex'] ?? 'id';
        $sord  = strtoupper($this->params['sortOrder'] ?? $this->sortDirection);

        if (!in_array($sidx, $this->searchableFields)) {
            $sidx = 'id';
        } else {
            $sidx = $this->mapField($sidx);
        }

        if (!in_array($sord, ['ASC', 'DESC'])) {
            $sord = $this->sortDirection;
        }

        // 1. Ambil nilai sidx dari record yang dituju
        $targetBuilder = $this->db->table('v_penjualan');
        $targetBuilder->select("id, {$sidx} as sort_val");
        $targetBuilder->where('id', $id);
        $targetRow = $targetBuilder->get()->getRow();

        if (!$targetRow) {
            return ['id' => $id, 'position' => 0, 'page' => $page, 'offset' => max(0, $page - 1)];
        }

        $targetVal = $targetRow->sort_val;

        // 2. Hitung posisi dengan Mathematical Count (Kecepatan O(1) s/d O(N) tanpa Filesort)
        $countBuilder = $this->db->table('v_penjualan');
        $this->applySearchFilter($countBuilder);

        $countBuilder->groupStart();
        if ($sord === 'ASC') {
            if ($targetVal === null) {
                $countBuilder->where("{$sidx} IS NULL");
                $countBuilder->where("id <=", $id);
            } else {
                $countBuilder->groupStart();
                    $countBuilder->where("{$sidx} <", $targetVal);
                    $countBuilder->orWhere("{$sidx} IS NULL");
                $countBuilder->groupEnd();
                $countBuilder->orGroupStart();
                    $countBuilder->where("{$sidx}", $targetVal);
                    $countBuilder->where("id <=", $id);
                $countBuilder->groupEnd();
            }
        } else {
            // DESC (NULLs ada di paling bawah di MariaDB)
            if ($targetVal === null) {
                $countBuilder->where("{$sidx} IS NOT NULL");
                $countBuilder->orGroupStart();
                    $countBuilder->where("{$sidx} IS NULL");
                    $countBuilder->where("id <=", $id);
                $countBuilder->groupEnd();
            } else {
                $countBuilder->where("{$sidx} >", $targetVal);
                $countBuilder->orGroupStart();
                    $countBuilder->where("{$sidx}", $targetVal);
                    $countBuilder->where("id <=", $id);
                $countBuilder->groupEnd();
            }
        }
        $countBuilder->groupEnd();

        $rowNumber = (int) $countBuilder->countAllResults();
        if ($rowNumber === 0) {
            $rowNumber = 1;
        }

        if ($isDeleting) {
            // 3. Cari Neighbor ID menggunakan metode limit 1
            $nextId = $this->getNeighborId($targetVal, $id, $sidx, $sord, 'next');
            
            if ($nextId) {
                $neighborId = $nextId;
                $finalPos = $rowNumber;
            } else {
                $prevId = $this->getNeighborId($targetVal, $id, $sidx, $sord, 'prev');
                if ($prevId) {
                    $neighborId = $prevId;
                    $finalPos = max(1, $rowNumber - 1);
                } else {
                    return ['id' => null, 'position' => 1, 'page' => 1, 'offset' => 0];
                }
            }

            return [
                'id'       => $neighborId,
                'position' => $finalPos,
                'page'     => ceil($finalPos / $limit),
                'offset'   => max(0, $finalPos - 1)
            ];
        }

        return [
            'id'       => $id,
            'position' => $rowNumber,
            'page'     => ceil($rowNumber / $limit),
            'offset'   => max(0, $rowNumber - 1)
        ];
    }

    /**
     * Helper khusus mencari neighbor ID dengan efisien.
     */
    protected function getNeighborId($targetVal, $targetId, $sidx, $sord, $direction = 'next')
    {
        $builder = $this->db->table('v_penjualan');
        $this->applySearchFilter($builder);
        $builder->select('id');

        $builder->groupStart();
        if ($sord === 'ASC') {
            if ($direction === 'next') {
                if ($targetVal === null) {
                    $builder->groupStart();
                        $builder->where("{$sidx} IS NULL");
                        $builder->where("id >", $targetId);
                    $builder->groupEnd();
                    $builder->orWhere("{$sidx} IS NOT NULL");
                } else {
                    $builder->where("{$sidx} >", $targetVal);
                    $builder->orGroupStart();
                        $builder->where("{$sidx}", $targetVal);
                        $builder->where("id >", $targetId);
                    $builder->groupEnd();
                }
                $builder->orderBy($sidx, 'ASC');
                $builder->orderBy('id', 'ASC');
            } else { // prev
                if ($targetVal === null) {
                    $builder->where("{$sidx} IS NULL");
                    $builder->where("id <", $targetId);
                } else {
                    $builder->groupStart();
                        $builder->where("{$sidx} <", $targetVal);
                        $builder->orWhere("{$sidx} IS NULL");
                    $builder->groupEnd();
                    $builder->orGroupStart();
                        $builder->where("{$sidx}", $targetVal);
                        $builder->where("id <", $targetId);
                    $builder->groupEnd();
                }
                $builder->orderBy($sidx, 'DESC');
                $builder->orderBy('id', 'DESC');
            }
        } else { // DESC
            if ($direction === 'next') {
                if ($targetVal === null) {
                    $builder->where("{$sidx} IS NULL");
                    $builder->where("id >", $targetId);
                } else {
                    $builder->where("{$sidx} <", $targetVal);
                    $builder->orWhere("{$sidx} IS NULL");
                    $builder->orGroupStart();
                        $builder->where("{$sidx}", $targetVal);
                        $builder->where("id >", $targetId);
                    $builder->groupEnd();
                }
                $builder->orderBy($sidx, 'DESC');
                $builder->orderBy('id', 'ASC');
            } else { // prev
                if ($targetVal === null) {
                    $builder->where("{$sidx} IS NOT NULL");
                    $builder->orGroupStart();
                        $builder->where("{$sidx} IS NULL");
                        $builder->where("id <", $targetId);
                    $builder->groupEnd();
                } else {
                    $builder->where("{$sidx} >", $targetVal);
                    $builder->orGroupStart();
                        $builder->where("{$sidx}", $targetVal);
                        $builder->where("id <", $targetId);
                    $builder->groupEnd();
                }
                $builder->orderBy($sidx, 'ASC');
                $builder->orderBy('id', 'DESC');
            }
        }
        $builder->groupEnd();
        
        $builder->limit(1);
        $row = $builder->get()->getRow();
        return $row ? $row->id : null;
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
