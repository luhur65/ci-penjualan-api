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
        $db = \Config\Database::connect();
        $filtersRaw = $this->params['filters'] ?? '';

        if (is_string($filtersRaw)) {
            $filters = json_decode($filtersRaw, true);
        } else {
            $filters = is_array($filtersRaw) ? $filtersRaw : [];
        }

        if (empty($filters['rules'])) {
            return $query;
        }

        $validRules = [];
        foreach ($filters['rules'] as $rule) {
            $field = $rule['field'];
            $value = $rule['data'];

            if ($value === '' || $value === '0') continue;
            if (!in_array($field, $this->searchableFields)) continue;

            $validRules[] = $rule;
        }

        if (empty($validRules)) {
            return $query;
        }

        $rawSqlConditions = [];
        foreach ($validRules as $rule) {
            $field = $rule['field'];

            $value = $db->escapeLikeString($rule['data']);

            $isDate = in_array($field, $this->dateFields);
            $dbField = $this->mapField($field);

            if ($isDate) {
                $rawSqlConditions[] = "DATE_FORMAT({$dbField}, '%d-%m-%Y %H:%i:%s') LIKE '%{$value}%' ESCAPE '!'";
            } else {
                $rawSqlConditions[] = "{$dbField} LIKE '%{$value}%' ESCAPE '!'";
            }
        }

        // Gabungkan semua kondisi dengan kata " OR "
        // Hasilnya: "roles.rolename LIKE '%U%' ESCAPE '!' OR userroles.modified_by LIKE '%U%' ESCAPE '!'"
        $combinedSql = implode(' OR ', $rawSqlConditions);

        // Bungkus string gabungan tadi dengan tanda kurung () secara manual dan lempar ke where()
        // Parameter 'false' di akhir sangat penting agar CI4 tidak merusak/mengubah string kita
        $query->where("($combinedSql)", null, false);

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
    // public function getPosition(int $id, array $params = [], bool $isDeleting = false)
    // {
    //     $this->params = $params;

    //     // 1. Ambil parameter pengurutan dari JQGrid    
    //     $page = (int) ($this->params['page'] ?? 1);
    //     $limit = (int) ($this->params['limit'] ?? 10);
    //     $sidx = $this->params['sortIndex'] ?? $this->primaryKey;
    //     $sord = strtoupper($this->params['sortOrder'] ?? $this->sortDirection);

    //     if (!in_array($sidx, $this->searchableFields)) {
    //         $sidx = $this->primaryKey;
    //     }

    //     if (!in_array($sord, ['ASC', 'DESC'])) {
    //         $sord = $this->sortDirection;
    //     }

    //     $builder = $this->builder();

    //     // 2. KUNCI PERFORMA: Gunakan fungsi bawaan ROW_NUMBER() OVER()
    //     $builder->select("{$this->table}.{$this->primaryKey}, ROW_NUMBER() OVER (ORDER BY {$sidx} {$sord}, {$this->table}.{$this->primaryKey} ASC) AS position", false);

    //     // 3. Terapkan filter pencarian aktif (jika ada)
    //     $this->filter($builder);

    //     // 4. Jika mode hapus → hitung posisi tanpa query ke DB
    //     // if ($isDeleting) {

    //     //     $indexRow = (int) ($this->params['indexRow'] ?? 1);

    //     //     $sqlBase = $builder->getCompiledSelect();

    //     //     // 🔥 ambil row terdekat dari posisi sekarang
    //     //     $sql = "
    //     //         SELECT {$this->primaryKey}, position 
    //     //         FROM ({$sqlBase}) AS ordered_query
    //     //         WHERE position = ?
    //     //         ORDER BY position ASC
    //     //         LIMIT 1
    //     //     ";

    //     //     $query = $this->db->query($sql, [$indexRow]);

    //     //     if ($query === false) {
    //     //         $error = $this->db->error();
    //     //         throw new \Exception("DB Error: " . $error['message'] . " | SQL: " . $sql);
    //     //     }

    //     //     $row = $query->getRow();

    //     //     // 🔥 kalau tidak ada (misalnya delete last row)
    //     //     if (!$row) {
    //     //         $sqlFallback = "
    //     //             SELECT {$this->primaryKey}, position 
    //     //             FROM ({$sqlBase}) AS ordered_query
    //     //             ORDER BY position DESC
    //     //             LIMIT 1
    //     //         ";

    //     //         $queryFallback = $this->db->query($sqlFallback);

    //     //         if ($queryFallback === false) {
    //     //             $error = $this->db->error();
    //     //             throw new \Exception("DB Error: " . $error['message'] . " | SQL: " . $sqlFallback);
    //     //         }

    //     //         $row = $queryFallback->getRow();
    //     //     }

    //     //     return [
    //     //         'id' => $row->id ?? null,
    //     //         'position' => $row->position ?? 0,
    //     //         'page' => $row->position ? ceil($row->position / $limit) : 1
    //     //     ];
    //     // }

    //     // 4. Jika mode hapus → hitung posisi tanpa query ke DB ( IndexRow nya local)
    //     if ($isDeleting) {

    //         // [KUNCI PERBAIKAN]: Tangkap indeks lokal (0-9) dari Frontend
    //         $localIndex = (int) ($this->params['indexRow'] ?? 0);

    //         // Hitung posisi GLOBAL menggunakan rumus matematika Halaman x Limit
    //         $globalPosition = (($page - 1) * $limit) + $localIndex + 1;

    //         $sqlBase = $builder->getCompiledSelect();

    //         // 🔥 ambil row terdekat berdasarkan posisi GLOBAL
    //         $sql = "
    //             SELECT {$this->primaryKey}, position 
    //             FROM ({$sqlBase}) AS ordered_query
    //             WHERE position = ?
    //             ORDER BY position ASC
    //             LIMIT 1
    //         ";

    //         // Masukkan variabel $globalPosition, bukan lagi $indexRow mentah
    //         $query = $this->db->query($sql, [$globalPosition]);

    //         if ($query === false) {
    //             $error = $this->db->error();
    //             throw new \Exception("DB Error: " . $error['message'] . " | SQL: " . $sql);
    //         }

    //         $row = $query->getRow();

    //         // 🔥 kalau tidak ada (misalnya delete last row di halaman terakhir)
    //         if (!$row) {
    //             $sqlFallback = "
    //                 SELECT {$this->primaryKey}, position 
    //                 FROM ({$sqlBase}) AS ordered_query
    //                 ORDER BY position DESC
    //                 LIMIT 1
    //             ";

    //             $queryFallback = $this->db->query($sqlFallback);

    //             if ($queryFallback === false) {
    //                 $error = $this->db->error();
    //                 throw new \Exception("DB Error: " . $error['message'] . " | SQL: " . $sqlFallback);
    //             }

    //             $row = $queryFallback->getRow();
    //         }

    //         return [
    //             'id' => $row->id ?? null,
    //             'position' => $row->position ?? 0,
    //             'page' => $row->position ? ceil($row->position / $limit) : 1
    //         ];
    //     }

    //     // 5. Ekstrak kueri builder menjadi string SQL mentah (Hanya jika bukan mode delete)
    //     $sqlBase = $builder->getCompiledSelect();

    //     // 6. [KUNCI PERBAIKAN]: Gunakan alias 'ordered_query' pada klausa WHERE
    //     $sql = "SELECT position FROM ({$sqlBase}) AS ordered_query WHERE ordered_query.{$this->primaryKey} = ?";

    //     \dd($sql);

    //     // 7. Eksekusi kueri akhir secara langsung
    //     $query = $this->db->query($sql, [$id]);

    //     // \dd($query->getResult());

    //     if ($query === false) {
    //         $error = $this->db->error();
    //         throw new \Exception("Database Error: " . $error['message'] . " | SQL: " . $sql);
    //     }

    //     $row = $query->getRow();
    //     // return $row ? (int) $row->position : 0;
    //     return [
    //         'id' => $id,
    //         'position' => $row ? (int) $row->position : -1,
    //         'page' => $row->position ? ceil($row->position / $limit) : 1
    //     ];
    // }


    /**
     * Mencari posisi baris dan halaman untuk JQGrid.
     * Menggunakan array_search (Ringan) untuk Create/Update
     * Menggunakan SQL Offset (Akurat) untuk posisi Delete
     */
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

        if (!in_array($sord, ['ASC', 'DESC'])) {
            $sord = $this->sortDirection;
        }

        $builder = $this->builder();
        $this->filter($builder); // Terapkan filter aktif JQGrid

        // =========================================================
        // SKENARIO 1: MODE HAPUS (DELETE)
        // Kita tidak bisa mencari ID yang sudah terhapus.
        // Kita harus mencari posisi matematika (Misal: Baris ke-25).
        // =========================================================
        if ($isDeleting) {
            // Ambil SEMUA ID terurut SEBELUM delete
            $allBuilder = $this->builder();
            $this->filter($allBuilder);
            $allBuilder->select("{$this->table}.{$this->primaryKey}")
                ->orderBy($sidx, $sord)
                ->orderBy($this->table . '.' . $this->primaryKey, 'ASC');

            $records = $allBuilder->get()->getResultArray();
            $ids = array_column($records, $this->primaryKey);

            // Cari posisi data yang akan dihapus
            $currentIndex = array_search((string)$id, array_map('strval', $ids));

            if ($currentIndex === false) {
                return ['id' => null, 'position' => 1, 'page' => 1, 'offset' => 0];
            }

            // Ambil tetangga: utamakan baris BERIKUTNYA, fallback ke SEBELUMNYA
            $totalRows = count($ids);

            if ($currentIndex < $totalRows - 1) {
                // Ada baris di bawahnya → ambil itu
                $neighborIndex = $currentIndex; // setelah delete, index ini jadi baris berikutnya
                $neighborId    = $ids[$currentIndex + 1];
            } elseif ($currentIndex > 0) {
                // Baris terakhir → ambil yang di atasnya
                $neighborIndex = $currentIndex - 1;
                $neighborId    = $ids[$currentIndex - 1];
            } else {
                // Satu-satunya data
                return ['id' => null, 'position' => 1, 'page' => 1, 'offset' => 0];
            }

            $finalPos = $neighborIndex + 1; // 1-based

            return [
                'id'       => $neighborId,
                'position' => $finalPos,
                'page'     => ceil($finalPos / $limit),
                'offset'   => max(0, $finalPos - 1)
            ];
        }

        // =========================================================
        // SKENARIO 2: MODE UBAH/TAMBAH (UPDATE/CREATE)
        // Menggunakan array_search seperti gaya Laravel
        // =========================================================

        // Ambil SEMUA ID yang lolos filter 
        $builder->select("{$this->table}.{$this->primaryKey}");
        // Wajib diurutkan agar posisi array sesuai dengan posisi grid!
        $builder->orderBy($sidx, $sord);
        $builder->orderBy($this->table . '.' . $this->primaryKey, 'ASC');

        $records = $builder->get()->getResultArray();
        $ids = array_column($records, $this->primaryKey);

        $rowIndex = array_search($id, $ids);

        if ($rowIndex === false) {

            // Cukup kembalikan ke halaman JQGrid sebelum diedit
            $currentPage = (int) ($this->params['page'] ?? 1);

            return [
                "id"       => $id,
                "position" => 0,
                "page"     => $currentPage,
                "offset"   => max(0, $currentPage - 1)
            ];
        }

        // SABUK PENGAMAN KACAMATA KUDA (Pencarian Dua Tahap)
        // if ($rowIndex === false) {

        //     // Jika pencarian sudah tanpa filter, berarti data terhapus, aman set ke 1
        //     if (empty($params['filters']) && empty($params['_search'])) {
        //         return ["id" => $id, "page" => 1, "position" => 1];
        //     }

        //     // Lepaskan filter JQGrid
        //     unset($params['filters']);
        //     unset($params['_search']);
        //     unset($params['searchField']);
        //     unset($params['searchString']);

        //     // Cari ulang posisinya tanpa filter
        //     return $this->getPosition($id, $params, false);
        // }

        $rowNumber = $rowIndex + 1;

        return [
            'id'       => $id,
            'position' => $rowNumber,
            'page'     => ceil($rowNumber / $limit),
            'offset'   => max(0, $rowNumber - 1)
        ];
    }
    
}
