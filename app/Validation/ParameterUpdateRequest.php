<?php

namespace App\Validation;

class ParameterUpdateRequest
{
    public function rules(): array
    {
        return [
            'id'   => 'required',
            'grp'  => 'required',
            'text' => 'required'
        ];
    }
}
