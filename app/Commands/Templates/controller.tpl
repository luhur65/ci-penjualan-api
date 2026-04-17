<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Libraries\ExcelMaker;
use App\Models\{name} as {name}Model;
use App\Services\{name}Service;
use CodeIgniter\HTTP\IncomingRequest;

class {name}Controller extends BaseController
{
    use ResponseTrait;

    protected ${var}Model;
    protected ${var}Service;

    /** @var IncomingRequest $request */
    protected $request;

    public function __construct()
    {
        $this->{var}Model   = new {name}Model();
        $this->{var}Service = new {name}Service();
    }

    /**
     * @ClassName 
     * @Keterangan TAMPILKAN DATA
     */
    public function index()
    {
        $params = $this->request->getGet();
        return $this->respond($this->{var}Service->getAll{name}($params));
    }

    public function show($id = null)
    {
        $data = $this->{var}Service->get{name}ById($id);

        if (!$data) {
            return $this->failNotFound("{name} not found");
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

            $result = $this->validateWithLabels($data, new \App\Validation\{name}CreateRequest());
            if ($result !== true) {
                return $this->respond(['errors' => $result], 422);
            }

            // Proses penyimpanan ke service
            $result = $this->{var}Service->create($data, $payload);

            return $this->respondCreated([
                'message' => '{name} successfully created',
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

            $result = $this->validateWithLabels($data, new \App\Validation\{name}UpdateRequest());
            if ($result !== true) {
                return $this->respond(['errors' => $result], 422);
            }

            $result = $this->{var}Service->update($data, $payload);

            return $this->respondUpdated([
                'message' => '{name} successfully updated',
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
        $data = $this->{var}Model->find($id);
        $payload = $this->request->getJSON(true);

        if (!$data) {
            return $this->failNotFound("{name} not found");
        }

        try {

            $result = $this->{var}Service->delete($id, $payload);

            return $this->respondDeleted([
                'message' => '{name} successfully deleted',
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
        $data = $this->{var}Service->getAll{name}($this->request->getGet());
        $excelData = [];

        // (Opsional) Jika Anda mengirimkan 'offset' dari FE, nomor urut bisa disesuaikan
        $offset = $this->request->getGet('offset') ?? 0;

        foreach ($data['data'] as $index => $item) {

            $statusData = json_decode($item->statusaktif, true);
            $statusMemo = $statusData['MEMO'] ?? '';

            $excelData[] = [
                $offset + $index + 1,      // Nomor Urut yang akurat
                $item->fullname ?? '',
                $item->email ?? '',
                $item->username ?? '',
                $statusMemo,               
                $item->modifiedby ?? '',
                $item->created_at ?? '',
                $item->updated_at ?? ''
            ];
        }

        // 4. Tentukan Judul Kolom
        $headers = [];

        // dd($excelData);

        $excelService = new ExcelMaker();
        return $excelService->generate('Laporan_{name}_' . date('Ymd_His'), $headers, $excelData);
    }

    /**
     * @ClassName 
     * @Keterangan REPORT DATA
     */
    public function report()
    {
        return "Report Data {name}";
    }

    public function fieldLength()
    {
        return $this->respond($this->{var}Model->getFieldLengths());
    }
}