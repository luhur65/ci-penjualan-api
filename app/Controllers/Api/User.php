<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\User as UserModel;

class User extends BaseController
{
    use ResponseTrait;
    
    /**
     * @ClassName 
     * @Keterangan TAMPILKAN DATA
     */
    public function index()
    {
        $users = new UserModel();
        return $this->respond([
            'data' => $users->get(),
            'attributes' => [
                'totalRows' => $users->totalRows,
                'totalPages' => $users->totalPages
            ]
        ]);
    }

    public function show($id = null)
    {
        $user = (new UserModel())->find1($id);

        if (!$user) {
            return $this->failNotFound("User not found");
        }

        return $this->respond($user);
    }

    /**
     * @ClassName 
     * @Keterangan TAMBAH DATA
     */
    public function create()
    {
        
        try {

            $data = [
                'fullname' => $this->request->getPost('fullname'),
                'email'    => $this->request->getPost('email'),
                'username' => $this->request->getPost('username'),
            ];

            // Validasi manual menggunakan validate()
            $userModel = new UserModel();
            if (!$userModel->validate($data)) {
                // return $this->failValidationErrors($userModel->errors());
                return $this->respond([
                    'errors' => $userModel->errors()  // Menyertakan pesan error validasi
                ], 422);  // Status code 422 for unprocessable entity
            }

            // Proses penyimpanan
            $user = $userModel->proccesStore($data);

            if ($user) {
                return $this->respondCreated([
                    'message' => 'User successfully created',
                    'data'    => $user  // Mengembalikan instance model yang baru disimpan
                ]);
            } else {
                return $this->fail('Failed to create user');
            }
            
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function fieldLength()
    {
        $model = new UserModel();
        return $this->respond($model->getFieldLengths());
    }

}
