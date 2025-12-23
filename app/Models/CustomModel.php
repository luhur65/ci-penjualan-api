<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Database;

class CustomModel extends Model
{
    protected $params = [];
    protected $totalPages = 0;
    protected $totalRows = 0;
    protected $lengthCache = [];

    /**
     * Request Parameters untuk JQGrid
     */
    public function setRequestParameters()
    {
        $request = service('request');

        $page   = $request->getGetPost('page') ?? 1;
        $limit  = $request->getGetPost('limit') ?? 10;

        $this->params = [
            'offset'     => $request->getGetPost('offset') ?? (($page ? $page - 1 : 0) * $limit),
            'limit'      => $limit,
            'filters'    => json_decode($request->getGetPost('filters') ?? '[]', true),
            'sidx'  => $request->getGetPost('sidx') ?? 'id',
            'sord'  => $request->getGetPost('sord') ?? 'asc',
        ];

        return $this;
    }


    /**
     * Ambil panjang field berdasarkan table model ini
     */
    public function getFieldLengths(): array
    {
        if (isset($this->lengthCache[$this->table])) {
            return $this->lengthCache[$this->table];
        }

        $db      = Database::connect();
        $table   = $this->table;
        $query   = $db->query("SHOW COLUMNS FROM `{$table}`");
        $columns = $query->getResult();

        $lengths = [];

        foreach ($columns as $col) {
            preg_match('/\((.*?)\)/', $col->Type, $matches);
            $lengths[$col->Field] = isset($matches[1]) ? (int)$matches[1] : null;
        }

        return $this->lengthCache[$this->table] = $lengths;
    }
}
