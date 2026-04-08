<?php

namespace App\Validation;

class UserUpdateRequest
{
    public function rules(): array
    {
        return [
            'id'       => 'required|is_natural_no_zero',
            'fullname' => 'required|max_length[254]|min_length[3]|alpha_space',
            'email'    => 'required|max_length[254]|valid_email|is_unique[users.email,id,{id}]',
            'username' => 'required|max_length[30]|alpha_numeric_space|min_length[3]|is_unique[users.username,id,{id}]',
            'statusaktif' => 'permit_empty|is_natural_no_zero',
        ];
    }
}
