<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Libraries\ControllerInspector;
use App\Models\Menu as MenuModel;
// use CodeIgniter\RESTful\ResourceController;
// use CodeIgniter\HTTP\RequestInterface;

class MenuController extends BaseController
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

    public function show($id = null)
    {
        $menu = (new MenuModel())->findOne($id);

        if (!$menu) {
            return $this->failNotFound("Menu not found");
        }

        return $this->respond($menu);
    }

    /**
     * @ClassName 
     * @Keterangan TAMBAH DATA
     */
    public function create()
    {
        $payload = $this->request->getJSON(true);

        // Ambil semua post data
        $data = [
            'menuname'   => $payload['menuname'] ?? '',
            'menu_seq'    => $payload['menu_seq'] ?? 0,
            'menu_icon'   => $payload['menu_icon'] ?? '',
            'menu_parent'   => $payload['menu_parent'] ?? 0,
            'link' => $payload['link'] ?? '',
            'controller' => $payload['controller'] ?? '',
        ];

        $db = db_connect();
        $db->transStart();

        try {
            $menuModel = new MenuModel();

            if (!$menuModel->validate($data)) {
                return $this->respond([
                    'errors' => $menuModel->errors()
                ], 422);
            }

            // Validasi & Simpan via Model
            // $rules = [
            //     'controller' => 'required|max_length[100]'
            // ];

            // if (!$this->validate($rules)) {
            //     return $this->respond([
            //         'errors' => $this->validator->getErrors()
            //     ], 422);
            // }

            $menu   = $menuModel->processStore($data);

            $db->transComplete();

            return $this->respond([
                'message' => 'Berhasil disimpan',
                'data'    => $menu,
            ]);

        } catch (\Throwable $th) {

            $db->transRollback();

            return $this->respond([
                'message' => $th->getMessage(),
                'error' => $th->getLine(),
            ])->setStatusCode(500);
        }
    }

    /**
     * @ClassName 
     * @Keterangan UPDATE DATA
     */
    public function update($id = null)
    {
        $payload = $this->request->getJSON(true);

        $data = [
            'id' => $id,
            'menuname'   => $payload['menuname'] ?? '',
            'menu_seq'    => $payload['menu_seq'] ?? 0,
            'menu_icon'   => $payload['menu_icon'] ?? '',
            'menu_parent'   => $payload['menu_parent'] ?? 0,
            'link' => $payload['link'] ?? '',
            'controller' => $payload['controller'] ?? '',
        ];

        $db = db_connect();
        $db->transStart();

        try {
            $menuModel = new MenuModel();

            if (!$menuModel->validate($data)) {
                return $this->respond([
                    'errors' => $menuModel->errors()
                ], 422);
            }

            $menu   = $menuModel->processUpdate($data);

            $db->transComplete();

            return $this->respondUpdated([
                'message' => 'Menu berhasil diupdate',
                'data'    => $menu,
            ]);

        } catch (\Throwable $th) {

            $db->transRollback();

            return $this->respond([
                'message' => $th->getMessage(),
                'error' => $th->getTrace(),
            ])->setStatusCode(500);
        }
    }

    /**
     * @ClassName 
     * @Keterangan DELETE DATA
     */
    public function delete($id = null)
    {
        $db = db_connect();
        $db->transStart();

        try {
            $menuModel = new MenuModel();
            $menu = $menuModel->processDelete($id);

            $db->transComplete();

            return $this->respondDeleted([
                'message' => 'Menu berhasil dihapus',
                'data'    => $menu,
            ]);

        } catch (\Throwable $th) {

            $db->transRollback();

            return $this->respond([
                'message' => $th->getMessage(),
                'error' => $th->getTrace(),
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
        $model = new MenuModel();
        $classes = $model->getController();
    
        return $this->respond([
            'data'   => $classes
        ]);
    }

    public function getMenuParent()
    {
        $model = new MenuModel();
        return $this->respond([
            'data' => $model->getMenuParent()
        ]);
    }
}
