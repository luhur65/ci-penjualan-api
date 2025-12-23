<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ByPassLoginFilter implements FilterInterface
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
        // 1. Safety Check: Hanya jalan di environment non-production
        if (env('CI_ENVIRONMENT') === 'production') {
            return;
        }

        // Panggil service request untuk bedah URL
        $request = \Config\Services::request();

        // 2. Cek Header Rahasia
        $bypassHeader = $request->getHeaderLine('X-TEST-BYPASS');
        $validKey     = env('TEST_AUTH_BYPASS_KEY');

        if (!empty($validKey) && $bypassHeader === $validKey) {
            
            // 3. Buat User Palsu (Mocking)
            $mockUserData = [
                'id_user'  => 9999,
                'username' => 'bot_tester',
                // 'role'     => 'admin', // Role sakti
                'email'    => 'bot@testing.local'
            ];

            // 4. Inject ke Global Request agar bisa dibaca Controller
            // Menggunakan setGlobal sesuai kodingan Anda sebelumnya
            $request->setGlobal('jwtUser', $mockUserData);
            
            // 5. Beri tanda flags bahwa request ini sudah di-bypass
            $request->setGlobal('is_bypassed', true);

            return $request;
        }
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
