<?php

use CodeIgniter\HTTP\IncomingRequest;

if (!function_exists('filter_sensitive_data')) {
    /**
     * Menghapus field sensitif dari array data sebelum di-log
     */
    function filter_sensitive_data($data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $sensitiveFields = ['password', 'token', 'secret', 'password_hash', 'api_key'];

        foreach ($data as $key => &$value) {
            if (in_array($key, $sensitiveFields, true)) {
                unset($data[$key]);
            } elseif (is_array($value)) {
                $value = filter_sensitive_data($value);
            }
        }

        return $data;
    }
}

if (!function_exists('audit_log')) {
    /**
     * Mencatat aktivitas CRUD ke tabel audit_logs
     */
    function audit_log(string $module, string $action, $recordId = null, $oldData = null, $newData = null)
    {

        // Coba dapatkan request global
        $request = \Config\Services::request();

        $ipAddress = 'CLI';
        $userAgent = 'CLI';
        $userId = null;

        if ($request instanceof \CodeIgniter\HTTP\IncomingRequest) {
            $ipAddress = $request->getIPAddress();
            $userAgent = $request->getUserAgent() ? $request->getUserAgent()->getAgentString() : 'Unknown';

            // Coba dapatkan JWT user (jika pakai API) atau session (jika web)
            if ($request->getServer('jwtUser')) {
                $userId = $request->getServer('jwtUser')['id'] ?? null;
            } elseif (session()->has('id')) {
                $userId = session('id');
            }
        }

        // Filter data sensitif sebelum di-encode
        $oldDataFiltered = $oldData ? filter_sensitive_data($oldData) : null;
        $newDataFiltered = $newData ? filter_sensitive_data($newData) : null;

        $logData = [
            'module'     => strtoupper($module),
            'action'     => strtoupper($action),
            'record_id'  => $recordId,
            'old_data'   => $oldDataFiltered ? json_encode($oldDataFiltered) : null,
            'new_data'   => $newDataFiltered ? json_encode($newDataFiltered) : null,
            'user_id'    => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Insert menggunakan model AuditLog
        $auditLogModel = new \App\Models\AuditLog();
        $auditLogModel->insert($logData);
    }
}
