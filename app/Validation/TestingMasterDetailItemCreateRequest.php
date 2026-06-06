<?php

namespace App\Validation;

class TestingMasterDetailItemCreateRequest
{
    public function rules($data = []): array
    {
        return [
            'penjualan_id' => 'required|max_length[36]',
            'nama_barang'  => 'required|max_length[255]',
            'qty'          => 'required|integer|greater_than[0]',
            'harga'        => 'required|decimal|greater_than[0]',
        ];
    }

    public function labels(array $data = []): array
    {
        return [
            'penjualan_id' => 'Penjualan',
            'nama_barang'  => 'Nama Barang',
            'qty'          => 'Qty',
            'harga'        => 'Harga',
        ];
    }
}
