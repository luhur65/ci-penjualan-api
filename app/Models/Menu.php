<?php

namespace App\Models;

use App\Models\CustomModel;
use App\Libraries\ControllerInspector;
use App\Models\Acos;

class Menu extends CustomModel
{
    protected $table            = 'menus';
    protected $primaryKey       = 'id';
    protected $sortDirection    = 'ASC';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'menukode',
        'menuname',
        'menu_seq',
        'menu_parent',
        'menu_icon',
        'link',
        'aco_id',
        'modified_by',
    ];

    protected $fieldMap = [
        'menuname' => 'menus.menuname',
        'menukode' => 'menus.menukode',
        'menu_seq' => 'menus.menu_seq',
        'menu_parent' => 'menus.menu_parent',
        'parent_name' => 'COALESCE(m2.menuname, "ROOT")',
        'menu_icon' => 'menus.menu_icon',
        'link' => 'menus.link',
        'aco_id' => 'menus.aco_id',
        'updated_at' => 'menus.updated_at',
        'created_at' => 'menus.created_at',
        'modified_by' => 'menus.modified_by'
    ];

    protected $searchableFields = [
        'menuname',
        'menukode',
        'menu_seq',
        'menu_parent',
        'parent_name',
        'menu_icon',
        'link',
        'aco_id',
        'updated_at',
        'created_at',
        'modified_by'
    ];

    protected bool $allowEmptyInserts = true;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'menuname'    => 'required|max_length[255]',
        // 'menu_seq'    => 'required|is_natural',
        // 'menu_parent' => 'permit_empty|is_natural',
        // 'menu_icon'   => 'required|max_length[50]',
        // 'link'        => 'required|max_length[50]',
        'controller'  => 'permit_empty|max_length[100]',
    ];
    protected $validationMessages = [
        'menuname' => [
            'required' => 'Nama menu wajib diisi.'
        ],
        'controller' => [
            'required' => 'Controller wajib diisi.'
        ],
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    public function getAll()
    {
        $query = $this->builder();

        $query->select([
            'menus.id',
            'menus.menuname',
            'menus.menu_seq',
            'menus.menu_parent',
            'COALESCE(m2.menuname, "ROOT") as parent_name',
            'menus.menu_icon',
            'menus.aco_id',
            'menus.link',
            'menus.menukode',
            'menus.modified_by as modifiedby',
            'menus.updated_at',
            'menus.created_at',
        ]);

        $query->join($this->table . ' m2', 'm2.id = menus.menu_parent', 'left');

        return $this->datatable($query);
    }

    public function findOne($id = null)
    {
        $menu = $this->builder()
            ->select([
                'menus.id',
                'menus.menuname',
                'menus.menu_seq',
                'menus.menu_parent',
                'p.menuname as parent_name',
                'menus.menu_icon',
                'menus.aco_id',
                'menus.link',
                'menus.menukode',
                'menus.updated_at',
                'menus.created_at',
                'acos.class as controller',
                'menus.modified_by',
                // 'acos.method as controller_method',
                // 'acos.nama as controller_name'
            ])
            ->join('acos', 'acos.id = menus.aco_id', 'left')
            ->join('menus p', 'p.id = menus.menu_parent', 'left')
            ->where('menus.id', $id)
            ->get()
            ->getRow();

        // if ($menu) {
        //     $menu->controller = $menu->controller_class . '/' . $menu->controller_method;
        // }

        return [
            'data' => $menu
        ];
    }

    // public function getMenuParent()
    // {
    //     $builder = $this->builder();
    //     $builder->select('menus.id as id, upper(menus.menuname) as menu');
    //     $builder->where('menus.aco_id', 0);
    //     return $builder->get()->getResult();
    // }

    // // public function getController() {
    // //     $inspector = new ControllerInspector();
    // //     $classes = $inspector->scanAllControllers();
    
    // //     return $classes;
    // // }

    // public function getController()
    // {
    //     $inspector = new ControllerInspector();

    //     // Ambil daftar controller yang sudah dipakai
    //     // controller ada di table acos
    //     $usedControllers = $this->builder()
    //         ->select('acos.class as controller')
    //         ->join('acos', 'acos.id = menus.aco_id', 'left')
    //         ->where('acos.class IS NOT NULL')
    //         ->where('acos.class !=', '')
    //         ->get()
    //         ->getResultArray();

    //     // Ubah jadi array 1 dimensi (flat array)
    //     $usedControllers = array_column($usedControllers, 'controller');

    //     // Scan semua controller, kecuali yang sudah dipakai
    //     return $inspector->scanAllControllers($usedControllers);
    // }

    // public function sort($query) 
    // {
    //     $query->orderBy($this->params['sidx'], $this->params['sord']);
    // }

    // public function filter(&$query)
    // {
    //     $filters = $this->params['filters'] ?? [];

    //     if (
    //         empty($filters) ||
    //         empty($filters['rules']) ||
    //         $filters['rules'][0]['data'] === ''
    //     ) {
    //         return $query;
    //     }

    //     $groupOp = strtoupper($filters['groupOp']);

    //     foreach ($filters['rules'] as $rule) {

    //         $field = $rule['field'];
    //         $value = trim($rule['data']);
    //         $isDate = in_array($field, ['created_at', 'updated_at']);

    //         // untuk field text
    //         $likeText = "%{$value}%";

    //         // untuk field DATE_FORMAT
    //         $likeDate = "'%{$value}%'";  // WAJIB STRING LITERAL

    //         $dateExpr = "DATE_FORMAT({$this->table}.{$field}, '%d-%m-%Y %H:%i:%s')";

    //         if ($groupOp === 'AND') {

    //             if ($isDate) {
    //                 // LIKE untuk date
    //                 $query->where("$dateExpr LIKE $likeDate", null, false);
    //             } else {
    //                 // LIKE normal CI4
    //                 $query->like("{$this->table}.{$field}", $value);
    //             }

    //         } else { // OR

    //             if ($isDate) {
    //                 $query->orWhere("$dateExpr LIKE $likeDate", null, false);
    //             } else {
    //                 $query->orLike("{$this->table}.{$field}", $value);
    //             }
    //         }
    //     }

    //     $this->totalRows = $query->countAllResults(false);
    //     $limit = $this->params['limit'] ?? 10;
    //     $this->totalPages = ceil($this->totalRows / $limit);

    //     return $query;
    // }

    // public function pagination($query) 
    // {
    //     $query->limit($this->params['limit'], $this->params['offset']);
    // }



    
}
