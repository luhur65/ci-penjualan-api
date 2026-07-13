<?php

namespace App\Services;

use App\Models\TestingMasterDetail;
use App\Models\TestingMasterDetailItem;
use Config\Database;

class TestingMasterDetailService
{
    protected $masterModel;
    protected $detailModel;
    protected $db;

    public function __construct()
    {
        $this->masterModel = new TestingMasterDetail();
        $this->detailModel = new TestingMasterDetailItem();
        $this->db          = Database::connect();
    }

    // =====================================================
    // MASTER (tbl_penjualan)
    // =====================================================

    public function getAllTestingMasterDetail(array $params): array
    {
        $data = $this->masterModel->setRequestParameters($params)->getAll();

        return [
            'data' => $data['data'],
            'attributes' => [
                'totalRows'  => $data['totalRows'],
                'totalPages' => $data['totalPages'],
            ],
        ];
    }

    public function getTestingMasterDetailById($id)
    {
        return $this->masterModel->findOne($id);
    }

    public function create(array $data, array $params, array $items = []): array
    {
        $this->db->transBegin();

        try {
            // Generate UUID_v7 untuk master
            $uuid = $this->generateUuidV7();

            $insertData = array_merge($data, ['uuid' => $uuid]);
            $this->db->table('tbl_penjualan')->insert($insertData);

            if ($this->db->affectedRows() === 0) {
                throw new \Exception("Error inserting penjualan.");
            }

            // Insert semua item detail dari form inline
            foreach ($items as $item) {
                $namaBarang = trim($item['nama_barang'] ?? '');
                if ($namaBarang === '') continue; // skip baris kosong

                $detailUuid = $this->generateUuidV7();
                $this->db->table('tbl_penjualan_detail')->insert([
                    'id'          => $detailUuid,
                    'penjualan_id' => $uuid,
                    'nama_barang'  => $namaBarang,
                    'qty'          => (int) ($item['qty']   ?? 1),
                    'harga'        => (float) ($item['harga'] ?? 0),
                    'modifiedby'   => $data['modifiedby'],
                ]);
            }

            $position = $this->masterModel->getPosition($uuid, $params);

            $this->db->transCommit();

            return $position;

        } catch (\Throwable $th) {
            $this->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
        }
    }

    public function update(array $data, array $params, array $items = []): array
    {
        $this->db->transBegin();

        try {
            $id = $data['id'];
            unset($data['id']);

            $this->db->table('tbl_penjualan')
                ->where('uuid', $id)
                ->update($data);

            // Jika ada items dari form inline → replace semua detail
            if (!empty($items)) {
                // Hapus semua detail lama
                $this->db->table('tbl_penjualan_detail')
                    ->where('penjualan_id', $id)
                    ->delete();

                // Insert ulang dari form
                foreach ($items as $item) {
                    $namaBarang = trim($item['nama_barang'] ?? '');
                    if ($namaBarang === '') continue;

                    $detailUuid = $this->generateUuidV7();
                    $this->db->table('tbl_penjualan_detail')->insert([
                        'id'           => $detailUuid,
                        'penjualan_id' => $id,
                        'nama_barang'  => $namaBarang,
                        'qty'          => (int) ($item['qty']   ?? 1),
                        'harga'        => (float) ($item['harga'] ?? 0),
                        'modifiedby'   => $data['modifiedby'],
                    ]);
                }
            }

            $position = $this->masterModel->getPosition($id, $params);

            $this->db->transCommit();

            return $position;

        } catch (\Throwable $th) {
            $this->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
        }
    }

    public function delete($id, array $params): array
    {
        $this->db->transBegin();

        try {
            $position = $this->masterModel->getPosition($id, $params, true);

            // Hapus detail dulu
            $this->db->table('tbl_penjualan_detail')
                ->where('penjualan_id', $id)
                ->delete();

            // Hapus master
            $this->db->table('tbl_penjualan')
                ->where('uuid', $id)
                ->delete();

            $this->db->transCommit();

            return $position;

        } catch (\Throwable $th) {
            $this->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
        }
    }

    // =====================================================
    // DETAIL (tbl_penjualan_detail)
    // =====================================================

    public function getAllDetail(string $penjualanId, array $params): array
    {
        $data = $this->detailModel->setRequestParameters($params)->getAllByPenjualan($penjualanId);

        return [
            'data' => $data['data'],
            'attributes' => [
                'totalRows'  => $data['totalRows'],
                'totalPages' => $data['totalPages'],
                'total'      => $this->detailModel->getTotalByPenjualan($penjualanId),
            ],
        ];
    }

    public function getDetailById($id)
    {
        return $this->detailModel->findOne($id);
    }

    public function createDetail(array $data, array $params): array
    {
        $this->db->transBegin();

        try {
            $uuid = $this->generateUuidV7();

            $insertData = array_merge($data, ['id' => $uuid]);

            $this->db->table('tbl_penjualan_detail')->insert($insertData);

            if ($this->db->affectedRows() === 0) {
                throw new \Exception("Error inserting detail penjualan.");
            }

            $position = $this->detailModel->getPosition($uuid, $params);

            $this->db->transCommit();

            return $position;

        } catch (\Throwable $th) {
            $this->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
        }
    }

    public function updateDetail(array $data, array $params): array
    {
        $this->db->transBegin();

        try {
            $id = $data['id'];
            unset($data['id']);

            $this->db->table('tbl_penjualan_detail')
                ->where('id', $id)
                ->update($data);

            $position = $this->detailModel->getPosition($id, $params);

            $this->db->transCommit();

            return $position;

        } catch (\Throwable $th) {
            $this->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
        }
    }

    public function deleteDetail($id, array $params): array
    {
        $this->db->transBegin();

        try {
            $position = $this->detailModel->getPosition($id, $params, true);

            $this->db->table('tbl_penjualan_detail')
                ->where('id', $id)
                ->delete();

            $this->db->transCommit();

            return $position;

        } catch (\Throwable $th) {
            $this->db->transRollback();
            log_message('error', $th->getMessage());
            throw $th;
        }
    }

    // =====================================================
    // HELPER
    // =====================================================

    /**
     * Generate UUID v7 via MariaDB function
     */
    protected function generateUuidV7(): string
    {
        try {
            // Coba fungsi custom UUID_v7() jika tersedia di server
            $result = $this->db->query("SELECT UUID_v7() as uuid");
            if ($result) {
                $row = $result->getRowObject();
                if ($row && isset($row->uuid)) {
                    return $row->uuid;
                }
            }
        } catch (\Throwable $th) {
            // Abaikan error jika UUID_v7() tidak dikenali
        }

        // Fallback: Gunakan standar UUID v1 bawaan MySQL/MariaDB
        $result = $this->db->query("SELECT UUID() as uuid")->getRowObject();
        return $result->uuid;
    }
}