<?php

namespace App\Validation;

class MenuCreateRequest
{
    public function rules(): array
    {
        return [
            'menuname'   => 'required|max_length[255]',
            'controller' => 'permit_empty|max_length[100]',
        ];
    }
}
