<?php

namespace App\Controllers\Api;

use App\Models\Menu as MenuModel;
use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Libraries\ControllerInspector;
use CodeIgniter\RESTful\ResourceController;
// use CodeIgniter\HTTP\RequestInterface;

class Menu extends ResourceController
{

    use ResponseTrait;

    /**
     * @ClassName 
     * @Keterangan TAMPILKAN DATA
     */
    public function index()
    {
        $menu = new MenuModel();

        return $this->respond([
            'data' => $menu->get(),
            'attributes' => [
                'totalRows' => $menu->totalRows,
                'totalPages' => $menu->totalPages
            ]
        ]);

    }

    /**
     * @ClassName 
     * @Keterangan TAMBAH DATA
     */
    public function store()
    {
        $request = service('request');

        // Ambil semua post data
        $data = [
            'menuname'   => ucwords(strtolower($request->getPost('menuname'))),
            'menuseq'    => $request->getPost('menuseq'),
            'menuparent' => $request->getPost('menuparent') ?? 0,
            'menuicon'   => strtolower($request->getPost('menuicon')),
            'menuexe'    => strtolower($request->getPost('menuexe')),
            'controller' => $request->getPost('controller'),
        ];

        $db = db_connect();
        $db->transStart();

        try {
            $menuModel = new MenuModel();

            // Validasi & Simpan via Model
            if (!$menuModel->save($data)) {

                return $this->respond([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $menuModel->errors()
                ])->setStatusCode(400);
            }

            $menuId = $menuModel->getInsertID();
            $menu   = $menuModel->find($menuId);

            // // Hitung posisi
            // $limit = (int) ($request->getPost('limit') ?? 10);
            // $menu->position = $this->getPosition($menu, $menuModel->table)->position;
            // $menu->page = ceil($menu->position / max($limit, 1));

            $db->transComplete();

            return $this->respond([
                'status'  => true,
                'message' => 'Berhasil disimpan',
                'data'    => $menu,
            ]);

        } catch (\Throwable $th) {

            $db->transRollback();

            return $this->respond([
                'status' => false,
                'message' => 'Gagal menyimpan',
                'error' => $th->getMessage(),
            ])->setStatusCode(500);
        }
    }

    
    /**
     * @ClassName 
     * @Keterangan REPORT DATA
    */
    public function report() {}
    
    /**
     * @ClassName 
     * @Keterangan EXPORT KE EXCEL
    */
    public function export() {}

    public function fieldLength()
    {
        $model = new MenuModel();
        return $this->respond($model->getFieldLengths());
    }


    public function getAllClass()
    {
        $inspector = new ControllerInspector();
        $classes = $inspector->scanAllControllers();
    
        return $this->respond([
            'status' => true,
            'data'   => $classes
        ]);
    }
}
