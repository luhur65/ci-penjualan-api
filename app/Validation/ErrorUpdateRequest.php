<?php

namespace App\Validation;

class ErrorUpdateRequest
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
            'id'         => 'required',
            'kodeerror'  => 'required',
            'keterangan' => 'required'
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
        $labels = [
            'kodeerror'  => 'Kode Error',
            'keterangan' => 'Keterangan',
        ];

        return $labels;
    }
}
