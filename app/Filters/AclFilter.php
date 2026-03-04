<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AclFilter implements FilterInterface
{
    private $exceptions = [
        'class' => [], // Silahkan lengkapi listnya
        'method' => ['refresh', 'cekvalidasi', 'index', 'show', 'getmenuparent', 'getallclass']   // Silahkan lengkapi listnya
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        $router = service('router');
        $userData = $request->getServer('jwtUser');
        $userId = $userData['id'] ?? null;

        // Ambil nama class dan method dari router CI4
        // Format di CI4 biasanya: \App\Controllers\NamaController
        $controllerQualifiedName = $router->controllerName();
        $classArr = explode('\\', $controllerQualifiedName);
        $class = strtolower(end($classArr));
        $method = strtolower($router->methodName());

        // Hapus kata 'controller' jika ada di nama class
        $class = str_replace('controller', '', $class);

        if (
            $this->inException($class) ||
            $this->inExceptionMethod($method) ||
            $this->hasPermission($userId, $class, $method)
        ) {
            return; // Lanjut ke controller
        }

        // Jika tidak ada akses, lempar 403
        return service('response')->setStatusCode(403)->setJSON([
            // 'status'  => 403,
            'source' => $class . '@' . $method,
            'message' => 'Anda tidak memiliki akses'
        ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak perlu diisi
    }

    private function inException($class)
    {
        return in_array($class, $this->exceptions['class']);
    }

    private function inExceptionMethod($method)
    {
        return in_array($method, array_map('strtolower', $this->exceptions['method']));
    }

    private function hasPermission($userId, $class, $method)
    {
        if (!$userId) return false;

        $db = \Config\Database::connect();

        // 1) cek user-based ACL
        $userDirect = $db->table('useracl')
            ->select('1', false)
            ->join('acos', 'useracl.aco_id = acos.id')
            ->where('acos.class', $class)
            ->where('acos.method', $method)
            ->where('useracl.user_id', $userId)
            ->limit(1)
            ->get()
            ->getRowArray();

        if ($userDirect) return true;

        // 2) cek role-based ACL
        $userRole = $db->table('userroles')
            ->select('1', false)
            ->join('acl', 'userroles.role_id = acl.role_id')
            ->join('acos', 'acl.aco_id = acos.id')
            ->where('acos.class', $class)
            ->where('acos.method', $method)
            ->where('userroles.user_id', $userId)
            ->limit(1)
            ->get()
            ->getRowArray();

        return (bool) $userRole;
    }
}
