<?php

namespace App\Models;

use CodeIgniter\Model;

class GridPreferences extends Model
{
    protected $table            = 'gridpreferences';
    protected $primaryKey       = 'id';
    protected $sortDirection    = 'ASC';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields = ['user_id', 'grid_name', 'preferences'];

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
    protected $validationRules = [
        'user_id'     => 'required|integer',
        'grid_name'   => 'required|max_length[100]',
        'preferences' => 'required',
    ];

    protected $validationMessages = [
        'user_id'     => ['required' => 'user_id wajib diisi.'],
        'grid_name'   => ['required' => 'grid_name wajib diisi.'],
        'preferences' => ['required' => 'preferences wajib diisi.'],
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

    /**
     * Cari satu record berdasarkan user_id + grid_name.
     */
    public function findByUserAndGrid(int $userId, string $gridName): ?array
    {
        return $this->where('user_id', $userId)
            ->where('grid_name', $gridName)
            ->first();
    }

    /**
     * Upsert: insert jika belum ada, update jika sudah ada.
     * Return array record hasil simpan.
     *
     * @throws \Exception
     */
    public function upsert(int $userId, string $gridName, array $preferences): array
    {
        $existing = $this->findByUserAndGrid($userId, $gridName);

        $data = [
            'user_id'     => $userId,
            'grid_name'   => $gridName,
            'preferences' => json_encode($preferences),
        ];

        if ($existing) {
            $this->update($existing['id'], $data);
            $id = $existing['id'];
        } else {
            $this->insert($data);
            $id = $this->getInsertID();
        }

        // Kembalikan record yang tersimpan
        $record = $this->find($id);

        // Decode preferences agar response berupa array, bukan string
        $record['preferences'] = json_decode($record['preferences'], true);

        return $record;
    }

    /**
     * Hapus preferensi milik satu user untuk satu grid.
     * Return true jika berhasil, false jika tidak ditemukan.
     */
    public function deleteByUserAndGrid(int $userId, string $gridName): bool
    {
        $existing = $this->findByUserAndGrid($userId, $gridName);

        if (!$existing) {
            return false;
        }

        return (bool) $this->delete($existing['id']);
    }

}

