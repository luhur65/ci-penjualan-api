<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;
use App\Models\Role as RoleModel;

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

            $role   = $roleModel->processStore($data);

            $db->transComplete();

            return $this->respond([
                'message' => 'Berhasil disimpan',
                'data'    => $role,
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

            $role   = $roleModel->processUpdate($data);

            $db->transComplete();

            return $this->respond([
                'message' => 'Berhasil disimpan',
                'data'    => $role,
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
     * @ClassName 
     * @Keterangan HAPUS DATA
     */
    public function delete($id = null)
    {
        $role = (new RoleModel())->findOne($id);

        if (!$role) {
            return $this->failNotFound("Role not found");
        }

        $roleModel = new RoleModel();
        $roleModel->delete($id);

        return $this->respond([
            'message' => 'Berhasil dihapus',
            'data'    => $role,
        ]);
    }

    public function fieldLength()
    {
        $model = new RoleModel();
        return $this->respond($model->getFieldLengths());
    }
}
