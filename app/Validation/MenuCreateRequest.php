<?php

namespace App\Validation;

class MenuCreateRequest
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
