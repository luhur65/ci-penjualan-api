<?php

namespace App\Validation;

class TestingMasterDetailItemUpdateRequest
{
    public function rules($data = []): array
    {
        return [
            'nama_barang' => 'required|max_length[255]',
            'qty'         => 'required|integer|greater_than[0]',
            'harga'       => 'required|decimal|greater_than[0]',
        ];
    }

    public function labels(array $data = []): array
    {
        return [
            'nama_barang' => 'Nama Barang',
            'qty'         => 'Qty',
            'harga'       => 'Harga',
        ];
    }
}
