<?php

namespace App\Models;

class Alatbayar extends CustomModel
{
    // Override group koneksi ke SQL Server
    protected $DBGroup          = 'sqlsrv';
    
    protected $table            = 'valatbayar'; // Menggunakan View dari SQL Server
    protected $primaryKey       = 'id';        // Sesuaikan Primary Key-nya
    protected $sortDirection    = 'ASC';       // Wajib untuk CustomModel
    protected $useAutoIncrement = true;        
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'nama',
        'keterangan',
        'statuslangsungcair',
        'statusdefault',
        'statusbank',
        'statusaktif',
        'info',
        'modifiedby'
    ];

    // Sesuaikan dengan kolom riil di SQL Server
    protected $searchableFields = [
        'id',
        'nama',
        'keterangan',
        'modifiedby'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll()
    {
        $query = $this->db->table('valatbayar');

        return $this->datatable($query);
    }
}
