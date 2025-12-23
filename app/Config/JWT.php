<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * --------------------------------------------------------------------------
 * JWT Configuration
 * --------------------------------------------------------------------------
 *
 * This file is used to configure the settings for JSON Web Tokens (JWT)
 * used for authentication in the application.
 *
 * @see https://jwt.io/introduction/ for more information on JWT.
 */

class JWT extends BaseConfig
{
    // Rahasia dapur, jangan sampai tetangga tau
    public $secret; 
    
    // Algoritma enkripsi
    public $algorithm;
    
    // Masa aktif token dalam detik (contoh: 1 jam)
    // debug: 1 menit = 60 detik
    public $expire;

    public function __construct()
    {
        $this->secret    = env('JWT_SECRET_KEY', 'default_secret');
        $this->algorithm = env('JWT_ALGO', 'HS256');
        $this->expire    = (int) env('JWT_EXPIRE', 3600);
    }
}
