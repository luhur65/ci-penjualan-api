<?php

namespace App\Validation;

class MenuUpdateRequest
{
    public function rules(): array
    {
        return [
            'id'         => 'required|is_natural_no_zero',
            'menuname'   => 'required|max_length[255]',
            'controller' => 'permit_empty|max_length[100]',
        ];
    }
}
