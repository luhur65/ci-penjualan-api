<?php

namespace App\Validation;

class RoleCreateRequest
{
    public function rules(): array
    {
        return [
            'rolename' => 'required',
        ];
    }
}
