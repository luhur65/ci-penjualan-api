<?php

namespace App\Models;

use App\Models\CustomModel;

class Role extends CustomModel
{
    protected $table            = 'roles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'rolename',
    ];

    protected bool $allowEmptyInserts = false;
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
        'rolename' => 'required',
    ];
    protected $validationMessages   = [
        'rolename' => [
            'required' => 'Role name is required',
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
        $builder = $this->builder();
        $builder->select('id, rolename, updated_at, created_at');

        $this->filter($builder);
        $this->sort($builder);
        $this->pagination($builder);

        $data = $builder->get()->getResult();

        $this->totalRows = $builder->countAllResults(false);
        $this->totalPages = ($this->totalRows > 0) ? ceil($this->totalRows / $this->params['limit']) : 1;

        return $data;
    }

    public function findOne($id = null)
    {
        $role = $this->builder()
            ->select('id, rolename, updated_at, created_at')
            ->where('id', $id)
            ->get()
            ->getRow();

        return [
            'data' => $role
        ];
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

    public function processStore($data)
    {
        if (!$this->insert($data)) {
            throw new \Exception("Error storing role.");
        }

        return true;
    }

    public function processUpdate($data)
    {
        if (!$this->update($data['id'], $data)) {
            throw new \Exception("Error updating role.");
        }

        $userModel = new User();
        $aclModel = new Acl();
        $aclModel->where('role_id', $data['id'])->delete();

        $acos = [];
        foreach ($data['acosIds'] as $acoId) {
            $acos[] = [
                'aco_id'  => $acoId,
                'role_id' => $data['id'],
            ];
        }

        if (!empty($acos)) {
            $aclModel->insertBatch($acos);
        }

        // 6. Query user dengan role tersebut
        // Menggunakan Query Builder CI4. 
        // Catatan: Jika Anda mutlak membutuhkan isolasi 'readuncommitted' SQL Server, Anda harus menggunakan $db->query() secara mentah.
        $queryUser = $this->db->table('userroles a')
            ->select('a.user_id')
            ->where('a.role_id', $data['id'])
            ->groupBy('a.user_id')
            ->get()
            ->getResultArray();

        // 7. Update menu user
        foreach ($queryUser as $item) {
            $userId = $item['user_id'];

            // Asumsi getMenu dan printRecursiveMenu sudah dikonversi ke dalam UserModel CI4
            $getMenu = $userModel->getMenu($userId);
            $listMenu = $userModel->printRecursiveMenu($getMenu, false);

            // Update langsung via Query Builder
            $this->db->table('users')
                ->where('id', $userId)
                ->update([
                    'menu' => $listMenu
                ]);
        }

        return true;
    }

    public function processDelete($id)
    {

        $role = $this->find($id);

        if (empty($role)) {
            throw new \Exception("Role with ID {$id} not found.");
        }

        $this->delete($id);

        return true;
    }

}
