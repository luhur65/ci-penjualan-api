<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\ExcelMaker;
use CodeIgniter\API\ResponseTrait;
use App\Models\User as UserModel;
use App\Services\UserService;
use CodeIgniter\HTTP\IncomingRequest;

class UserController extends BaseController
{
    use ResponseTrait;

    protected $userModel;
    protected $userService;

    /** @var IncomingRequest $request */
    protected $request;

    public function __construct()
    {
        $this->userModel   = new UserModel();
        $this->userService = new UserService();
    }


    /**
     * @ClassName 
     * @Keterangan TAMPILKAN DATA
     */
    public function index()
    {
        $requestData = $this->request->getGet();
        return $this->respond($this->userService->getAllUsers($requestData));
    }

    public function show($id = null)
    {
        $user = $this->userService->getUserDetail($id);
        if (!$user) {
            return $this->failNotFound("User not found");
        }
        return $this->respond($user);
    }

    public function getUserRoles($id = null)
    {
        $requestData = $this->request->getGet();
        $user = $this->userService->getRoleByUserId($id, $requestData);
        return $this->respond($user);
    }

    public function getUserAcls($id = null)
    {
        $requestData = $this->request->getGet();
        $user = $this->userService->getAclByUserId($id, $requestData);
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
            $authUserName = $this->authUserName();
            $payload = $this->request->getJSON(true); // true = associative array

            $data = [
                'fullname' => $payload['fullname'] ?? null,
                'email'    => $payload['email'] ?? null,
                'username' => $payload['username'] ?? null,
                'modified_by' => $authUserName ?? null,
                'statusaktif' => $payload['statusaktif'] ?? null,
            ];

            $result = $this->validateWithLabels($data, new \App\Validation\UserCreateRequest());
            if ($result !== true) {
                return $this->respond(['errors' => $result], 422);
            }

            // Proses penyimpanan ke service
            $result = $this->userService->create($data, $payload);

            return $this->respondCreated([
                'message' => 'User successfully created',
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
                'id'       => $id,
                'fullname' => $payload['fullname'] ?? null,
                'email'    => $payload['email'] ?? null,
                'username' => $payload['username'] ?? null,
                'role_ids' => $payload['role_ids'] ?? [],
                'acls'     => $payload['acls'] ?? [],
                'modified_by' => $authUserName ?? null,
                'statusaktif' => $payload['statusaktif'] ?? null,
            ];

            $result = $this->validateWithLabels($data, new \App\Validation\UserUpdateRequest());
            if ($result !== true) {
                return $this->respond(['errors' => $result], 422);
            }

            $result = $this->userService->update($data, $payload);

            return $this->respondUpdated([
                'message' => 'User successfully updated',
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
        $user = $this->userModel->find($id);
        $payload = $this->request->getJSON(true);

        if (!$user) {
            return $this->failNotFound("User not found");
        }

        try {

            $result = $this->userService->delete($id, $payload);

            return $this->respondDeleted([
                'message' => 'User successfully deleted',
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
        $users = $this->userService->getAllUsers($this->request->getGet());
        $excelData = [];

        // (Opsional) Jika Anda mengirimkan 'offset' dari FE, nomor urut bisa disesuaikan
        $offset = $this->request->getGet('offset') ?? 0;

        foreach ($users['data'] as $index => $user) {

            $statusData = json_decode($user->statusaktif, true);
            $statusMemo = $statusData['MEMO'] ?? '';

            $excelData[] = [
                $offset + $index + 1,      // Nomor Urut yang akurat
                $user->fullname ?? '',
                $user->email ?? '',
                $user->username ?? '',
                $statusMemo,
                $user->modifiedby ?? '',
                $user->created_at ?? '',
                $user->updated_at ?? ''
            ];
        }

        // 4. Tentukan Judul Kolom
        $headers = ['NO', 'NAMA LENGKAP', 'EMAIL', 'USERNAME', 'STATUS AKTIF', 'MODIFIED BY', 'CREATED AT', 'UPDATED AT'];

        // dd($excelData);

        $excelService = new ExcelMaker();
        return $excelService->generate('Laporan_User_' . date('Ymd_His'), $headers, $excelData);
    }

    /**
     * @ClassName 
     * @Keterangan REPORT DATA
     */
    public function report()
    {
        return "Report Data User";
    }

    public function fieldLength()
    {
        return $this->respond($this->userModel->getFieldLengths());
    }
}
