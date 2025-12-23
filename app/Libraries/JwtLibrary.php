<?php 

namespace App\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Config\JWT as JWTConfig;

class JwtLibrary
{
    protected $secret;
    protected $algo;
    protected $expire;

    public function __construct()
    {
        $config = new JWTConfig();
        $this->secret = $config->secret;
        $this->algo   = $config->algorithm;
        $this->expire = $config->expire;
    }

    public function generateToken($payload)
    {
        $issuedAt = time();
        $expireAt = $issuedAt + $this->expire;

        $token = [
            'iss'  => 'sistempenjualan-api', // Issuer (opsional, tapi bagus untuk standar)
            'iat'  => $issuedAt,
            'exp'  => $expireAt,
            'data' => $payload // Payload data user (id, role, dll) masuk sini
        ];

        return JWT::encode($token, $this->secret, $this->algo);
    }

    public function validateToken($token)
    {
        if (empty($token)) {
            return false;
        }

        try {
            // Decode token
            $decoded = JWT::decode($token, new Key($this->secret, $this->algo));
            
            // Kembalikan hanya bagian 'data' (user info)
            return $decoded->data; 

        } catch (\Exception $e) {
            // Jika expired atau signature salah, masuk sini
            // Anda bisa log error-nya jika perlu: log_message('error', $e->getMessage());
            return false;
        }
    }

    public function getExpireTime()
    {
        return $this->expire;
    }
}