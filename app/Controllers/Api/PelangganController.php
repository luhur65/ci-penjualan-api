<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class PelangganController extends BaseController
{
    use ResponseTrait;

    /**
     * GET /api/pelanggan - Daftar semua pelanggan untuk dropdown
     */
    public function index()
    {
        $db = \Config\Database::connect();

        $pelanggan = $db->table('tbl_pelanggan')
            ->select('id, nama_pelanggan')
            ->orderBy('nama_pelanggan', 'ASC')
            ->get()
            ->getResultArray();

        return $this->respond([
            'data' => $pelanggan,
        ]);
    }
}
