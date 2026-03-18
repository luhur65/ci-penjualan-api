<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Services\AcosService;
use CodeIgniter\HTTP\IncomingRequest;

class AcosController extends BaseController
{
    use ResponseTrait;

    /** @var IncomingRequest $request */
    protected $request;
    protected $acosService;

    public function __construct()
    {
        $this->acosService = new AcosService();
    }


    /**
     * @ClassName 
     * @Keterangan TAMPILKAN DATA
     */
    public function index()
    {
        $requestData = $this->request->getGetPost();
        return $this->respond($this->acosService->getAllAcos($requestData));
    }
}
