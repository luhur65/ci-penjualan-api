<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\Alatbayar;

class AlatbayarController extends BaseController
{
    use ResponseTrait;

    protected $model;

    public function __construct()
    {
        // Model ini sudah otomatis terhubung ke SQL Server via properti $DBGroup
        $this->model = new Alatbayar();
    }


    /**
     * @ClassName 
     * @Keterangan TAMPILKAN DATA
     */
    public function index()
    {
        $params = $this->request->getGet();
        $this->model->setRequestParameters($params);
        
        $data = $this->model->getAll();

        return $this->respond([
            'data' => $data['data'],
            'attributes' => [
                'totalRows'  => $data['totalRows'],
                'totalPages' => $data['totalPages'],
            ],
        ]);
    }
}
