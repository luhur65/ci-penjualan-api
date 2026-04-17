<?php

namespace App\Validation;

class UserCreateRequest
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
            'fullname' => 'required|max_length[254]|min_length[3]|alpha_space',
            'email'    => 'required|max_length[254]|valid_email|is_unique[users.email]',
            'username' => 'required|max_length[30]|alpha_numeric_space|min_length[3]|is_unique[users.username]',
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
