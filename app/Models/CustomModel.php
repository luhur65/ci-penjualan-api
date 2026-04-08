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
     * Temporary property to store old data before update
     */
    protected $tempOldData = [];

    // Enable callbacks for the model so events fire
    protected $allowCallbacks = true;

    public function __construct()
    {
        parent::__construct();
        helper('audit');

        // Dynamically append the callback methods to ensure they execute
        // even if a child model defines its own callback properties as empty arrays.
        if (!in_array('logAfterInsert', $this->afterInsert)) {
            $this->afterInsert[] = 'logAfterInsert';
        }
        if (!in_array('logBeforeUpdate', $this->beforeUpdate)) {
            $this->beforeUpdate[] = 'logBeforeUpdate';
        }
        if (!in_array('logAfterUpdate', $this->afterUpdate)) {
            $this->afterUpdate[] = 'logAfterUpdate';
        }
        if (!in_array('logBeforeDelete', $this->beforeDelete)) {
            $this->beforeDelete[] = 'logBeforeDelete';
        }
    }

    protected function logAfterInsert(array $data)
    {
        if (!isset($data['id'])) {
            return $data;
        }

        $recordId = $data['id'];
        $newData = $data['data'] ?? [];

        audit_log($this->table, 'CREATE', $recordId, null, $newData);

        return $data;
    }

    protected function logBeforeUpdate(array $data)
    {
        // Parameter $data['id'] is an array containing the IDs being updated.
        // E.g., ['id' => [1]]
        if (isset($data['id']) && is_array($data['id'])) {
            $recordId = $data['id'][0] ?? null;
            if ($recordId) {
                // Fetch existing data using a fresh builder to prevent wiping out model builder's state
                $oldRecord = $this->db->table($this->table)->where($this->primaryKey, $recordId)->get()->getRowArray();
                $this->tempOldData[$recordId] = $oldRecord;
            }
        }
        return $data;
    }

    protected function logAfterUpdate(array $data)
    {
        if (isset($data['id']) && is_array($data['id'])) {
            $recordId = $data['id'][0] ?? null;
            if ($recordId) {
                $oldData = $this->tempOldData[$recordId] ?? [];
                $newData = $data['data'] ?? [];

                // Unset to prevent memory leaks if many updates happen in a loop
                unset($this->tempOldData[$recordId]);

                audit_log($this->table, 'UPDATE', $recordId, $oldData, $newData);
            }
        }
        return $data;
    }

    protected function logBeforeDelete(array $data)
    {
        if (isset($data['id']) && is_array($data['id'])) {
            $recordId = $data['id'][0] ?? null;
            if ($recordId) {
                // Use fresh builder to prevent wiping model builder state
                $oldRecord = $this->db->table($this->table)->where($this->primaryKey, $recordId)->get()->getRowArray();

                audit_log($this->table, 'DELETE', $recordId, $oldRecord, null);
            }
        }
        return $data;
    }

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
        $field = $this->params['sidx'] ?? $this->primaryKey;
        $dir   = $this->params['sord'] ?? $this->sortDirection;

        if (in_array($field, $this->searchableFields) || $field === $this->primaryKey) {
            $dbField = $this->mapField($field);
            $query->orderBy($dbField, $dir);
        } else {
            $query->orderBy($this->table . '.' . $this->primaryKey, $this->sortDirection);
        }

        if ($field !== $this->primaryKey) {
            $query->orderBy($this->table . '.' . $this->primaryKey, $this->sortDirection);
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

        $rowNumber = $rowIndex + 1;

        return [
            'id'       => $id,
            'position' => $rowNumber,
            'page'     => ceil($rowNumber / $limit),
            'offset'   => max(0, $rowNumber - 1)
        ];
    }
    
}
