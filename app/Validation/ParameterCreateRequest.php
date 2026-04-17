<?php

namespace App\Validation;

class ParameterCreateRequest
{
    /**
     * Get the validation rules.
     *
     * @param array $data
     * @return array
     */
    public function rules($data = []): array
    {
        $rules = [
            'grp'  => 'required',
            'subgrp' => 'required',
            'text' => 'required',
        ];

        if (isset($data['memo'])) {
            foreach ($data['memo'] as $key => $val) {
                $rules["memo.$key"] = 'required';
            }
        }

        return $rules;
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
            'id'     => 'ID',
            'grp'    => 'Group',
            'subgrp' => 'Subgroup',
            'text'   => 'Nama Parameter',
        ];

        if (!empty($data['memo'])) {
            foreach ($data['memo'] as $key => $val) {
                $labels["memo.{$key}"] = $key; // ← tampil "MEMO wajib diisi" bukan "memo.MEMO"
            }
        }

        return $labels;
    }
}
