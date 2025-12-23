<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\User;
use \Firebase\JWT\JWT;
use App\Libraries\JwtLibrary;
use App\Models\RefreshTokenModel;

class LoginController extends BaseController
{
    use ResponseTrait;

    public function index()
    {

        $model = new User();
        $username = $this->request->getVar('username');
        $password = $this->request->getVar('password');

        $user = $model->where('username', $username)->first();

        // if(!$user){
        //     return $this->failNotFound('User tidak ditemukan');
        // }

        // if(!password_verify($password, $user['password'])){
        //     return $this->fail('password salah', 401);
        // }

        $payload = [
            'id'   => $user['id'],
            // 'role' => $user['role'], // Penting untuk RBAC
            'name' => $user['username'],
        ];

        // Panggil Library
        $jwtLib = new JwtLibrary();
        $token  = $jwtLib->generateToken($payload);
        
        // 3. Generate Refresh Token (Random String)
        $refreshToken = bin2hex(random_bytes(32));
        
        // 4. Simpan Refresh Token ke DB (MariaDB)
        $refreshTokenModel = new RefreshTokenModel();
        // Hapus/matra invalidasi refresh token lama untuk user ini agar refresh token lama tidak lagi bisa dipakai
        $refreshTokenModel->where('user_id', $user['id'])->delete();
        $refreshTokenModel->save([
            'token_hash' => hash('sha256', $refreshToken),
            'user_id'    => $user['id'],
            'expires_at' => date('Y-m-d H:i:s', strtotime('+120 minutes'))
        ]);
        
        return $this->respond([
            // 'status' => 200,
            'access_token'  => $token,
            'refresh_token' => $refreshToken,
            'expires_in'   => $jwtLib->getExpireTime(), // sebaiknya 2 jam
            // 'role'   => $user['role']
        ]);

    }

    // Endpoint untuk Refresh Token (mirip Passport)
    public function refreshToken()
    {
        $incomingRefreshToken = $this->request->getVar('refresh_token');
        
        if (!$incomingRefreshToken) {
            return $this->fail('Refresh token diperlukan', 400);
        }

        $refreshTokenModel = new RefreshTokenModel();
        $tokenHash = hash('sha256', $incomingRefreshToken);
        
        $storedToken = $refreshTokenModel->where('token_hash', $tokenHash)->first();
        
        if (!$storedToken) {
            return $this->fail('Refresh token tidak valid', 401);
        }

        if (strtotime($storedToken['expires_at']) < time()) {
            return $this->fail('Refresh token sudah expired', 401);
        }

        $userModel = new User();
        $user = $userModel->find($storedToken['user_id']);
        
        if (!$user) {
            return $this->fail('User tidak ditemukan', 404);
        }

        $payload = [
            'id'   => $user['id'],
            'name' => $user['username']
        ];

        $jwtLib = new JwtLibrary();
        $newAccessToken = $jwtLib->generateToken($payload);

        // ROTATE refresh token → buat baru
        $newRefreshToken = bin2hex(random_bytes(40));

        $refreshTokenModel->where('user_id', $user['id'])->delete();
        $refreshTokenModel->save([
            'token_hash' => hash('sha256', $newRefreshToken),
            'user_id'    => $user['id'],
            'expires_at' => date('Y-m-d H:i:s', strtotime('+2 days')),
        ]);

        return $this->respond([
            'access_token'  => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'expires_in'    => 3600
        ]);
    }
}
