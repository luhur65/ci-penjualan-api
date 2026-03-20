<?php

namespace App\Services;

use App\Models\Menu;
use App\Services\AcosService;
use App\Libraries\ControllerInspector;
use Config\Database;

/**
 * Service class for handling Menu business logic.
 */
class MenuService
{
  protected $menuModel;
  protected $acosService;
  protected $db;

  public function __construct()
  {
    $this->menuModel = new Menu();
    $this->acosService = new AcosService();
    $this->db = Database::connect();
  }

  /**
   * Retrieves all menus with pagination.
   *
   * @param array $params Request parameters for filtering and pagination.
   * @return array Array containing menu data and pagination attributes.
   */
  public function getAllMenus(array $params)
  {
    $menus = $this->menuModel->setRequestParameters($params)->getAll();

    return [
      'data' => $menus['data'],
      'attributes' => [
        'totalRows' => $menus['totalRows'],
        'totalPages' => $menus['totalPages']
      ]
    ];
  }

  /**
   * Stores a new menu and processes related ACOS.
   *
   * @param array $data
   * @param array $params
   * @return array
   * @throws \Exception
   */
  public function create(array $data, array $params)
  {
    $inspector = new ControllerInspector();
    $controllerData = $inspector->scanController($data['controller']);

    $menuAcoId = 0;
    $modifiedBy = $data['modified_by'] ?? null;

    $this->db->transBegin();

    try {

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
            'modified_by' => $modifiedBy,
          ]);
  
          // Simpan ACO untuk detail jika ada
          if (!empty($item['detail'])) {
            foreach ($item['detail'] as $detailClass) {
              if ($detailClass === '') continue;
  
              $detailData = $inspector->scanController($detailClass);
  
              foreach ($detailData as $detailItem) {
                $detailClassName = str_replace('controller', '', strtolower($detailItem['class']));
  
                $idHeader = $this->acosService->findOneByClassAndMethod($classHeader, 'index')['id'] ?? 0;
  
                $this->acosService->create([
                  'class' => $detailClassName,
                  'method' => $detailItem['method'],
                  'nama' => $detailItem['name'],
                  'idheader' => $idHeader,
                  'keterangan' => $item['keterangan'],
                  'modified_by' => $modifiedBy,
                ]);
              }
            }
          }
  
          // Ambil ACO id utama
          $menuAcoId = $this->acosService->findOneByClassAndMethod($className, 'index')['id'] ?? 0;
        }
      }

      $saveData = [
        'menuname'   => ucwords($data['menuname']),
        'menu_seq'    => (int) $data['menu_seq'],
        'menu_parent' => (int) $data['menu_parent'] ?? 0,
        'menu_icon'   => $data['menu_icon'],
        'link'       => '',
        'aco_id'     => (int) $menuAcoId,
        'menukode'   => $this->generateMenuKode($data['menu_parent'], $data['menuname']),
        'controller' => $data['controller'],
        'modified_by' => $modifiedBy,
      ];
      
      if (!$this->menuModel->insert($saveData)) {
        throw new \Exception("Error storing menu." . json_encode($this->menuModel->errors()));
      }

      $newId = $this->menuModel->getInsertID();
      $position = $this->menuModel->getPosition($newId, $params);
      
      $this->db->transCommit();

      return $position;

    } catch (\Throwable $th) {
      $this->db->transRollback();
      log_message('error', $th->getMessage());
      throw $th;
    }
  }

  /**
   * Updates an existing menu and its related ACOS.
   *
   * @param array $data
   * @param array $params
   * @return array
   * @throws \Exception
   */
  public function update(array $data, array $params)
  {
    $inspector = new ControllerInspector();

    $menu = $this->menuModel->find($data['id']);
    if (!$menu) {
      throw new \Exception("Menu dengan ID {$data['id']} tidak ditemukan.");
    }

    $menuAcoId = $menu['aco_id'];
    $modifiedBy = $data['modified_by'] ?? null;

    // 1. Ambil dari inputan user di Frontend (Jika dia memilih sesuatu yang baru)
    $controller = $data['controller'] ?? null;

    // 2. Jika Frontend mengirim null/kosong, ambil jejak dari tabel menus
    if (empty($controller)) {
      $controller = $menu['controller'] ?? null;
    }

    // 3. Jika di tabel menus JUGA kosong (karena ini data lama), lacak lewat tabel acos!
    if (empty($controller) && !empty($menu['aco_id'])) {
      $acos = $this->acosService->findOneById($menu['aco_id']);
      $controller = $acos['class'] ?? null;
    }

    
    $this->db->transBegin();
    
    try {
      
      // Jika menu ini memiliki controller (bukan menu folder/parent mati)
      if (!empty($controller)) {
        
        // JIT SCAN: Membedah file fisik secara spesifik!
        $controllerData = $inspector->scanController($controller);
        
        if (!empty($controllerData)) {
          foreach ($controllerData as $item) {
            $className = str_replace('controller', '', strtolower($item['class']));
            $classHeader = $className;

            // Cek apakah method ini sudah didaftarkan di ACOS
            $aco = $this->acosService->findOneByClassAndMethod($className, $item['method']);

            if ($aco) {
              // Jika method lama -> UPDATE
              $this->acosService->update([
                'id'          => $aco['id'],
                'class'       => $className,
                'method'      => $item['method'],
                'nama'        => $item['name'],
                'keterangan'  => $item['keterangan'],
                'modified_by' => $modifiedBy,
              ]);
            } else {
              // [KUNCI PERBAIKAN 2]: Jika method BARU ditemukan di file fisik -> INSERT OTOMATIS!
              $this->acosService->create([
                'class'       => $className,
                'method'      => $item['method'],
                'nama'        => $item['name'],
                'keterangan'  => $item['keterangan'],
                'idheader'    => 0,
                'modified_by' => $modifiedBy,
              ]);
            }

            // Proses detail controller (jika ada controller anakan)
            if (!empty($item['detail'])) {
              foreach ($item['detail'] as $detailClass) {
                if (empty($detailClass)) continue;

                $detailData = $inspector->scanController($detailClass);

                foreach ($detailData as $detailItem) {
                  $detailClassName = str_replace('controller', '', strtolower($detailItem['class']));

                  // Dapatkan idHeader dari bapaknya yang baru saja discan
                  $idHeader = $this->acosService->findOneByClassAndMethod($classHeader, 'index')['id'] ?? 0;
                  $existing = $this->acosService->findOneByClassAndMethod($detailClassName, $detailItem['method']);

                  if ($existing) {
                    $this->acosService->update([
                      'id'          => $existing['id'],
                      'nama'        => $detailItem['name'],
                      'keterangan'  => $item['keterangan'], // Mewarisi keterangan induk
                      'class'       => $detailClassName,
                      'method'      => $detailItem['method'],
                      'modified_by' => $modifiedBy,
                    ]);
                  } else {
                    $this->acosService->create([
                      'class'       => $detailClassName,
                      'method'      => $detailItem['method'],
                      'nama'        => $detailItem['name'],
                      'idheader'    => $idHeader,
                      'keterangan'  => $item['keterangan'],
                      'modified_by' => $modifiedBy,
                    ]);
                  }
                }
              }
            }

            // Ambil ID ACO utama method index untuk menu (Mencegah fallback ke nilai lama)
            $newMenuAcoId = $this->acosService->findOneByClassAndMethod($className, 'index')['id'] ?? null;

            if ($newMenuAcoId) {
              $menuAcoId = $newMenuAcoId;
            }
          }
        }
      }

      // Update menu dengan data baru & controller final
      $updateData = [
        'menuname'    => $data['menuname'] ?? $menu['menuname'],
        'menu_seq'    => (int) ($data['menu_seq'] ?? $menu['menu_seq']),
        'menu_parent' => (int) ($data['menu_parent'] ?? $menu['menu_parent']),
        'menu_icon'   => strtolower($data['menu_icon'] ?? $menu['menu_icon']),
        'aco_id'      => (int) $menuAcoId,
        'controller'  => $controller, // Pastikan nama controller tetap terjaga
        'modified_by' => $modifiedBy,
      ];

      if (!$this->menuModel->update($data['id'], $updateData)) {
        throw new \Exception("Error updating menu: " . json_encode($this->menuModel->errors()));
      }

      $position = $this->menuModel->getPosition($data['id'], $params);

      $this->db->transCommit();
      return $position;
    } catch (\Throwable $th) {
      $this->db->transRollback();
      log_message('error', $th->getMessage());
      throw $th;
    }
  }

  /**
   * Deletes a menu and its associated ACOS by ID.
   *
   * @param int|string $id
   * @param array $params
   * @return array
   * @throws \Exception
   */
  public function delete($id, array $params)
  {
    $menu = $this->menuModel->find($id);
    if (!$menu) {
      throw new \Exception("Menu dengan ID {$id} tidak ditemukan.");
    }

    $acos = $this->acosService->findOneById($menu['aco_id']);

    $this->db->transBegin();

    try {

      if ($acos) {
        $this->acosService->delete($acos['id']);
      }
  
      $this->menuModel->delete($id);
  
      $position = $this->menuModel->getPosition($id, $params, true);
      $this->db->transCommit();

      return $position;
      
    } catch (\Throwable $th) {
      $this->db->transRollback();
      log_message('error', $th->getMessage());
      throw $th;
    }

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

  /**
   * Retrieves a list of parent menus.
   *
   * @return array
   */
  public function getMenuParent()
  {
    $builder = $this->menuModel->builder();
    $builder->select('menus.id as id, upper(menus.menuname) as menu');
    $builder->where('menus.aco_id', 0);
    return $builder->get()->getResult();
  }

  /**
   * Retrieves a list of available controllers.
   *
   * @return array
   */
  public function getController()
  {
    $inspector = new ControllerInspector();

    // Ambil daftar controller yang sudah dipakai
    // controller ada di table acos
    $usedControllers = $this->menuModel->builder()
      ->select('acos.class as controller')
      ->join('acos', 'acos.id = menus.aco_id', 'left')
      ->where('acos.class IS NOT NULL')
      ->where('acos.class !=', '')
      ->get()
      ->getResultArray();

    // Ubah jadi array 1 dimensi (flat array)
    $usedControllers = array_column($usedControllers, 'controller');

    // Scan semua controller, kecuali yang sudah dipakai
    return $inspector->scanAllControllers($usedControllers);
  }
}
