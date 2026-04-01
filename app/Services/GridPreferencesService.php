<?php

namespace App\Services;

use App\Models\GridPreferences;

class GridPreferencesService
{
    protected GridPreferences $model;

    public function __construct()
    {
        $this->model = new GridPreferences();
    }

    // ─── GET ──────────────────────────────────────────────────────

    /**
     * Ambil preferensi milik user untuk satu grid_name.
     * Return null jika belum pernah disimpan.
     */
    public function getPreference(int $userId, string $gridName): ?array
    {
        $row = $this->model->findByUserAndGrid($userId, $gridName);

        if (!$row) {
            return null;
        }

        $row['preferences'] = json_decode($row['preferences'], true);
        return $row;
    }

    // ─── SAVE (upsert) ────────────────────────────────────────────

    /**
     * Simpan (insert/update) preferensi.
     * Sanitasi kolom yang diizinkan sebelum disimpan.
     *
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function savePreference(int $userId, string $gridName, array $preferences): array
    {
        if (empty($gridName)) {
            throw new \InvalidArgumentException('grid_name tidak boleh kosong.');
        }

        if (empty($preferences)) {
            throw new \InvalidArgumentException('preferences tidak boleh kosong.');
        }

        // Sanitasi: hanya simpan field yang diizinkan per item
        $clean = array_map(function (array $col): array {
            return [
                'name'   => (string)  ($col['name']   ?? ''),
                'width'  => (int)     ($col['width']  ?? 100),
                'hidden' => (bool)    ($col['hidden'] ?? false),
                'order'  => (int)     ($col['order']  ?? 0),
            ];
        }, $preferences);

        return $this->model->upsert($userId, $gridName, $clean);
    }

    // ─── DELETE ───────────────────────────────────────────────────

    /**
     * Hapus preferensi (reset ke default).
     * Return true jika berhasil dihapus, false jika tidak ada datanya.
     */
    public function deletePreference(int $userId, string $gridName): bool
    {
        return $this->model->deleteByUserAndGrid($userId, $gridName);
    }
}
