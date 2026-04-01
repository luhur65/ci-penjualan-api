<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Services\GridPreferencesService;
use CodeIgniter\HTTP\IncomingRequest;

class GridPreferencesController extends BaseController
{
    use ResponseTrait;

    protected GridPreferencesService $service;

    /** @var IncomingRequest $request */
    protected $request;

    public function __construct()
    {
        $this->service = new GridPreferencesService();
    }

    // ─── Ambil user_id dari JWT yang sudah divalidasi ─────────────
    // Sesuaikan dengan cara project ini decode JWT.
    // Contoh di bawah menggunakan authUserId() dari BaseController
    // yang sudah ada di project (lihat LoginController / BaseController).
    private function getUserId(): int
    {
        return (int) $this->authUserId();
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/grid-preferences?grid_name=user_master_grid
    // ─────────────────────────────────────────────────────────────
    public function getGridPreferences()
    {
        $gridName = $this->request->getGet('grid_name');

        if (empty($gridName)) {
            return $this->fail('Parameter grid_name wajib diisi.', 400);
        }

        $data = $this->service->getPreference($this->getUserId(), $gridName);

        return $this->respond([
            'status'      => 'ok',
            'preferences' => $data ? $data['preferences'] : null,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/grid-preferences
    // Body: { "grid_name": "...", "preferences": [...] }
    // ─────────────────────────────────────────────────────────────
    public function saveGridPreferences()
    {
        $body = $this->request->getJSON(true);

        $gridName    = $body['grid_name']   ?? null;
        $preferences = $body['preferences'] ?? null;

        if (empty($gridName)) {
            return $this->fail('grid_name wajib diisi.', 400);
        }

        if (!is_array($preferences) || empty($preferences)) {
            return $this->fail('preferences wajib berupa array dan tidak boleh kosong.', 400);
        }

        try {
            $result = $this->service->savePreference(
                $this->getUserId(),
                $gridName,
                $preferences
            );

            return $this->respond([
                'status'      => 'ok',
                'message'     => 'Preferensi berhasil disimpan.',
                'preferences' => $result['preferences'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 400);
        } catch (\Throwable $th) {
            log_message('error', '[GridPreferences] save: ' . $th->getMessage());
            return $this->failServerError('Terjadi kesalahan saat menyimpan preferensi.');
        }
    }

    // ─────────────────────────────────────────────────────────────
    // DELETE /api/grid-preferences/:grid_name
    // ─────────────────────────────────────────────────────────────
    public function deleteGridPreferences(string $gridName = '')
    {
        if (empty($gridName)) {
            return $this->fail('grid_name wajib diisi.', 400);
        }

        $deleted = $this->service->deletePreference($this->getUserId(), $gridName);

        if (!$deleted) {
            return $this->failNotFound('Preferensi tidak ditemukan.');
        }

        return $this->respond([
            'status'  => 'ok',
            'message' => 'Preferensi berhasil dihapus.',
        ]);
    }
}
