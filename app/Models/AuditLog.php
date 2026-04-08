<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLog extends Model
{
    protected $table            = 'audit_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'module',
        'action',
        'record_id',
        'old_data',
        'new_data',
        'user_id',
        'ip_address',
        'user_agent',
        'created_at'
    ];

    protected $useTimestamps = false;
}
