<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\Role;
use App\Services\RoleService;
use CodeIgniter\HTTP\IncomingRequest;

class RoleController extends BaseController
{
    use ResponseTrait;

    protected $roleService;
    protected $roleModel;

    /** @var IncomingRequest $request */
    protected $request;

    public function __construct()
    {
        $this->roleService = new RoleService();
        $this->roleModel = new Role();
    }

    /**
     * @ClassName 
     * @Keterangan TAMPILKAN DATA
     */
    public function index()
    {
        $requestData = $this->request->getGet();
        return $this->respond($this->roleService->getAllRoles($requestData));
    }

    public function show($id = null)
    {
        $role = $this->roleService->getRoleById($id);

        if (!$role) {
            return $this->failNotFound("Role not found");
        }

        return $this->respond($role);
    }

    /**
     * Creates a new Role.
     * 
     * @ClassName 
     * @Keterangan TAMBAH DATA
     */
    public function create()
    {
        $payload = $this->request->getJSON(true);

        $data = [
            'rolename'   => $payload['rolename'] ?? '',
            'modified_by'   => $this->authUserName() ?? null,
        ];

        try {
            $result = $this->validateWithLabels($data, new \App\Validation\RoleCreateRequest());
            if ($result !== true) {
                return $this->respond(['errors' => $result], 422);
            }

            $result = $this->roleService->create($data, $payload);

            return $this->respondCreated([
                'message' => 'Berhasil disimpan',
                'data'    => $result,
            ]);
        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
            // return $this->respond([
            //     'message' => $th->getMessage(),
            //     'error' => $th->getLine(),
            // ])->setStatusCode(500);
        }
    }

    /**
     * Updates an existing Role by ID.
     * 
     * @ClassName 
     * @Keterangan UBAH DATA
     */
    public function update($id = null)
    {
        $payload = $this->request->getJSON(true);

        $data = [
            'id'         => $id,
            'rolename'   => $payload['rolename'] ?? '',
            'acosIds'    => \json_decode($payload['acosIds']) ?? '',
            'modified_by'   => $this->authUserName() ?? null,
        ];

        try {
            $result = $this->validateWithLabels($data, new \App\Validation\RoleUpdateRequest());
            if ($result !== true) {
                return $this->respond(['errors' => $result], 422);
            }

            $result = $this->roleService->update($data, $payload);

            return $this->respondUpdated([
                'message' => 'Berhasil disimpan',
                'data'    => $result,
            ]);
            
        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
            // return $this->respond([
            //     'message' => $th->getMessage(),
            //     'error' => $th->getTrace(),
            // ])->setStatusCode(500);
        }
    }

    /**
     * Deletes a Role by ID.
     * 
     * @ClassName 
     * @Keterangan HAPUS DATA
     */
    public function delete($id = null)
    {
        $role = $this->roleModel->find($id);
        $payload = $this->request->getJSON(true);

        if (!$role) {
            return $this->failNotFound("Role not found");
        }

        try {
            $result = $this->roleService->delete($id, $payload);

            return $this->respondDeleted([
                'message' => 'Berhasil dihapus',
                'data'    => $result,
            ]);

        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
        }
    }

    /**
     * @ClassName 
     * @Keterangan EKSPOR DATA
     */
    public function export()
    {
        return "export";
    }

    public function fieldLength()
    {
        return $this->respond($this->roleModel->getFieldLengths());
    }
}
