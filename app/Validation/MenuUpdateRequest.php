<?php

namespace App\Validation;

class MenuUpdateRequest
{
    /**
     * Get the validation rules.
     *
     * @param array $data
     * @return array
     */
    public function rules($data = []): array
    {
        $idCondition = $data['id'] ? ",id,{$data['id']}" : "";
        return [
            'id'         => 'required|is_natural_no_zero',
            'menuname'   => 'required|max_length[255]',
            'controller' => 'permit_empty|max_length[100]',
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
            'menuname'   => 'Nama Menu',
            'controller' => 'Controller',
        ];

        return $labels;
    }
}
