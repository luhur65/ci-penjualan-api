<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\Penjualan as PenjualanModel;
use App\Services\PenjualanService;
use CodeIgniter\HTTP\IncomingRequest;

class PenjualanController extends BaseController
{
    use ResponseTrait;

    protected $penjualanModel;
    protected $penjualanService;

    /** @var IncomingRequest $request */
    protected $request;

    public function __construct()
    {
        $this->penjualanModel   = new PenjualanModel();
        $this->penjualanService = new PenjualanService();
    }

    /**
     * @ClassName 
     * @Keterangan TAMPILKAN DATA
     */
    public function index()
    {
        $params = $this->request->getGet();
        return $this->respond($this->penjualanService->getAllPenjualan($params));
    }

    public function show($id = null)
    {
        $data = $this->penjualanService->getPenjualanById($id);

        if (!$data) {
            return $this->failNotFound("Penjualan not found");
        }

        return $this->respond($data);
    }

    /**
     * Creates a new User.
     *
     * @ClassName 
     * @Keterangan TAMBAH DATA
     */
    public function create()
    {
        try {
            $authUserName = $this->authUserName();  
            $payload = $this->request->getJSON(true); // true = associative array

            $data = [
                // TODO: mapping data
            ];

            // Validasi manual menggunakan validate()
            if (!$this->penjualanModel->validate($data)) {
                return $this->respond([
                    'errors' => $this->penjualanModel->errors()  // Menyertakan pesan error validasi
                ], 422);  // Status code 422 for unprocessable entity
            }

            // Proses penyimpanan ke service
            $result = $this->penjualanService->create($data, $payload);

            return $this->respondCreated([
                'message' => 'Penjualan successfully created',
                'data' => $result
            ]);
            
        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
        }
    }

    /**
     * Updates an existing User by ID.
     *
     * @ClassName 
     * @Keterangan UPDATE DATA
     */
    public function update($id = null)
    {
        try {
            $authUserName = $this->authUserName();
            $payload = $this->request->getJSON(true); // true = associative array

            $data = [
                // TODO: mapping data
            ];

            if (!$this->penjualanModel->validate($data)) {
                return $this->respond([
                    'errors' => $this->penjualanModel->errors()
                ], 422);
            }

            $result = $this->penjualanService->update($data, $payload);

            return $this->respondUpdated([
                'message' => 'Penjualan successfully updated',
                'data' => $result
            ]);
            
        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
        }
    }

    /**
     * Deletes a User by ID.
     *
     * @ClassName 
     * @Keterangan DELETE DATA
     */
    public function delete($id = null)
    {
        $data = $this->penjualanModel->find($id);
        $payload = $this->request->getJSON(true);

        if (!$data) {
            return $this->failNotFound("Penjualan not found");
        }

        try {

            $result = $this->penjualanService->delete($id, $payload);

            return $this->respondDeleted([
                'message' => 'Penjualan successfully deleted',
                'data' => $result
            ]);

        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
        }
    }

    /**
     * @ClassName 
     * @Keterangan EXPORT DATA
     */
    public function export()
    {
        $data = $this->penjualanModel->getAll();
        return $this->respond($data);
    }

    /**
     * @ClassName 
     * @Keterangan REPORT DATA
     */
    public function report()
    {
        return "Report Data Penjualan";
    }

    public function fieldLength()
    {
        return $this->respond($this->penjualanModel->getFieldLengths());
    }
}