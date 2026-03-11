<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\User as UserModel;
use App\Services\UserService;

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
     * Creates a new User.
     *
     * @ClassName 
     * @Keterangan TAMBAH DATA
     */
    public function create()
    {
        try {
            $payload = $this->request->getJSON(true); // true = associative array

            $data = [
                'fullname' => $payload['fullname'] ?? null,
                'email'    => $payload['email'] ?? null,
                'username' => $payload['username'] ?? null,
            ];

            // Validasi manual menggunakan validate()
            $userModel = new UserModel();
            if (!$userModel->validate($data)) {
                return $this->respond([
                    'errors' => $userModel->errors()  // Menyertakan pesan error validasi
                ], 422);  // Status code 422 for unprocessable entity
            }

            // Proses penyimpanan ke service
            $userService = new UserService();
            $userService->create($data);

            return $this->respondCreated([
                'message' => 'User successfully created'
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

            $userService = new UserService();
            $userService->update($data);

            return $this->respondUpdated([
                'message' => 'User successfully updated'
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
        try {
            $userModel = new UserModel();
            $user = $userModel->find($id);

            if (!$user) {
                return $this->respond([
                    'message' => $this->failNotFound("User not found")->getReasonPhrase()
                ], 404);
            }

            $userService = new UserService();
            $userService->delete($id);

            return $this->respondDeleted([
                'message' => 'User successfully deleted',
                'data'    => $user
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
        $userModel = new UserModel();
        $users = $userModel->getAll();
        return $this->respond($users);
    }

    public function fieldLength()
    {
        $model = new UserModel();
        return $this->respond($model->getFieldLengths());
    }

}
