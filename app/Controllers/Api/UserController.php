<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\User as UserModel;

class UserController extends BaseController
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
            'data' => $users->getAll(),
            'attributes' => [
                'totalRows' => $users->totalRows,
                'totalPages' => $users->totalPages
            ]
        ]);
    }

    public function show($id = null)
    {
        $user = (new UserModel())->findOne($id);

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

            // Ambil data dari request dengan method getJSON
            $payload = $this->request->getJSON(true); // true = associative array

            // if (!$payload) {
            //     return $this->response->setJSON([
            //         'message' => 'Invalid JSON payload'
            //     ])->setStatusCode(400);
            // }

            $data = [
                'fullname' => $payload['fullname'] ?? null,
                'email'    => $payload['email'] ?? null,
                'username' => $payload['username'] ?? null,
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

    /**
     * @ClassName 
     * @Keterangan UPDATE DATA
     */
    public function update($id = null)
    {
        try {

            $payload = $this->request->getJSON(true); // true = associative array

            $data = [
                'id'       => $id,
                'fullname' => $payload['fullname'] ?? null,
                'email'    => $payload['email'] ?? null,
                'username' => $payload['username'] ?? null,
                'role_ids' => $payload['role_ids'] ?? [],
            ];

            $userModel = new UserModel();
            if (!$userModel->validate($data)) {
                return $this->respond([
                    'errors' => $userModel->errors()
                ], 422);
            }

            $user = $userModel->proccesUpdate($data);

            if ($user) {
                return $this->respondUpdated([
                    'message' => 'User successfully updated',
                    // 'data'    => $user
                ]);
            } else {
                return $this->fail('Failed to update user');
            }
            
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * @ClassName 
     * @Keterangan DELETE DATA
     */
    public function delete($id = null)
    {
        try {
            $userModel = new UserModel();
            $user = $userModel->find($id);

            if (!$user) {
                return $this->respond([
                    'message' => $this->failNotFound("User not found")->getReasonPhrase()
                ], 404);
            }

            $userModel->proccesDelete($id);

            return $this->respondDeleted([
                'message' => 'User successfully deleted',
                'data'    => $user
            ]);
        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
        }
    }

    public function fieldLength()
    {
        $model = new UserModel();
        return $this->respond($model->getFieldLengths());
    }

}
