<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Database;

class CustomModel extends Model
{
    protected $params = [];
    protected $totalPages = 0;
    protected $totalRows = 0;
    protected $lengthCache = [];

    protected $fieldMap = [];
    protected $searchableFields = [];
    protected $dateFields = [
        'created_at',
        'updated_at'
    ];

    /**
     * Request Parameters untuk JQGrid
     */
    public function setRequestParameters(array $requestData = [])
    {
        $page  = $requestData['page'] ?? 1;
        $limit = $requestData['rows'] ?? 10;

        $filters = '[]';

        if (isset($requestData['filters'])) {
            $filters = is_string($requestData['filters'])
                ? $requestData['filters']
                : json_encode($requestData['filters']);
        }

        $this->params = [
            'offset'  => ($page - 1) * $limit,
            'limit'   => $limit,
            'filters' => json_decode($filters, true),
            'sidx'    => $requestData['sidx'] ?? $this->primaryKey,
            'sord'    => $requestData['sord'] ?? $this->sortDirection,
            'search'  => $requestData['search'] ?? null
        ];

        return $this;
    }

    protected function mapField($field)
    {
        return $this->fieldMap[$field] ?? "{$this->table}.{$field}";
    }

    public function filter(&$query)
    {
        $filtersRaw = $this->params['filters'] ?? '';

        // 3. Konversi String JSON menjadi Array PHP (Jika wujudnya masih string)
        if (is_string($filtersRaw)) {
            $filters = json_decode($filtersRaw, true);
        } else {
            $filters = is_array($filtersRaw) ? $filtersRaw : [];
        }

        if (empty($filters['rules'])) {
            return $query;
        }

        $groupOp = strtoupper($filters['groupOp'] ?? 'AND');

        foreach ($filters['rules'] as $rule) {

            $field = $rule['field'];
            $value = trim($rule['data']);
            $isDate = in_array($field, $this->dateFields);

            if ($value === '' || $value === '0') { // all data 
                continue;
            }

            // Hanya filter field yang diperbolehkan
            if (!in_array($field, $this->searchableFields)) continue;

            $dbField = $this->mapField($field);

            if ($groupOp === 'AND') {
                if ($isDate) {
                    $query->where("DATE_FORMAT({$dbField}, '%d-%m-%Y %H:%i:%s') LIKE '%{$value}%'", null, false);
                } else {
                    $query->like($dbField, $value);
                }
            } else { // OR
                if ($isDate) {
                    $query->orWhere("DATE_FORMAT({$dbField}, '%d-%m-%Y %H:%i:%s') LIKE '%{$value}%'", null, false);
                } else {
                    $query->orLike($dbField, $value);
                }
            }
        }

        // dd($query->getCompiledSelect());

        return $query;
    }

    public function sort(&$query)
    {
        $field = $this->params['sidx'];
        $dir   = $this->params['sord'];

        // Hanya sort field yang diperbolehkan
        if (in_array($field, $this->searchableFields)) {
            $query->orderBy($this->mapField($field), $dir);
        }

        return $query;
    }

    public function pagination(&$query)
    {
        $query->limit($this->params['limit'], $this->params['offset']);
        return $query;
    }

    public function datatable(&$query)
    {
        $this->filter($query);

        $countBuilder = clone $query;
        $this->totalRows = $countBuilder->countAllResults();

        $this->sort($query);
        $this->pagination($query);

        $limit = $this->params['limit'];

        $this->totalPages = $limit ? ceil($this->totalRows / $limit) : 0;

        return [
            'data' => $query->get()->getResult(),
            'totalRows' => $this->totalRows,
            'totalPages' => $this->totalPages
        ];
    }

    /**
     * Get field lengths based on table model
     */
    public function getFieldLengths(): array
    {
        if (isset($this->lengthCache[$this->table])) {
            return $this->lengthCache[$this->table];
        }

        $db      = Database::connect();
        $table   = $this->table;
        $query   = $db->query("SHOW COLUMNS FROM `{$table}`");
        $columns = $query->getResult();

        $lengths = [];

        foreach ($columns as $col) {
            preg_match('/\((.*?)\)/', $col->Type, $matches);
            $lengths[$col->Field] = isset($matches[1]) ? (int)$matches[1] : null;
        }

        return $this->lengthCache[$this->table] = $lengths;
    }


    /**
     * Mencari posisi baris dan halaman untuk JQGrid tanpa Temporary Table
     */
    public function getPosition(int $id, array $params = [], bool $isDeleting = false)
    {
        $this->params = $params;

        // 1. Ambil parameter pengurutan dari JQGrid    
        $page = (int) ($this->params['page'] ?? 1);
        $limit = (int) ($this->params['limit'] ?? 10);
        $sidx = $this->params['sortIndex'] ?? $this->primaryKey;
        $sord = strtoupper($this->params['sortOrder'] ?? $this->sortDirection);

        if (!in_array($sidx, $this->searchableFields)) {
            $sidx = $this->primaryKey;
        }

        if (!in_array($sord, ['ASC', 'DESC'])) {
            $sord = $this->sortDirection;
        }

        $builder = $this->builder();

        // 2. KUNCI PERFORMA: Gunakan fungsi bawaan ROW_NUMBER() OVER()
        $builder->select("{$this->table}.{$this->primaryKey}, ROW_NUMBER() OVER (ORDER BY {$sidx} {$sord}, {$this->table}.{$this->primaryKey} ASC) AS position", false);

        // 3. Terapkan filter pencarian aktif (jika ada)
        $this->filter($builder);

        // 4. Jika mode hapus → hitung posisi tanpa query ke DB
        // if ($isDeleting) {

        //     $indexRow = (int) ($this->params['indexRow'] ?? 1);

        //     $sqlBase = $builder->getCompiledSelect();

        //     // 🔥 ambil row terdekat dari posisi sekarang
        //     $sql = "
        //         SELECT {$this->primaryKey}, position 
        //         FROM ({$sqlBase}) AS ordered_query
        //         WHERE position = ?
        //         ORDER BY position ASC
        //         LIMIT 1
        //     ";

        //     $query = $this->db->query($sql, [$indexRow]);

        //     if ($query === false) {
        //         $error = $this->db->error();
        //         throw new \Exception("DB Error: " . $error['message'] . " | SQL: " . $sql);
        //     }

        //     $row = $query->getRow();

        //     // 🔥 kalau tidak ada (misalnya delete last row)
        //     if (!$row) {
        //         $sqlFallback = "
        //             SELECT {$this->primaryKey}, position 
        //             FROM ({$sqlBase}) AS ordered_query
        //             ORDER BY position DESC
        //             LIMIT 1
        //         ";

        //         $queryFallback = $this->db->query($sqlFallback);

        //         if ($queryFallback === false) {
        //             $error = $this->db->error();
        //             throw new \Exception("DB Error: " . $error['message'] . " | SQL: " . $sqlFallback);
        //         }

        //         $row = $queryFallback->getRow();
        //     }

        //     return [
        //         'id' => $row->id ?? null,
        //         'position' => $row->position ?? 0,
        //         'page' => $row->position ? ceil($row->position / $limit) : 1
        //     ];
        // }

        // 4. Jika mode hapus → hitung posisi tanpa query ke DB ( IndexRow nya local)
        if ($isDeleting) {

            // [KUNCI PERBAIKAN]: Tangkap indeks lokal (0-9) dari Frontend
            $localIndex = (int) ($this->params['indexRow'] ?? 0);

            // Hitung posisi GLOBAL menggunakan rumus matematika Halaman x Limit
            $globalPosition = (($page - 1) * $limit) + $localIndex + 1;

            $sqlBase = $builder->getCompiledSelect();

            // 🔥 ambil row terdekat berdasarkan posisi GLOBAL
            $sql = "
                SELECT {$this->primaryKey}, position 
                FROM ({$sqlBase}) AS ordered_query
                WHERE position = ?
                ORDER BY position ASC
                LIMIT 1
            ";

            // Masukkan variabel $globalPosition, bukan lagi $indexRow mentah
            $query = $this->db->query($sql, [$globalPosition]);

            if ($query === false) {
                $error = $this->db->error();
                throw new \Exception("DB Error: " . $error['message'] . " | SQL: " . $sql);
            }

            $row = $query->getRow();

            // 🔥 kalau tidak ada (misalnya delete last row di halaman terakhir)
            if (!$row) {
                $sqlFallback = "
                    SELECT {$this->primaryKey}, position 
                    FROM ({$sqlBase}) AS ordered_query
                    ORDER BY position DESC
                    LIMIT 1
                ";

                $queryFallback = $this->db->query($sqlFallback);

                if ($queryFallback === false) {
                    $error = $this->db->error();
                    throw new \Exception("DB Error: " . $error['message'] . " | SQL: " . $sqlFallback);
                }

                $row = $queryFallback->getRow();
            }

            return [
                'id' => $row->id ?? null,
                'position' => $row->position ?? 0,
                'page' => $row->position ? ceil($row->position / $limit) : 1
            ];
        }

        // 5. Ekstrak kueri builder menjadi string SQL mentah (Hanya jika bukan mode delete)
        $sqlBase = $builder->getCompiledSelect();

        // 6. [KUNCI PERBAIKAN]: Gunakan alias 'ordered_query' pada klausa WHERE
        $sql = "SELECT position FROM ({$sqlBase}) AS ordered_query WHERE ordered_query.{$this->primaryKey} = ?";

        // 7. Eksekusi kueri akhir secara langsung
        $query = $this->db->query($sql, [$id]);

        if ($query === false) {
            $error = $this->db->error();
            throw new \Exception("Database Error: " . $error['message'] . " | SQL: " . $sql);
        }

        $row = $query->getRow();
        // return $row ? (int) $row->position : 0;
        return [
            'id' => $id,
            'position' => $row ? (int) $row->position : 0,
            'page' => $row->position ? ceil($row->position / $limit) : 1
        ];
    }
    
}
