<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Services\ErrorService;
use CodeIgniter\HTTP\IncomingRequest;

class ErrorController extends BaseController
{
    use ResponseTrait;

    /** @var IncomingRequest $request */
    protected $request;
    protected $errorService;

    public function __construct()
    {
        $this->errorService = new ErrorService();
    }

    /**
     * @ClassName
     * @Keterangan TAMPILKAN DATA
     */
    public function index()
    {
        $requestData = $this->request->getGet();
        return $this->respond($this->errorService->getAllErrors($requestData));
    }

    public function show($id = null)
    {
        $error = $this->errorService->getErrorById($id);
        if (!$error) {
            return $this->failNotFound("Error not found");
        }
        return $this->respond($error);
    }

    /**
     * Creates a new Error.
     *
     * @ClassName
     * @Keterangan TAMBAH DATA
     */
    public function create()
    {
        try {
            $authUserName = $this->authUserName();
            $payload = $this->request->getJSON(true);

            $data = [
                'kodeerror'   => $payload['kodeerror'] ?? null,
                'keterangan'  => $payload['keterangan'] ?? null,
                'modified_by' => $authUserName ?? null,
            ];

            $result = $this->validateWithLabels($data, new \App\Validation\ErrorCreateRequest());
            if ($result !== true) {
                return $this->respond(['errors' => $result], 422);
            }

            $result = $this->errorService->create($data, $payload);

            return $this->respondCreated([
                'message' => 'Error successfully created',
                'data' => $result
            ]);
        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
        }
    }

    /**
     * Updates an existing Error by ID.
     *
     * @ClassName
     * @Keterangan UPDATE DATA
     */
    public function update($id = null)
    {
        try {
            $authUserName = $this->authUserName();
            $payload = $this->request->getJSON(true);

            $data = [
                'id'          => $id,
                'kodeerror'   => $payload['kodeerror'] ?? null,
                'keterangan'  => $payload['keterangan'] ?? null,
                'modified_by' => $authUserName ?? null,
            ];

            $result = $this->validateWithLabels($data, new \App\Validation\ErrorUpdateRequest());
            if ($result !== true) {
                return $this->respond(['errors' => $result], 422);
            }

            $result = $this->errorService->update($data, $payload);

            return $this->respondUpdated([
                'message' => 'Error successfully updated',
                'data' => $result
            ]);
        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
        }
    }

    /**
     * Deletes a Error by ID.
     *
     * @ClassName
     * @Keterangan DELETE DATA
     */
    public function delete($id = null)
    {
        $error = $this->errorService->getErrorById($id);
        $payload = $this->request->getJSON(true);

        if (!$error) {
            return $this->failNotFound("Error not found");
        }

        try {
            $result = $this->errorService->delete($id, $payload ?? []);

            return $this->respondDeleted([
                'message' => 'Error successfully deleted',
                'data' => $result
            ]);
        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
        }
    }
}
