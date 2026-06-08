<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Libraries\ExcelMaker;
use App\Models\TestingMasterDetail as TestingMasterDetailModel;
use App\Models\TestingMasterDetailItem as TestingMasterDetailItemModel;
use App\Services\TestingMasterDetailService;
use App\Services\RunningNumberService;
use CodeIgniter\HTTP\IncomingRequest;

class TestingMasterDetailController extends BaseController
{
    use ResponseTrait;

    protected $masterModel;
    protected $detailModel;
    protected $service;
    protected $runningNumber;

    /** @var IncomingRequest $request */
    protected $request;

    public function __construct()
    {
        $this->masterModel   = new TestingMasterDetailModel();
        $this->detailModel   = new TestingMasterDetailItemModel();
        $this->service       = new TestingMasterDetailService();
        $this->runningNumber = new RunningNumberService();
    }

    // =====================================================================
    // MASTER - tbl_penjualan
    // =====================================================================

    /**
     * @ClassName 
     * @Keterangan TAMPILKAN DATA MASTER
     */
    public function index()
    {
        $params = $this->request->getGet();
        return $this->respond($this->service->getAllTestingMasterDetail($params));
    }

    public function show($id = null)
    {
        $data = $this->service->getTestingMasterDetailById($id);

        if (!$data) {
            return $this->failNotFound("Penjualan tidak ditemukan");
        }

        return $this->respond($data);
    }

    /**
     * @ClassName 
     * @Keterangan TAMBAH DATA MASTER
     */
    /**
     * @ClassName
     * @Keterangan GET NOMOR BUKTI BERIKUTNYA
     */
    public function nextnumber()
    {
        $nextNo = $this->runningNumber->generate(
            table:     'tbl_penjualan',
            column:    'no_bukti',
            prefix:    'INV',
            separator: '-',
            pad:       7
        );

        return $this->respond(['next_number' => $nextNo]);
    }

    public function create()
    {
        try {
            $authUserName = $this->authUserName();
            $payload      = $this->request->getJSON(true);

            // Auto-generate no_bukti jika tidak dikirim dari FE
            $noBukti = !empty($payload['no_bukti'])
                ? $payload['no_bukti']
                : $this->runningNumber->generate(
                    table:     'tbl_penjualan',
                    column:    'no_bukti',
                    prefix:    'INV',
                    separator: '-',
                    pad:       7
                );

            $data = [
                'no_bukti'     => $noBukti,
                'tgl_bukti'    => $payload['tgl_bukti']    ?? null,
                'pelanggan_id' => $payload['pelanggan_id'] ?? null,
                'modifiedby'   => $authUserName,
            ];

            $result = $this->validateWithLabels($data, new \App\Validation\TestingMasterDetailCreateRequest());
            if ($result !== true) {
                return $this->respond(['errors' => $result], 422);
            }

            // Items detail dari form inline (opsional)
            $items = $payload['items'] ?? [];

            $result = $this->service->create($data, $payload, $items);

            return $this->respondCreated([
                'message' => 'Penjualan berhasil ditambahkan',
                'data'    => $result,
            ]);

        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
        }
    }

    /**
     * @ClassName 
     * @Keterangan UPDATE DATA MASTER
     */
    public function update($id = null)
    {
        try {
            $authUserName = $this->authUserName();
            $payload      = $this->request->getJSON(true);

            // Items detail dari form inline (opsional)
            $items = $payload['items'] ?? [];

            $data = [
                'id'          => $id,
                'no_bukti'    => $payload['no_bukti']    ?? null,
                'tgl_bukti'   => $payload['tgl_bukti']   ?? null,
                'pelanggan_id' => $payload['pelanggan_id'] ?? null,
                'modifiedby'  => $authUserName,
            ];

            $result = $this->validateWithLabels($data, new \App\Validation\TestingMasterDetailUpdateRequest());
            if ($result !== true) {
                return $this->respond(['errors' => $result], 422);
            }

            $result = $this->service->update($data, $payload, $items);

            return $this->respondUpdated([
                'message' => 'Penjualan berhasil diupdate',
                'data'    => $result,
            ]);

        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
        }
    }

    /**
     * @ClassName 
     * @Keterangan DELETE DATA MASTER
     */
    public function delete($id = null)
    {
        $data    = $this->masterModel->findOne($id);
        $payload = $this->request->getJSON(true) ?? [];

        if (!$data) {
            return $this->failNotFound("Penjualan tidak ditemukan");
        }

        try {
            $result = $this->service->delete($id, (array) $payload);

            return $this->respondDeleted([
                'message' => 'Penjualan berhasil dihapus',
                'data'    => $result,
            ]);

        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
        }
    }

    /**
     * @ClassName
     * @Keterangan EXPORT DATA MASTER
     */
    public function export()
    {
        $filters = $this->request->getGet();
        $userId  = $this->authUserId() ?? null;

        // Push ke background queue — tidak block request
        service('queue')->push('default', 'export_penjualan_master', [
            'userId'  => $userId,
            'filters' => $filters,
        ]);

        return $this->respond([
            'message' => 'Export data penjualan sedang diproses. Notifikasi akan muncul saat file siap diunduh.',
        ]);
    }

    /**
     * @ClassName 
     * @Keterangan REPORT DATA
     */
    public function report()
    {
        return $this->respond(['message' => 'Report Data TestingMasterDetail']);
    }

    public function fieldLength()
    {
        return $this->respond($this->masterModel->getFieldLengths());
    }

    // =====================================================================
    // DETAIL - tbl_penjualan_detail
    // =====================================================================

    /**
     * @ClassName 
     * @Keterangan TAMPILKAN DATA DETAIL
     * GET /api/testingmasterdetail/{penjualan_id}/detail
     */
    public function indexDetail($penjualanId = null)
    {
        // Return empty result jika penjualan_id tidak valid (misal placeholder)
        if (!$penjualanId || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $penjualanId)) {
            return $this->respond([
                'data'       => [],
                'attributes' => ['totalRows' => 0, 'totalPages' => 0, 'total' => 0],
            ]);
        }

        $params = $this->request->getGet();
        $params['penjualan_id'] = $penjualanId;

        return $this->respond($this->service->getAllDetail($penjualanId, $params));
    }

    /**
     * @ClassName
     * @Keterangan TAMPILKAN SATU DATA DETAIL
     * GET /api/testingmasterdetail/detail/{id}
     */
    public function showDetail($id = null)
    {
        $data = $this->service->getDetailById($id);

        if (!$data) {
            return $this->failNotFound("Detail tidak ditemukan");
        }

        return $this->respond($data);
    }

    /**
     * @ClassName 
     * @Keterangan TAMBAH DATA DETAIL
     * POST /api/testingmasterdetail/detail
     */
    public function createDetail()
    {
        try {
            $authUserName = $this->authUserName();
            $payload      = $this->request->getJSON(true);

            $data = [
                'penjualan_id' => $payload['penjualan_id'] ?? null,
                'nama_barang'  => $payload['nama_barang']  ?? null,
                'qty'          => $payload['qty']          ?? null,
                'harga'        => $payload['harga']        ?? null,
                'modifiedby'   => $authUserName,
            ];

            $result = $this->validateWithLabels($data, new \App\Validation\TestingMasterDetailItemCreateRequest());
            if ($result !== true) {
                return $this->respond(['errors' => $result], 422);
            }

            $result = $this->service->createDetail($data, $payload);

            return $this->respondCreated([
                'message' => 'Detail berhasil ditambahkan',
                'data'    => $result,
            ]);

        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
        }
    }

    /**
     * @ClassName 
     * @Keterangan UPDATE DATA DETAIL
     * PATCH /api/testingmasterdetail/detail/{id}
     */
    public function updateDetail($id = null)
    {
        try {
            $authUserName = $this->authUserName();
            $payload      = $this->request->getJSON(true);

            $data = [
                'id'          => $id,
                'nama_barang' => $payload['nama_barang'] ?? null,
                'qty'         => $payload['qty']         ?? null,
                'harga'       => $payload['harga']       ?? null,
                'modifiedby'  => $authUserName,
            ];

            $result = $this->validateWithLabels($data, new \App\Validation\TestingMasterDetailItemUpdateRequest());
            if ($result !== true) {
                return $this->respond(['errors' => $result], 422);
            }

            $result = $this->service->updateDetail($data, $payload);

            return $this->respondUpdated([
                'message' => 'Detail berhasil diupdate',
                'data'    => $result,
            ]);

        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
        }
    }

    /**
     * @ClassName 
     * @Keterangan DELETE DATA DETAIL
     * DELETE /api/testingmasterdetail/detail/{id}
     */
    public function deleteDetail($id = null)
    {
        $data    = $this->detailModel->findOne($id);
        $payload = $this->request->getJSON(true) ?? [];

        if (!$data) {
            return $this->failNotFound("Detail tidak ditemukan");
        }

        try {
            $result = $this->service->deleteDetail($id, (array) $payload);

            return $this->respondDeleted([
                'message' => 'Detail berhasil dihapus',
                'data'    => $result,
            ]);

        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
        }
    }

    /**
     * @ClassName
     * @Keterangan EXPORT DATA DETAIL
     */
    public function exportDetail($penjualanId = null)
    {
        $filters = $this->request->getGet();
        $userId  = $this->authUserId() ?? null;

        // Ambil no_bukti untuk label nama file
        $master  = $this->service->getTestingMasterDetailById($penjualanId);
        $noBukti = $master->no_bukti ?? $penjualanId;

        // Push ke background queue
        service('queue')->push('default', 'export_penjualan_detail', [
            'userId'      => $userId,
            'penjualanId' => $penjualanId,
            'noBukti'     => $noBukti,
            'filters'     => $filters,
        ]);

        return $this->respond([
            'message' => "Export detail penjualan ({$noBukti}) sedang diproses. Notifikasi akan muncul saat file siap diunduh.",
        ]);
    }
}