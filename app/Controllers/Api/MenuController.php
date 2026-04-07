<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\ExcelMaker;
use CodeIgniter\API\ResponseTrait;  
use App\Models\Menu;
use App\Services\MenuService;
use CodeIgniter\HTTP\IncomingRequest;

class MenuController extends BaseController
{

    use ResponseTrait;

    /** @var IncomingRequest $request */
    protected $request;

    protected $menuService;
    protected $menuModel;


    public function __construct()
    {
        $this->menuService = new MenuService();
        $this->menuModel = new Menu();
    }

    /**
     * @ClassName 
     * @Keterangan TAMPILKAN DATA
     */
    public function index()
    {
        $requestData = $this->request->getGet();
        return $this->respond($this->menuService->getAllMenus($requestData));
    }

    public function show($id = null)
    {
        if (!$menu = $this->menuModel->findOne($id)) {
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
            'menuname'      => $payload['menuname'] ?? '',
            'menu_seq'      => $payload['menu_seq'] ?? 0,
            'menu_icon'     => $payload['menu_icon'] ?? '',
            'menu_parent'   => $payload['menu_parent'] ?? 0,
            'link'          => $payload['menu_exe'] ?? '',
            'controller'    => $payload['controller'] ?? '',
            'modified_by'   => $this->authUserName() ?? null,
        ];

        try {
            if (!$this->menuModel->validate($data)) {
                return $this->respond([
                    'errors' => $this->menuModel->errors()
                ], 422);
            }

            $result = $this->menuService->create($data, $payload);


            return $this->respondCreated([
                'message' => 'Data berhasil disimpan',
                'data'    => $result,
            ]);
        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
            // return $this->respond([
            //     'message' => $th->getMessage(),
            //     'error' => $th->getLine(),
            // ])->setStatusCode(500);
        }
    }

    /**
     * Updates an existing Menu by ID.
     * 
     * @ClassName 
     * @Keterangan UPDATE DATA
     */
    public function update($id = null)
    {
        $payload = $this->request->getJSON(true);

        $data = [
            'id'            => $id,
            'menuname'      => $payload['menuname'] ?? '',
            'menu_seq'      => $payload['menu_seq'] ?? 0,
            'menu_icon'     => $payload['menu_icon'] ?? '',
            'menu_parent'   => $payload['menu_parent'] ?? 0,
            'link'          => $payload['menu_exe'] ?? '',
            'controller'    => $payload['controller'] ?? '',
            'modified_by'   => $this->authUserName() ?? null,
        ];

        try {
            if (!$this->menuModel->validate($data)) {
                return $this->respond([
                    'errors' => $this->menuModel->errors()
                ], 422);
            }

            $result = $this->menuService->update($data, $payload);

            return $this->respondUpdated([
                'message' => 'Data berhasil diupdate',
                'data'    => $result,
            ]);
        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
            // return $this->respond([
            //     'message' => $th->getMessage(),
            //     'error' => $th->getTrace(),
            // ])->setStatusCode(500);
        }
    }

    /**
     * Deletes a Menu by ID.
     * 
     * @ClassName 
     * @Keterangan DELETE DATA
     */
    public function delete($id = null)
    {
        $payload = $this->request->getJSON(true);
        try {
            $result = $this->menuService->delete($id, $payload);

            return $this->respondDeleted([
                'message' => 'Data berhasil dihapus',
                'data'    => $result,
            ]);
        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
            // return $this->respond([
            //     'message' => $th->getMessage(),
            //     'error' => $th->getTrace(),
            // ])->setStatusCode(500);
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
    public function export()
    {
        $data = $this->menuService->getAllMenus($this->request->getGet());
        $excelData = [];

        // (Opsional) Jika Anda mengirimkan 'offset' dari FE, nomor urut bisa disesuaikan
        $offset = $this->request->getGet('offset') ?? 0;

        foreach ($data['data'] as $index => $item) {

            $excelData[] = [
                $offset + $index + 1,      // Nomor Urut yang akurat
                $item->menuname ?? '',
                $item->menu_icon ?? '',
                $item->parent_name ?? '-',
                $item->link ?? '',
                $item->menukode ?? '',
                $item->modified_by ?? '',
                $item->created_at ?? '', 
                $item->updated_at ?? ''
            ];
        }

        // 4. Tentukan Judul Kolom
        $headers = ['NO', 'NAMA MENU', 'ICON', 'PARENT MENU', 'LINK', 'KODE MENU', 'MODIFIED BY', 'CREATED AT', 'UPDATED AT'];

        // dd($excelData);

        $excelService = new ExcelMaker();
        return $excelService->generate('Laporan_Menu_' . date('Ymd_His'), $headers, $excelData);
    }

    public function fieldLength()
    {
        try {
            $result = $this->menuModel->getFieldLengths();
            return $this->respond([
                'data' => $result
            ]);
        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
        }
    }


    public function getAllClass()
    {
        try {
            $classes = $this->menuService->getController();
            return $this->respond([
                'data'   => $classes
            ]);
        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
        }
    }

    public function getMenuParent()
    {
        try {
            $result = $this->menuService->getMenuParent();
            return $this->respond([
                'data' => $result
            ]);
        } catch (\Throwable $th) {
            return $this->failServerError($th->getMessage());
        }
    }
}
