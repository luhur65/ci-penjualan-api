<?php

namespace App\Models;

class Pelanggan extends CustomModel
{
    protected $table            = 'tbl_pelanggan';
    protected $primaryKey       = 'id';
    protected $sortDirection    = 'ASC';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nama_pelanggan'
    ];

    protected $fieldMap = [
        'id'             => 'tbl_pelanggan.id',
        'nama_pelanggan' => 'tbl_pelanggan.nama_pelanggan'
    ];

    protected $searchableFields = [
        'nama_pelanggan'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll()
    {
        $query = $this->builder();
        $query->select([
            'tbl_pelanggan.id',
            'tbl_pelanggan.nama_pelanggan'
        ]);

        return $this->datatable($query);
    }
}
