<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        // Return json response with header application/json
        return $this->response->setJSON(['message' => env('app.name') . ' is running']);
    }
}
