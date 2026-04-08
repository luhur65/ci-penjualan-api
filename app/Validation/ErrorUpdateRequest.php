<?php

namespace App\Validation;

class ErrorUpdateRequest
{
    public function rules(): array
    {
        return [
            'id'         => 'required',
            'kodeerror'  => 'required',
            'keterangan' => 'required'
        ];
    }
}
