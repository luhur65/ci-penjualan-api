<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Libraries\ExcelMaker;
use App\Models\TestingMasterDetail as TestingMasterDetailModel;
use App\Models\TestingMasterDetailItem as TestingMasterDetailItemModel;
use App\Services\TestingMasterDetailService;
use CodeIgniter\HTTP\IncomingRequest;

class TestingMasterDetailController extends BaseController
{
    use ResponseTrait;

    protected $masterModel;
    protected $detailModel;
    protected $service;

    /** @var IncomingRequest $request */
    protected $request;

    public function __construct()
    {
        $this->masterModel = new TestingMasterDetailModel();
        $this->detailModel = new TestingMasterDetailItemModel();
        $this->service     = new TestingMasterDetailService();
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
    public function create()
    {
        try {
            $authUserName = $this->authUserName();
            $payload      = $this->request->getJSON(true);

            $data = [
                'no_bukti'     => $payload['no_bukti']     ?? null,
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
        $params = $this->request->getGet();
        // Hapus pagination untuk export semua
        unset($params['page'], $params['rows']);
        $params['rows'] = 999999;

        $data = $this->service->getAllTestingMasterDetail($params);

        $excelData = [];
        $offset    = $this->request->getGet('offset') ?? 0;

        foreach ($data['data'] as $index => $item) {
            $excelData[] = [
                $offset + $index + 1,
                $item->no_bukti      ?? '',
                $item->tgl_bukti     ?? '',
                $item->nama_pelanggan ?? '',
                $item->modifiedby    ?? '',
                $item->created_at    ?? '',
                $item->updated_at    ?? '',
            ];
        }

        $headers = [
            'No',
            'No. Bukti',
            'Tanggal',
            'Pelanggan',
            'Modified By',
            'Created At',
            'Updated At',
        ];

        $excelService = new ExcelMaker();
        return $excelService->generate('Laporan_Penjualan_' . date('Ymd_His'), $headers, $excelData);
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
        $params = $this->request->getGet();
        $params['rows'] = 999999;

        $data = $this->service->getAllDetail($penjualanId, $params);

        $excelData = [];
        foreach ($data['data'] as $index => $item) {
            $excelData[] = [
                $index + 1,
                $item->nama_barang ?? '',
                $item->qty         ?? 0,
                number_format($item->harga    ?? 0, 2, ',', '.'),
                number_format($item->subtotal ?? 0, 2, ',', '.'),
                $item->modifiedby  ?? '',
                $item->created_at  ?? '',
            ];
        }

        $headers = [
            'No',
            'Nama Barang',
            'Qty',
            'Harga Satuan',
            'Subtotal',
            'Modified By',
            'Created At',
        ];

        $excelService = new ExcelMaker();
        return $excelService->generate('Laporan_Detail_Penjualan_' . date('Ymd_His'), $headers, $excelData);
    }
}