<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\JwtLibrary;
use Config\Services;

class JwtFilter implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {

        // Panggil service request untuk bedah URL
        // $request = \Config\Services::request();

       // 1. Ambil Scheme dari objek URI (http/https)
        // Pastikan $request diperlakukan sebagai IncomingRequest agar method getUri() terbaca
        $scheme = $request->getUri()->getScheme(); 

        // 2. Ambil Authority (Domain + Port)
        // Kita gunakan getAuthority() dari URI juga biar konsisten, 
        // atau tetap pakai getServer('HTTP_HOST') juga boleh.
        $authority = $request->getUri()->getAuthority();

        // 3. Ambil path folder dari .env
        $frontendPath = getenv('frontend.path');

        // Rakit ulang: {http}://{domain}/{folder}
        // sprintf adalah cara elegan menggabungkan string biar rapi
        $dynamicUrl = sprintf('%s://%s/%s', $scheme, $authority, $frontendPath);

        // 1. Ambil Header Authorization
        $header = $request->getServer('HTTP_AUTHORIZATION');
        if (!$header) {
            // Cek apakah klien meminta JSON (biasanya API call)
            // atau jika request datang dari AJAX
            if ($request->hasHeader('Accept') && strpos($request->getHeaderLine('Accept'), 'application/json') !== false) {
                return Services::response()
                    ->setJSON(['msg' => 'Token tidak ditemukan'])
                    ->setStatusCode(401);
            }

            // Jika akses langsung via browser bar, lempar ke FE
            return redirect()->to($dynamicUrl);
        }

        // 2. Pecah string "Bearer <token>"
        // Kadang user lupa pakai spasi atau format salah, kita handle sedikit
        $token = null;
        if (!preg_match('/Bearer\s+(\S+)/', $header, $matches)) {
            return Services::response()
                ->setJSON(['message' => 'Format Authorization salah'])
                ->setStatusCode(401);
        }

        $token = $matches[1];

        // 3. Validasi menggunakan Library JwtLibrary
        $jwt = new JwtLibrary();
        $userData = $jwt->validateToken($token);

        if (!$userData) {
            return Services::response()
                            ->setJSON(['message' => 'Token Invalid atau Expired'])
                            ->setStatusCode(401);
        }

        // 4. (Opsional RBAC) Cek Role dari Argumen Filter
        // $arguments didapat dari Routes: ['filter' => 'auth:admin']
        if ($arguments) {
            // $userData adalah object, karena hasil decode JWT defaultnya object
            if (!in_array($userData->role, $arguments)) {
                 return Services::response()
                                ->setJSON(['message' => 'Akses Ditolak (Role tidak sesuai)'])
                                ->setStatusCode(403);
            }
        }

        // Simpan user ke global request (aman dan direkomendasikan)
        $request->setGlobal('jwtUser', (array)$userData);

        return $request;
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
