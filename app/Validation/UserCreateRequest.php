<?php

namespace App\Validation;

class UserCreateRequest
{
    public function rules(): array
    {
        return [
            'fullname' => 'required|max_length[254]|min_length[3]|alpha_space',
            'email'    => 'required|max_length[254]|valid_email|is_unique[users.email]',
            'username' => 'required|max_length[30]|alpha_numeric_space|min_length[3]|is_unique[users.username]',
            'statusaktif' => 'permit_empty|is_natural_no_zero',
        ];
    }
}
