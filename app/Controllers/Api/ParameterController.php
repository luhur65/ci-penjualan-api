<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\ParameterService;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\IncomingRequest;

class ParameterController extends BaseController
{
    use ResponseTrait;

    /** @var IncomingRequest $request */
    protected $request;
    protected $parameterService;

    public function __construct()
    {
        $this->request = service('request');
        $this->parameterService = new ParameterService();
    }

    /**
     * @ClassName 
     * @Keterangan TAMPILKAN DATA
     */
    public function index()
    {
        $requestData = $this->request->getGet();
        return $this->respond($this->parameterService->getAllParameters($requestData));
    }

    public function getCombo()
    {
        $grp = $this->request->getVar('grp');
        $subgrp = $this->request->getVar('subgrp');
        return $this->respond($this->parameterService->getComboByMemo($grp, $subgrp));
    }

    public function lookup()
    {
        $grp = $this->request->getVar('grp');
        $subgrp = $this->request->getVar('subgrp');
        return $this->respond([
            'data' => $this->parameterService->getLookup($grp, $subgrp)
        ]);
    }

    /**
     * Creates a new Parameter.
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
                'grp'         => $payload['grp'] ?? null,
                'subgrp'      => $payload['subgrp'] ?? null,
                'kelompok'    => $payload['kelompok'] ?? null,
                'text'        => $payload['text'] ?? null,
                'memo'        => $payload['memo'] ?? null,
                'type'        => $payload['type'] ?? null,
                'is_default'  => $payload['is_default'] ?? 0,
                'modified_by' => $authUserName ?? null,
            ];

            $validation = \Config\Services::validation();

            if (!$validation->run($data, 'parameterCreate')) {
                return $this->respond([
                    'errors' => $validation->getErrors()
                ], 422);
            }

            $result = $this->parameterService->create($data, $payload);

            return $this->respondCreated([
                'message' => 'Parameter successfully created',
                'data' => $result
            ]);
        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
        }
    }

    /**
     * Updates an existing Parameter by ID.
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
                'grp'         => $payload['grp'] ?? null,
                'subgrp'      => $payload['subgrp'] ?? null,
                'kelompok'    => $payload['kelompok'] ?? null,
                'text'        => $payload['text'] ?? null,
                'memo'        => $payload['memo'] ?? null,
                'type'        => $payload['type'] ?? null,
                'is_default'  => $payload['is_default'] ?? 0,
                'modified_by' => $authUserName ?? null,
            ];

            $validation = \Config\Services::validation();

            if (!$validation->run($data, 'parameterUpdate')) {
                return $this->respond([
                    'errors' => $validation->getErrors()
                ], 422);
            }

            $result = $this->parameterService->update($data, $payload);

            return $this->respondUpdated([
                'message' => 'Parameter successfully updated',
                'data' => $result
            ]);
        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
        }
    }

    /**
     * Deletes a Parameter by ID.
     *
     * @ClassName
     * @Keterangan DELETE DATA
     */
    public function delete($id = null)
    {
        $parameter = $this->parameterService->getParameterById($id);
        $payload = $this->request->getJSON(true);

        if (!$parameter) {
            return $this->failNotFound("Parameter not found");
        }

        try {
            $result = $this->parameterService->delete($id, $payload ?? []);

            return $this->respondDeleted([
                'message' => 'Parameter successfully deleted',
                'data' => $result
            ]);
        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
        }
    }
}
