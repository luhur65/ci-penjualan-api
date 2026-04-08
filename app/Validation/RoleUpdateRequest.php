<?php

namespace App\Validation;

class RoleUpdateRequest
{
    public function rules(): array
    {
        return [
            'id'       => 'required|is_natural_no_zero',
            'rolename' => 'required',
        ];
    }
}
