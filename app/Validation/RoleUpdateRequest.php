<?php

namespace App\Validation;

class RoleUpdateRequest
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
            'id'       => 'required|is_natural_no_zero',
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
