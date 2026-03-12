<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Acos;
use App\Libraries\ControllerInspector;
use Config\Database;
use App\Services\AcosService;

/**
 * Service class for handling Menu business logic.
 */
class MenuService
{
    protected $menuModel;
    protected $acosModel;
    protected $acosService;
    protected $db;

    public function __construct()
    {
        $this->menuModel = new Menu();
        $this->acosModel = new Acos();
        $this->acosService = new AcosService();
        $this->db = Database::connect();
    }

    /**
     * Stores a new menu and processes related ACOS.
     *
     * @param array $data
     * @return Menu
     * @throws \Exception
     */
    public function create(array $data)
    {
        $inspector = new ControllerInspector();
        $controllerData = $inspector->scanController($data['controller']);

        $menuAcoId = 0;
        $modifiedBy = session()->get('id') ?? 0;

        if (!empty($controllerData)) {
            foreach ($controllerData as $item) {
                $className = str_replace('controller', '', strtolower($item['class']));
                $classHeader = $className;

                // Simpan ACO untuk method utama
                $this->acosService->create([
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
                            $detailClassName = str_replace('controller', '', strtolower($detailItem['class']));

                            $idHeader = $this->acosModel->select('id')
                                ->where('class', $classHeader)
                                ->where('method', 'index')
                                ->first()['id'] ?? 0;

                            $this->acosService->create([
                                'class' => $detailClassName,
                                'method' => $detailItem['method'],
                                'nama' => $detailItem['name'],
                                'idheader' => $idHeader,
                                'keterangan' => $item['keterangan'],
                            ]);
                        }
                    }
                }

                // Ambil ACO id utama
                $menuAcoId = $this->acosModel->select('id')
                    ->where('class', $className)
                    ->where('method', 'index')
                    ->orderBy('id', 'asc')
                    ->first()['id'] ?? 0;
            }
        }

        try {
            $saveData = [
                'menuname'   => ucwords($data['menuname']),
                'menu_seq'    => (int) $data['menu_seq'],
                'menu_parent' => (int) $data['menu_parent'] ?? 0,
                'menu_icon'   => $data['menu_icon'],
                'link'       => '',
                'aco_id'     => (int) $menuAcoId,
                'menukode'   => $this->generateMenuKode($data['menu_parent'], $data['menuname']),
                'controller' => $data['controller'],
            ];

            if (!$this->menuModel->insert($saveData)) {
                throw new \Exception("Error storing menu." . json_encode($this->menuModel->errors()));
            }

            return $this->menuModel->find($this->menuModel->getInsertID());

        } catch (\Exception $e) {
            log_message('error', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Updates an existing menu and its related ACOS.
     *
     * @param array $data
     * @return Menu
     * @throws \Exception
     */
    public function update(array $data)
    {
        $inspector = new ControllerInspector();

        $menu = $this->menuModel->find($data['id']);
        if (!$menu) {
            throw new \Exception("Menu dengan ID {$data['id']} tidak ditemukan.");
        }

        $menuAcoId = $menu['aco_id'];
        $modifiedBy = session()->get('id') ?? 0;

        // Cek controller
        // cek jika aco_id = 0, berarti itu menu master
        if ($menu['aco_id'] != 0) {
            $acos = $this->acosModel->where('id', $menu['aco_id'])->first();
            // Jika controller tidak diisi, ambil dari acos
            $controller = (empty($data['controller']) ? $acos['class'] : $data['controller']);
        } else {
            $controller = $data['controller'];
        }

        if (!empty($controller)) {
            $controllerData = $inspector->scanController($controller);

            if (!empty($controllerData)) {
                foreach ($controllerData as $item) {
                    $className = str_replace('controller', '', strtolower($item['class']));
                    $classHeader = $className;

                    // Cek ada gak method ini di acos
                    $aco = $this->acosModel
                        ->where('class', $className)
                        ->where('method', $item['method'])
                        ->first();

                    if ($aco) {
                        // Update keterangan dan modifiedby jika sudah ada
                        $this->acosService->update([
                            'id' => $aco['id'],
                            'class' => $className,
                            'method' => $item['method'],
                            'nama' => $item['name'],
                            'keterangan' => $item['keterangan'],
                        ]);
                    } else {
                        // Insert baru method baru yang belum ada
                        $this->acosService->create([
                            'class' => $className,
                            'method' => $item['method'],
                            'nama' => $item['name'],
                            'keterangan' => $item['keterangan'],
                            'idheader' => 0,
                        ]);
                    }

                    // Proses detail controller jika ada
                    if (!empty($item['detail'])) {
                        foreach ($item['detail'] as $detailClass) {
                            if (empty($detailClass)) continue;

                            $detailData = $inspector->scanController($detailClass);

                            foreach ($detailData as $detailItem) {
                                $detailClassName = str_replace('controller', '', strtolower($detailItem['class']));

                                $idHeader = $this->acosModel->select('id')
                                    ->where('class', $classHeader)
                                    ->where('method', 'index')
                                    ->first()['id'] ?? 0;

                                $existing = $this->acosModel
                                    ->where('class', $detailClassName)
                                    ->where('method', $detailItem['method'])
                                    ->first();

                                if ($existing) {
                                    $this->acosService->update([
                                        'id' => $existing['id'],
                                        'nama' => $detailItem['name'],
                                        'keterangan' => $item['keterangan'],
                                        'class' => $detailClassName,
                                        'method' => $detailItem['method'],
                                    ]);
                                } else {
                                    $this->acosService->create([
                                        'class' => $detailClassName,
                                        'method' => $detailItem['method'],
                                        'nama' => $detailItem['name'],
                                        'idheader' => $idHeader,
                                        'keterangan' => $item['keterangan'],
                                    ]);
                                }
                            }
                        }
                    }

                    // Ambil ID ACO utama method index untuk menu (Mencegah fallback ke nilai lama jika berubah)
                    $newMenuAcoId = $this->acosModel->select('id')
                        ->where('class', $className)
                        ->where('method', 'index')
                        ->orderBy('id', 'asc')
                        ->first()['id'] ?? null;

                    if ($newMenuAcoId) {
                        $menuAcoId = $newMenuAcoId;
                    }
                }
            }
        }

        // Update menu dengan data baru
        $updateData = [
            'id' => $data['id'],
            'menuname'    => $data['menuname'] ?? $menu['menuname'],
            'menu_seq'    => (int) ($data['menu_seq'] ?? $menu['menu_seq']),
            'menu_parent' => (int) ($data['menu_parent'] ?? $menu['menu_parent']),
            'menu_icon'   => strtolower($data['menu_icon'] ?? $menu['menu_icon']),
            'aco_id'      => (int) $menuAcoId,
            'controller'  => $data['controller'] ?? $menu['controller'],
        ];

        if (!$this->menuModel->update($data['id'], $updateData)) {
            throw new \Exception("Error updating menu: " . json_encode($this->menuModel->errors()));
        }

        return $this->menuModel->find($data['id']);
    }

    /**
     * Deletes a menu and its associated ACOS by ID.
     *
     * @param int|string $id
     * @return Menu
     * @throws \Exception
     */
    public function delete($id)
    {
        $menu = $this->menuModel->find($id);
        if (!$menu) {
            throw new \Exception("Menu dengan ID {$id} tidak ditemukan.");
        }

        $acos = $this->acosModel->where('id', $menu['aco_id'])->first();

        if ($acos) {
            $this->acosModel->where('class', $acos['class'])->delete();
        }

        $this->menuModel->delete($id);

        return $menu;
    }

    /**
     * Generates a menu code based on its parent.
     *
     * @param int $parentId
     * @param string $menuName
     * @return string
     */
    protected function generateMenuKode($parentId, $menuName): string
    {
        if (strtoupper($menuName) === 'LOGOUT') {
            return 'Z';
        }

        $inspector = new ControllerInspector();

        // =========================
        // CEK APAKAH SUDAH ADA SIBLING
        // =========================
        $lastSibling = $this->menuModel
            ->where('menu_parent', $parentId)
            ->orderBy('menukode', 'DESC')
            ->first();

        // =========================
        // JIKA BELUM ADA SAMA SEKALI
        // =========================
        if (!$lastSibling) {

            // Parent = 0 → root menu
            if ($parentId == 0) {

                $lastRoot = $this->menuModel
                    ->where('menu_parent', 0)
                    ->orderBy('menukode', 'DESC')
                    ->first();

                if (!$lastRoot) {
                    return '1';
                }

                return $inspector->incrementKode($lastRoot['menukode']);
            }

            // Child pertama → ambil kode parent + ".1"
            $parent = $this->menuModel->find($parentId);
            return $parent['menukode'] . '.1';
        }

        // =========================
        // JIKA SUDAH ADA SIBLING
        // =========================
        return $inspector->incrementKode($lastSibling['menukode']);
    }
}
