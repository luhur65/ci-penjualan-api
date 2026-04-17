<?php

namespace App\Validation;

class UserUpdateRequest
{
    /**
     * Get the validation rules.
     *
     * @param array $data
     * @return array
     */
    public function rules($data = []): array
    {
        $idCondition = $data['id'] ? ",id,{$data['id']}" : "";
        return [
            'id'       => 'required|is_natural_no_zero',
            'fullname' => 'required|max_length[254]|min_length[3]|alpha_space',
            'email'    => "required|max_length[254]|valid_email|is_unique[users.email{$idCondition}]",
            'username' => "required|max_length[30]|alpha_numeric_space|min_length[3]|is_unique[users.username{$idCondition}]",
            'statusaktif' => 'permit_empty|is_natural_no_zero',
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
            'fullname' => 'Nama Lengkap',
            'email'    => 'Email',
            'username' => 'Username',
            'statusaktif' => 'Status Aktif',
        ];

        return $labels;
    }
}
