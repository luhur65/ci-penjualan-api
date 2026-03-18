<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\ParameterService;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\IncomingRequest;

class ParameterController extends BaseController
{
    use ResponseTrait;

    /** @var IncomingRequest $request */
    protected $request;
    protected $parameterService;

    public function __construct()
    {
        $this->request = service('request');
        $this->parameterService = new ParameterService();
    }

    /**
     * @ClassName 
     * @Keterangan TAMPILKAN DATA
     */
    public function index()
    {
        $requestData = $this->request->getGet();
        return $this->respond($this->parameterService->getAllParameters($requestData));
    }

    public function getCombo()
    {
        $grp = $this->request->getVar('grp');
        $subgrp = $this->request->getVar('subgrp');
        return $this->respond($this->parameterService->getComboByMemo($grp, $subgrp));
    }

    public function lookup()
    {
        $grp = $this->request->getVar('grp');
        $subgrp = $this->request->getVar('subgrp');
        return $this->respond([
            'data' => $this->parameterService->getLookup($grp, $subgrp)
        ]);
    }
}
