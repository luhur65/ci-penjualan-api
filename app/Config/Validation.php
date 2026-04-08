<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var list<string>
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------

    public array $parameterCreate = [
        'grp'  => 'required',
        'text' => 'required'
    ];

    public array $parameterUpdate = [
        'id'   => 'required',
        'grp'  => 'required',
        'text' => 'required'
    ];

    public array $errorCreate = [
        'kodeerror'  => 'required',
        'keterangan' => 'required'
    ];

    public array $errorUpdate = [
        'id'         => 'required',
        'kodeerror'  => 'required',
        'keterangan' => 'required'
    ];

    public array $userCreate = [
        'fullname' => 'required|max_length[254]|min_length[3]|alpha_space',
        'email'    => 'required|max_length[254]|valid_email|is_unique[users.email]',
        'username' => 'required|max_length[30]|alpha_numeric_space|min_length[3]|is_unique[users.username]',
        'statusaktif' => 'permit_empty|is_natural_no_zero',
    ];



    public array $userUpdate = [
        'id'       => 'required|is_natural_no_zero',
        'fullname' => 'required|max_length[254]|min_length[3]|alpha_space',
        'email'    => 'required|max_length[254]|valid_email|is_unique[users.email,id,{id}]',
        'username' => 'required|max_length[30]|alpha_numeric_space|min_length[3]|is_unique[users.username,id,{id}]',
        'statusaktif' => 'permit_empty|is_natural_no_zero',
    ];



    public array $roleCreate = [
        'rolename' => 'required',
    ];



    public array $roleUpdate = [
        'id'       => 'required|is_natural_no_zero',
        'rolename' => 'required',
    ];



    public array $menuCreate = [
        'menuname'   => 'required|max_length[255]',
        'controller' => 'permit_empty|max_length[100]',
    ];



    public array $menuUpdate = [
        'id'         => 'required|is_natural_no_zero',
        'menuname'   => 'required|max_length[255]',
        'controller' => 'permit_empty|max_length[100]',
    ];


}
