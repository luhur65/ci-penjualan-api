<?php

namespace App\Validation;

class ParameterUpdateRequest
{
    public function rules($id = null): array
    {
        return [
            'id'   => 'required',
            'grp'  => 'required',
            'text' => 'required'
        ];
    }
}
