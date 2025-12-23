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
        'menuseq',
        'menuparent',
        'menuicon',
        'menuexe',
        'link',
        'aco_id',
        // 'info',
        // 'modifiedby',
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
        'menuname'   => 'required|max_length[255]',
        'menuseq'    => 'required|is_natural',
        'menuparent' => 'permit_empty|is_natural',
        'menuicon'   => 'required|max_length[50]',
        'menuexe'    => 'required|max_length[50]',
        'controller' => 'required|max_length[100]'
    ];
    protected $validationMessages = [
        'menuname' => [
            'required' => 'Nama menu wajib diisi.'
        ]
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
        $builder = $this->builder();
        $builder->select('id, menuname, menu_seq, menu_parent, menu_icon, aco_id, link, menuexe, menukode, updated_at, created_at');

        $this->filter($builder);
        $this->sort($builder);
        $this->pagination($builder);

        $data = $builder->get()->getResult();

        $this->totalRows = $builder->countAllResults(false);
        $this->totalPages = ($this->totalRows > 0) ? ceil($this->totalRows / $this->params['limit']) : 1;

        return $data;
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

    public function processStore(array $data): Menu
    {
        $acosModel = new Acos();
        $inspector = new ControllerInspector();
        $controllerData = $inspector->scanController($data['controller']);
        
        $menuAcoId = 0;
        $modifiedBy = session()->get('id') ?? 0;

        if (!empty($controllerData)) {
            foreach ($controllerData as $item) {
                $className = strtolower(str_replace('controller', '', $item['class']));
                $classHeader = $className;

                // Simpan ACO untuk method utama
                $aco = (new Acos())->processStore([
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
                            $detailClassName = strtolower(str_replace('controller', '', $detailItem['class']));

                            $idHeader = $acosModel->select('id')
                                ->where('class', $classHeader)
                                ->where('method', 'index')
                                ->first()['id'] ?? 0;

                            (new Acos())->processStore([
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

                // Ambil ACO id utama
                $menuAcoId = $acosModel->select('id')
                    ->where('class', $className)
                    ->where('method', 'index')
                    ->orderBy('id', 'asc')
                    ->first()['id'] ?? 0;
            }
        }

        try {
            $menu = new Menu();
            $saveData = [
                'menuname'   => ucwords(strtolower($data['menuname'])),
                'menuseq'    => $data['menuseq'],
                'menuparent' => $data['menuparent'] ?? 0,
                'menuicon'   => strtolower($data['menuicon']),
                'menuexe'    => strtolower($data['menuexe']),
                'link'       => '',
                'aco_id'     => $menuAcoId,
                'menukode'   => $this->generateMenuKode($data['menuparent'], $data['menuname'])
            ];

            if (!$menu->save($saveData)) {
                throw new \Exception("Error storing menu.");
            }

            return $menu;

        } catch (\Exception $e) {
            log_message('error', $e->getMessage());
            throw $e;
        }
    }

    protected function generateMenuKode($parentId, $menuName): string
    {
        if (strtoupper($menuName) === 'LOGOUT') {
            return 'Z';
        }

        // Logic menukode sesuai parent, sama persis seperti sebelumnya
        // ...
        return 'AUTO_GENERATED_KODE';
    }


    
}
