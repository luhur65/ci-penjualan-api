<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;
use App\Models\Role as RoleModel;

class Role extends BaseController
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

    public function fieldLength()
    {
        $model = new RoleModel();
        return $this->respond($model->getFieldLengths());
    }
}
