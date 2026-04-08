<?php

namespace App\Validation;

class ErrorUpdateRequest
{
    public function rules($id = null): array
    {
        return [
            'id'         => 'required',
            'kodeerror'  => 'required',
            'keterangan' => 'required'
        ];
    }
}
