<?php

namespace App\Services;

use App\Models\Acos;

/**
 * Service class for handling Acos business logic.
 */
class AcosService
{
  protected $acosModel;

  public function __construct()
  {
    $this->acosModel = new Acos();
  }

  /**
   * Get all ACOS.
   *
   * @param array $requestData
   * @return array
   */
  public function getAllAcos($requestData)
  {
    $acos = $this->acosModel->setRequestParameters($requestData)->getAll();

    return [
      'data' => $acos['data'],
      'attributes' => [
        'totalRows' => $acos['totalRows'],
        'totalPages' => $acos['totalPages']
      ]
    ];
  }

  /**
   * Find one ACOS by ID.
   *
   * @param int|string $id
   * @return array
   */
  public function findOneById($id)
  {
    return $this->acosModel->where('id', $id)->first();
  }

  /**
   * Find one ACOS by class and method.
   *
   * @param string $class
   * @param string $method
   * @return array
   */
  public function findOneByClassAndMethod($class, $method)
  {
    return $this->acosModel->where('class', $class)->where('method', $method)->orderBy('id', 'asc')->first();
  }

  /**
   * Stores a new ACOS.
   *
   * @param array $data
   * @return bool
   * @throws \Exception
   */
  public function create(array $data)
  {
    if (!$this->acosModel->insert($data)) {
      throw new \Exception("Error storing ACOS.");
    }

    return true;
  }

  /**
   * Updates an existing ACOS.
   *
   * @param array $data
   * @return bool
   * @throws \Exception
   */
  public function update(array $data)
  {
    if (!$this->acosModel->update($data['id'], $data)) {
      throw new \Exception("Error updating ACOS.");
    }

    return true;
  }

  /**
   * Deletes an ACOS by ID.
   *
   * @param int|string $id
   * @return bool
   * @throws \Exception
   */
  public function delete($id)
  {
    if (!$this->acosModel->delete($id)) {
      throw new \Exception("Error deleting ACOS.");
    }

    return true;
  }
}
