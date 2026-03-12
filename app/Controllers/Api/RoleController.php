<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;
use App\Models\Role as RoleModel;
use App\Services\RoleService;

class RoleController extends BaseController
{
    use ResponseTrait;

    /**
     * @ClassName 
     * @Keterangan TAMPILKAN DATA
     */
    public function index()
    {
        $roles = new RoleModel();
        return $this->respond([
            'data' => $roles->get(),
            'attributes' => [
                'totalRows' => $roles->totalRows,
                'totalPages' => $roles->totalPages
            ]
        ]);
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
        ];

        $db = db_connect();
        $db->transStart();

        try {
            $roleModel = new RoleModel();

            if (!$roleModel->validate($data)) {
                return $this->respond([
                    'errors' => $roleModel->errors()
                ], 422);
            }

            $roleService = new RoleService();
            $roleService->create($data);

            $db->transComplete();

            return $this->respond([
                'message' => 'Berhasil disimpan',
            ]);
        } catch (\Throwable $th) {

            $db->transRollback();

            return $this->respond([
                'message' => $th->getMessage(),
                'error' => $th->getLine(),
            ])->setStatusCode(500);
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
        ];

        $db = db_connect();
        $db->transStart();

        try {
            $roleModel = new RoleModel();

            if (!$roleModel->validate($data)) {
                return $this->respond([
                    'errors' => $roleModel->errors()
                ], 422);
            }

            $roleService = new RoleService();
            $roleService->update($data);

            $db->transComplete();

            return $this->respond([
                'message' => 'Berhasil disimpan',
            ]);
        } catch (\Throwable $th) {

            $db->transRollback();

            return $this->respond([
                'message' => $th->getMessage(),
                'error' => $th->getTrace(),
            ])->setStatusCode(500);
        }
    }

    public function show($id = null)
    {
        $role = (new RoleModel())->findOne($id);

        if (!$role) {
            return $this->failNotFound("Role not found");
        }

        return $this->respond($role);
    }

    /**
     * Deletes a Role by ID.
     *
     * @ClassName 
     * @Keterangan HAPUS DATA
     */
    public function delete($id = null)
    {
        $role = (new RoleModel())->findOne($id);

        if (!$role) {
            return $this->failNotFound("Role not found");
        }

        $db = db_connect();
        $db->transStart();

        try {
            $roleService = new RoleService();
            $roleService->delete($id);

            $db->transComplete();

            return $this->respond([
                'message' => 'Berhasil dihapus',
                'data'    => $role,
            ]);
        } catch (\Throwable $th) {
            $db->transRollback();
            return $this->failServerError($th->getMessage());
        }
    }

    public function fieldLength()
    {
        $model = new RoleModel();
        return $this->respond($model->getFieldLengths());
    }
}
