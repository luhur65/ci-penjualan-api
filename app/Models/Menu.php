<?php

namespace App\Models;

use App\Models\CustomModel;
use App\Libraries\ControllerInspector;
use App\Models\Acos;

class Menu extends CustomModel
{
    protected $table            = 'menus';
    protected $primaryKey       = 'id';
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

    public function get()
    {
        // $builder = $this->db->table($this->table);
        // $query = $builder->get();
        // return $query->getResultArray();

        $this->setRequestParameters();

        // Base query
        $query = $this->builder();
        $query->select('id, menuname, menu_seq, menu_parent, menu_icon, aco_id, link, menukode, updated_at, created_at');

        $this->filter($query);
        $this->sort($query);
        $this->pagination($query);

        $data = $query->get()->getResult();

        $this->totalRows = $query->countAllResults(false);
        $this->totalPages = ($this->totalRows > 0) ? ceil($this->totalRows / $this->params['limit']) : 1;

        return $data;
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

    public function getMenuParent()
    {
        $builder = $this->builder();
        $builder->select('menus.id as id, upper(menus.menuname) as menu');
        $builder->where('menus.aco_id', 0);
        return $builder->get()->getResult();
    }

    // public function getController() {
    //     $inspector = new ControllerInspector();
    //     $classes = $inspector->scanAllControllers();
    
    //     return $classes;
    // }

    public function getController()
    {
        $inspector = new ControllerInspector();

        // Ambil daftar controller yang sudah dipakai
        // controller ada di table acos
        $usedControllers = $this->builder() 
            ->select('acos.class as controller')
            ->join('acos', 'acos.id = menus.aco_id', 'left')
            ->where('acos.class IS NOT NULL')
            ->where('acos.class !=', '')
            ->get()
            ->getResultArray();

        // Ubah jadi array 1 dimensi (flat array)
        $usedControllers = array_column($usedControllers, 'controller');

        // Scan semua controller, kecuali yang sudah dipakai
        return $inspector->scanAllControllers($usedControllers);
    }

    public function sort($query) 
    {
        $query->orderBy($this->params['sidx'], $this->params['sord']);
    }

    public function filter(&$query)
    {
        $filters = $this->params['filters'] ?? [];

        if (
            empty($filters) ||
            empty($filters['rules']) ||
            $filters['rules'][0]['data'] === ''
        ) {
            return $query;
        }

        $groupOp = strtoupper($filters['groupOp']);

        foreach ($filters['rules'] as $rule) {

            $field = $rule['field'];
            $value = trim($rule['data']);
            $isDate = in_array($field, ['created_at', 'updated_at']);

            // untuk field text
            $likeText = "%{$value}%";

            // untuk field DATE_FORMAT
            $likeDate = "'%{$value}%'";  // WAJIB STRING LITERAL

            $dateExpr = "DATE_FORMAT({$this->table}.{$field}, '%d-%m-%Y %H:%i:%s')";

            if ($groupOp === 'AND') {

                if ($isDate) {
                    // LIKE untuk date
                    $query->where("$dateExpr LIKE $likeDate", null, false);
                } else {
                    // LIKE normal CI4
                    $query->like("{$this->table}.{$field}", $value);
                }

            } else { // OR

                if ($isDate) {
                    $query->orWhere("$dateExpr LIKE $likeDate", null, false);
                } else {
                    $query->orLike("{$this->table}.{$field}", $value);
                }
            }
        }

        $this->totalRows = $query->countAllResults(false);
        $limit = $this->params['limit'] ?? 10;
        $this->totalPages = ceil($this->totalRows / $limit);

        return $query;
    }

    public function pagination($query) 
    {
        $query->limit($this->params['limit'], $this->params['offset']);
    }

    public function processStore(array $data)
    {
        $acosModel = new Acos();
        $inspector = new ControllerInspector();
        $controllerData = $inspector->scanController($data['controller']);
        
        $menuAcoId = 0;
        $modifiedBy = session()->get('id') ?? 0;

        if (!empty($controllerData)) {
            foreach ($controllerData as $item) {
                $className = str_replace('controller', '', strtolower($item['class']));
                $classHeader = $className;

                // Simpan ACO untuk method utama
                $acosModel->processStore([
                    'class' => $className,
                    'method' => $item['method'],
                    'nama' => $item['name'],
                    'keterangan' => $item['keterangan'],
                    'idheader' => 0,
                ]);

                // Simpan ACO untuk detail jika ada
                if (!empty($item['detail'])) {
                    foreach ($item['detail'] as $detailClass) {
                        if ($detailClass === '') continue;

                        $detailData = $inspector->scanController($detailClass);

                        foreach ($detailData as $detailItem) {
                            $detailClassName = str_replace('controller', '', strtolower($detailItem['class']));

                            $idHeader = $acosModel->select('id')
                                ->where('class', $classHeader)
                                ->where('method', 'index')
                                ->first()['id'] ?? 0;

                            $acosModel->processStore([
                                'class' => $detailClassName,
                                'method' => $detailItem['method'],
                                'nama' => $detailItem['name'],
                                'idheader' => $idHeader,
                                'keterangan' => $item['keterangan'],
                            ]);
                        }
                    }
                }

                // Ambil ACO id utama
                $menuAcoId = $acosModel->select('id')
                    ->where('class', $className)
                    ->where('method', 'index')
                    ->orderBy('id', 'asc')
                    ->first()['id'] ?? 0;
            }
        }

        try {
            $saveData = [
                'menuname'   => ucwords($data['menuname']),
                'menu_seq'    => (int) $data['menu_seq'],
                'menu_parent' => (int) $data['menu_parent'] ?? 0,
                'menu_icon'   => $data['menu_icon'],
                'link'       => '',
                'aco_id'     => (int) $menuAcoId,
                'menukode'   => $this->generateMenuKode($data['menu_parent'], $data['menuname']),
                'controller' => $data['controller'],
            ];

            if (!$this->insert($saveData)) {
                throw new \Exception("Error storing menu." . json_encode($this->errors()));
                // return $menu->errors();
            }

            return $this;

        } catch (\Exception $e) {
            log_message('error', $e->getMessage());
            throw $e;
        }
    }

    public function processUpdate(array $data)
    {
        $acosModel = new Acos();
        $inspector = new ControllerInspector();

        $menu = $this->find($data['id']);
        if (!$menu) {
            throw new \Exception("Menu dengan ID {$data['id']} tidak ditemukan.");
        }

        $menuAcoId = $menu['aco_id'];
        $modifiedBy = session()->get('id') ?? 0;

        // Cek controller
        $acos = $acosModel->where('id', $menu['aco_id'])->first();

        // Jika controller tidak diisi, ambil dari acos
        $controller = (empty($data['controller']) ? $acos['class'] : $data['controller']);

        if (!empty($controller)) {
            $controllerData = $inspector->scanController($controller);

            if (!empty($controllerData)) {
                foreach ($controllerData as $item) {
                    $className = str_replace('controller', '', strtolower($item['class']));
                    $classHeader = $className;

                    // Cek ada gak method ini di acos
                    $aco = $acosModel
                        ->where('class', $className)
                        ->where('method', $item['method'])
                        ->first();

                    if ($aco) {
                        // Update keterangan dan modifiedby jika sudah ada
                        $acosModel->processUpdate([
                            'id' => $aco['id'],
                            'class' => $className,
                            'method' => $item['method'],
                            'nama' => $item['name'],
                            'keterangan' => $item['keterangan'],
                            // 'modifiedby' => $modifiedBy,
                        ]);
                    } else {
                        // Insert baru method baru yang belum ada
                        $acosModel->processStore([
                            'class' => $className,
                            'method' => $item['method'],
                            'nama' => $item['name'],
                            'keterangan' => $item['keterangan'],
                            'idheader' => 0,
                            // 'modifiedby' => $modifiedBy,
                        ]);
                    }

                    // Proses detail controller jika ada
                    if (!empty($item['detail'])) {
                        foreach ($item['detail'] as $detailClass) {
                            if (empty($detailClass)) continue;

                            $detailData = $inspector->scanController($detailClass);

                            foreach ($detailData as $detailItem) {
                                $detailClassName = str_replace('controller', '', strtolower($detailItem['class']));

                                $idHeader = $acosModel->select('id')
                                    ->where('class', $classHeader)
                                    ->where('method', 'index')
                                    ->first()['id'] ?? 0;

                                $existing = $acosModel
                                    ->where('class', $detailClassName)
                                    ->where('method', $detailItem['method'])
                                    ->first();

                                if ($existing) {
                                    $acosModel->processUpdate([
                                        'id' => $existing['id'],
                                        'nama' => $detailItem['name'],
                                        'keterangan' => $item['keterangan'],
                                        'class' => $detailClassName,
                                        'method' => $detailItem['method'],
                                        // 'modifiedby' => $modifiedBy,
                                    ]);
                                } else {
                                    $acosModel->processStore([
                                        'class' => $detailClassName,
                                        'method' => $detailItem['method'],
                                        'nama' => $detailItem['name'],
                                        'idheader' => $idHeader,
                                        'keterangan' => $item['keterangan'],
                                        // 'modifiedby' => $modifiedBy,
                                    ]);
                                }
                            }
                        }
                    }

                    // Ambil ID ACO utama method index untuk menu (Mencegah fallback ke nilai lama jika berubah)
                    $newMenuAcoId = $acosModel->select('id')
                        ->where('class', $className)
                        ->where('method', 'index')
                        ->orderBy('id', 'asc')
                        ->first()['id'] ?? null;

                    if ($newMenuAcoId) {
                        $menuAcoId = $newMenuAcoId;
                    }
                }
            }
        }

        // Update menu dengan data baru
        $updateData = [
            'id' => $data['id'],
            'menuname'    => $data['menuname'] ?? $menu['menuname'],
            'menu_seq'    => (int) ($data['menu_seq'] ?? $menu['menu_seq']),
            'menu_parent' => (int) ($data['menu_parent'] ?? $menu['menu_parent']),
            'menu_icon'   => strtolower($data['menu_icon'] ?? $menu['menu_icon']),
            'aco_id'      => (int) $menuAcoId,
            'controller'  => $data['controller'] ?? $menu['controller'],
        ];

        if (!$this->update($data['id'], $updateData)) {
            throw new \Exception("Error updating menu: " . json_encode($this->errors()));
        }

        return $this;
    }

    public function processDelete($id)
    {
        $menu = $this->find($id);
        if (!$menu) {
            throw new \Exception("Menu dengan ID {$id} tidak ditemukan.");
        }

        $acosModel = new Acos();
        $acos = $acosModel->where('id', $menu['aco_id'])->first();

        if ($acos) {
            $acosModel->where('class', $acos['class'])->delete();
        }

        $this->delete($id);

        return $this;
    }

    protected function generateMenuKode($parentId, $menuName): string
    {
        if (strtoupper($menuName) === 'LOGOUT') {
            return 'Z';
        }

        $inspector = new ControllerInspector();

        // =========================
        // CEK APAKAH SUDAH ADA SIBLING
        // =========================
        $lastSibling = $this
            ->where('menu_parent', $parentId)
            ->orderBy('menukode', 'DESC')
            ->first();

        // =========================
        // JIKA BELUM ADA SAMA SEKALI
        // =========================
        if (!$lastSibling) {

            // Parent = 0 → root menu
            if ($parentId == 0) {

                $lastRoot = $this
                    ->where('menu_parent', 0)
                    ->orderBy('menukode', 'DESC')
                    ->first();

                if (!$lastRoot) {
                    return '1';
                }

                return $inspector->incrementKode($lastRoot['menukode']);
            }

            // Child pertama → ambil kode parent + ".1"
            $parent = $this->find($parentId);
            return $parent['menukode'] . '.1';
        }

        // =========================
        // JIKA SUDAH ADA SIBLING
        // =========================
        return $inspector->incrementKode($lastSibling['menukode']);
    }


    
}
