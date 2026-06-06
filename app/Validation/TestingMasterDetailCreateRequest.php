<?php

namespace App\Validation;

class TestingMasterDetailCreateRequest
{
    /**
     * Get the validation rules.
     *
     * @param array $data
     * @return array
     */
    public function rules($data = []): array
    {
        return [
            'no_bukti'    => 'required|max_length[100]',
            'tgl_bukti'   => 'required|valid_date[Y-m-d]',
            'pelanggan_id' => 'required|integer',
        ];
    }

    /**
     * Get the labels for the validation rules.
     *
     * @param array $data
     * @return array
     */
    public function labels(array $data = []): array
    {
        return [
            'no_bukti'    => 'No. Bukti',
            'tgl_bukti'   => 'Tanggal Bukti',
            'pelanggan_id' => 'Pelanggan',
        ];
    }
}
