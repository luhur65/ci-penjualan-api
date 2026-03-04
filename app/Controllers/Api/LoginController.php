<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\User;
use App\Libraries\JwtLibrary;
use App\Models\RefreshTokenModel;
use App\Models\PasswordReset;
use CodeIgniter\I18n\Time;
use App\Libraries\EmailSender;

class LoginController extends BaseController
{
    use ResponseTrait;

    public function index()
    {

        $userModel = new User();
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        if (!$username || !$password) {
            return $this->failValidationErrors('Username dan password wajib diisi');
        }

        // $user = $userModel->where('username', $username)->first();
        $user = $userModel->withRoles($username);

        if(!$user){
            return $this->failNotFound('User tidak ditemukan');
        }

        if ($user['password'] === "" || $user['password'] === null) {
            return $this->failUnauthorized('Akun belum diaktifkan');
        }

        if(!password_verify($password, $user['password'])){
            return $this->failUnauthorized('Password salah');
        }

        $payload = [
            'id'   => $user['id'],
            'role' => $user['roles'], // Penting untuk RBAC
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
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'fullname' => $user['fullname'],
                'email' => $user['email'],
                'roles' => $user['roles'],
            ],
            'refresh_token' => $refreshToken,
            'expires_in'   => $jwtLib->getExpireTime(), // sebaiknya 2 jam
            'menu'   => $user['menu'],
            'permissions' => $this->getPermissionUser($user['id'])
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

        $userWithRoles = $userModel->withRoles($user['username']);

        $payload = [
            'id'   => $userWithRoles['id'],
            'role' => $userWithRoles['roles'], // Penting untuk RBAC
            'name' => $userWithRoles['username'],
        ];

        $jwtLib = new JwtLibrary();
        $newAccessToken = $jwtLib->generateToken($payload);

        // ROTATE refresh token → buat baru
        $newRefreshToken = bin2hex(random_bytes(40));

        // $refreshTokenModel->where('user_id', $user['id'])->delete();
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

    // public function getPermissionUser($userId)
    // {
    //     $db = \Config\Database::connect();

    //     // Ambil semua hak akses user dari ACL dan UserACL
    //     $permissionsBuilder = $db->table('acos')->select('acos.class, acos.method')
    //         ->join('acl', 'acos.id = acl.aco_id', 'left')
    //         ->join('userroles', 'acl.role_id = userroles.role_id', 'left')
    //         ->join('useracl', 'acos.id = useracl.aco_id', 'left')
    //         ->where('userroles.user_id', $userId)
    //         ->orWhere('useracl.user_id', $userId)
    //         ->get()
    //         ->getResult();

    //     // Format menjadi array sederhana: ['menu.store', 'menu.update', 'menu.destroy']
    //     $userPermissions = [];
    //     foreach ($permissionsBuilder as $row) {
    //         $userPermissions[] = strtolower($row->class . '.' . $row->method);
    //     }

    //     return $userPermissions;

    //     // return $this->respond([
    //     //     'permissions' => $userPermissions
    //     // ]);
    // }

    public function getPermissionUser($userId)
    {
        $db = \Config\Database::connect();

        // Ambil semua hak akses user dari ACL dan UserACL (menggunakan JOIN untuk role dan useracl)
        $permissionsBuilder = $db->table('acos')
            ->select('acos.class, acos.method')
            ->join('acl', 'acos.id = acl.aco_id', 'left')
            ->join('userroles', 'acl.role_id = userroles.role_id', 'left')
            ->join('useracl', 'acos.id = useracl.aco_id', 'left')
            ->where('userroles.user_id', $userId)  // Hak akses yang terkait dengan role
            ->orWhere('useracl.user_id', $userId)  // Hak akses khusus user (bypass role)
            ->groupBy('acos.class, acos.method')  // Mengelompokkan berdasarkan aksi agar tidak duplikat
            ->get()
            ->getResult();

        // Format menjadi array sederhana: ['menu.store', 'menu.update', 'menu.destroy']
        $userPermissions = [];
        foreach ($permissionsBuilder as $row) {
            // Gabungkan class dan method, misalnya 'menu.store', 'menu.update'
            $userPermissions[] = strtolower($row->class . '.' . $row->method);
        }

        // Menghindari duplikat jika ada (set bisa digunakan untuk itu)
        $userPermissions = array_unique($userPermissions);

        return $userPermissions;
    }

    // POST /forgot-password (AJAX)
    public function forgotPassword()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Bad request']);
        }

        $rules = [
            'username' => 'required|min_length[3]|max_length[100]',
        ];

        if (! $this->validate($rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Validation error',
                'errors'  => $this->validator->getErrors(),
            ]);
        }

        $payload = $this->request->getJSON(true);
        $username = trim((string) $payload['username']);
        // $checkOnly = (bool) $payload['check']; // meniru checkValidation kamu

        $userModel = new User();

        // Sesuaikan pencarian user: by username/user_id
        $userRow = $userModel->where('username', $username)->first(); // contoh kolom: user
        // kalau kolomnya "userid" atau "username", ganti di sini.

        // Anti user-enumeration: response sama walaupun user ga ada
        if (!$userRow) {
            return $this->respond([
                'message' => 'Tidak ada user dengan username tersebut.',
            ], 500);
        }

        // kalau cuma check, stop di sini
        // if ($checkOnly) {
        //     return $this->response->setJSON(['message' => 'OK']);
        // }

        $email = $userRow['email'] ?? null;
        if (!$email) {
            // kalau user ada tapi email kosong, balikin error (ini beda dari anti-enumeration)
            return $this->respond([
                'message' => 'Email user tidak tersedia.',
                'errors'  => ['username' => 'Email untuk user ini belum terdaftar. Hubungi admin.'],
            ], 422);
        }

        // generate token, simpan HASH (bukan token asli)
        $rawToken  = bin2hex(random_bytes(32)); // 64 char
        $tokenHash = hash('sha256', $rawToken);

        $expiresAt = Time::now()->addMinutes(30)->toDateTimeString();

        $resetModel = new PasswordReset();
        // optional: hapus token lama
        $resetModel->where('username', $userRow['username'])->delete();

        $resetModel->insert([
            'username'   => $userRow['username'],
            'email'      => $email,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'created_at' => Time::now()->toDateTimeString(),
        ]);

        $resetLink = $this->generateLinkReset($rawToken, $userRow['username']);

        // kirim email
        $mailer = new EmailSender();
        $subject = 'Reset Password';
        // $message = "Klik link berikut untuk reset password (berlaku 30 menit):\n\n{$resetLink}";
        $mailer->sendEmail($email, $subject, $resetLink, $rawToken, $userRow['username']);

        return $this->respond([
            'message' => 'Link reset sudah dikirim ke email.',
        ]);
    }

    public function generateLinkReset($rawToken, $username)
    {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $domain = $_SERVER['HTTP_HOST'];  // Get the domain (e.g., localhost)
        $path = env('frontend.path') . '/reset-password';  // The path you're building the URL for

        $resetLink = $protocol . '://' . $domain . '/' . $path . '?token=' . $rawToken . '&user=' . urlencode($username);

        return $resetLink;
    }

    public function resetPassword()
    {
        $payload = $this->request->getJSON(true);

        $user     = trim((string) $payload['user']);
        $token    = trim((string) $payload['token']);
        $password = (string) $payload['password'];
        $passwordConfirm = (string) $payload['passwordConfirm'];

        if ($user === '' || $token === '' || strlen($password) < 8 ) {
            return $this->respond([
                'errors' => ['password' => 'Password minimal 8 karakter.']
            ], 422);
        }

        if ($password !== $passwordConfirm) {
            return $this->respond([
                'message' => ['password_confirmation' => 'Password konfirmasi tidak sesuai'],
            ], 422);
        }

        $resetModel = new PasswordReset();
        $row = $resetModel->where('username', $user)->first();

        if (!$row) {
            return $this->respond([
                'message' => 'Token tidak valid atau sudah dipakai.',
            ], 400);
        }

        if (Time::now()->isAfter(Time::parse($row['expires_at']))) {
            $resetModel->where('username', $user)->delete();
            return $this->respond([
                'message' => 'Token sudah kedaluwarsa.',
            ], 400);
        }

        $tokenHash = hash('sha256', $token);
        if (!hash_equals($row['token_hash'], $tokenHash)) {
            return $this->respond([
                'message' => 'Token tidak valid.',
            ], 400);
        }

        $userModel = new User();
        $u = $userModel->where('email', $row['email'])->first();
        if (!$u) {
            $resetModel->where('username', $user)->delete();
            return $this->respond([
                'message' => 'User tidak ditemukan.',
            ], 400);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        if (!$userModel->updatePasswordById((int)$u['id'], $hash)) {
            return $this->respond(['message' => 'Gagal mengubah password.'], 400);
        }

        $resetModel->where('username', $user)->delete();

        return $this->respond([
            'message' => 'Password berhasil direset.',
        ]);
    }
}
