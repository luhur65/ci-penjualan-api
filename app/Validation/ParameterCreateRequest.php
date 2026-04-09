<?php

namespace App\Validation;

class ParameterCreateRequest
{
    public function rules(): array
    {
        return [
            'grp'  => 'required',
            'text' => 'required'
        ];
    }
}
