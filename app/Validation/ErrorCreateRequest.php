<?php

namespace App\Validation;

class ErrorCreateRequest
{
    public function rules(): array
    {
        return [
            'kodeerror'  => 'required',
            'keterangan' => 'required'
        ];
    }
}
