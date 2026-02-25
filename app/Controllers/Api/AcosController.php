<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\Acos;

class AcosController extends BaseController
{
    use ResponseTrait;


    /**
     * @ClassName 
     * @Keterangan TAMPILKAN DATA
     */
    public function index()
    {
        $acos = new Acos();
        return $this->respond([
            'data' => $acos->get(),
            'attributes' => [
                'totalRows' => $acos->totalRows,
                'totalPages' => $acos->totalPages
            ]
        ]);
    }
}
