<?php

namespace App\Validation;

class RoleCreateRequest
{
    /**
     * Get the validation rules.
     *
     * @param array $data
     * @return array
     */
    public function rules($data = []): array
    {
        return [
            'rolename' => 'required',
        ];
    }

    /**
     * Get the labels for the validation rules.
     *
     * @param array $data
     * @return array
     */
    public function labels(array $data = []): array
    {
        $labels = [
            'rolename' => 'Nama Role',
        ];

        return $labels;
    }
}
