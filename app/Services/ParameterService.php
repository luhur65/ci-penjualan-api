<?php

namespace App\Services;

use App\Models\Parameter;

class ParameterService
{

  protected $parameterModel;

  public function __construct()
  {
    $this->parameterModel = new Parameter();
  }

  /**
   * Get all parameters.
   *
   * @param array $requestData
   * @return array
   */
  public function getAllParameters($requestData)
  {
    $parameters = $this->parameterModel->setRequestParameters($requestData)->getAll();

    return [
      'data' => $parameters['data'],
      'attributes' => [
        'totalRows' => $parameters['totalRows'],
        'totalPages' => $parameters['totalPages']
      ]
    ];
  }

  public function getLookup($grp, $subgrp = null)
  {
    $cache = cache();
    $cacheKey = 'lookup_' . md5($grp . $subgrp);

    // cek cache dulu
    $cached = $cache->get($cacheKey);
    if ($cached !== null) {
      return $cached;
    }

    $builder = $this->parameterModel->where('grp', $grp);
    if ($subgrp !== null) {
      $builder->where('subgrp', $subgrp);
    }

    $data = $builder->findAll();
    $result = [];

    foreach ($data as $item) {
      $memo = json_decode($item['memo'], true);

      $result[] = [
        'id'        => $item['id'],
        'param'     => $item['id'],
        'parameter' => $memo['MEMO'] ?? $item['text'],
        'text'      => $item['text'],
        'default'   => $item['default'] ?? 'TIDAK'
      ];
    }

    // simpan cache 24 jam
    $cache->save($cacheKey, $result, 86400);

    return $result;
  }

  public function getComboByMemo($grp, $subgrp = null)
  {
    $cache = cache();
    $cacheKey = 'combo_' . md5($grp . $subgrp);

    // cek cache dulu
    $cached = $cache->get($cacheKey);
    if ($cached !== null) {
      return $cached;
    }

    $builder = $this->parameterModel->where('grp', $grp);
    if ($subgrp !== null) {
      $builder->where('subgrp', $subgrp);
    }

    $data = $builder->findAll();
    $result = [];

    foreach ($data as $item) {
      $memo = json_decode($item['memo'], true);

      $result[] = [
        'id'        => $item['id'],
        'param'     => $item['id'],
        'parameter' => $memo['MEMO'] ?? $item['text'],
        'text'      => $item['text'],
        'default'   => $item['default'] ?? 'TIDAK'
      ];
    }

    // simpan cache 24 jam
    $cache->save($cacheKey, $result, 86400);

    return $result;
  }

  public function getParameterById($id)
  {
    return $this->parameterModel->find($id);
  }

  /**
   * Stores a new parameter.
   *
   * @param array $data
   * @param array $params
   * @return array
   * @throws \Exception
   */
  public function create(array $data, array $params = []): array
  {
    $this->parameterModel->db->transBegin();

    try {
      if (!$this->parameterModel->insert($data)) {
        throw new \Exception("Error storing parameter.");
      }

      $newId = $this->parameterModel->getInsertID();
      helper('audit');
      audit_log('parameters', 'CREATE', $newId, null, $data);
      $position = $this->parameterModel->getPosition($newId, $params);

      $this->parameterModel->db->transCommit();

      return $position;
    } catch (\Throwable $th) {
      $this->parameterModel->db->transRollback();
      log_message('error', $th->getMessage());
      throw $th;
    }
  }

  /**
   * Updates an existing parameter.
   *
   * @param array $data
   * @param array $params
   * @return array
   * @throws \Exception
   */
  public function update(array $data, array $params = []): array
  {
    $this->parameterModel->db->transBegin();

    try {
      helper('audit');
      $oldData = $this->parameterModel->find($data['id']);

      if (!$this->parameterModel->update($data['id'], $data)) {
        throw new \Exception("Error updating parameter.");
      }

      audit_log('parameters', 'UPDATE', $data['id'], $oldData, $data);

      $position = $this->parameterModel->getPosition($data['id'], $params);

      $this->parameterModel->db->transCommit();

      return $position;
    } catch (\Throwable $th) {
      $this->parameterModel->db->transRollback();
      log_message('error', $th->getMessage());
      throw $th;
    }
  }

  /**
   * Deletes a parameter by ID.
   *
   * @param int|string $id
   * @param array $params
   * @return array
   * @throws \Exception
   */
  public function delete($id, array $params = []): array
  {
    $this->parameterModel->db->transBegin();

    try {
      $position = $this->parameterModel->getPosition($id, $params, true);

      helper('audit');
      $oldData = $this->parameterModel->find($id);

      if (!$this->parameterModel->delete($id)) {
        throw new \Exception("Error deleting parameter.");
      }

      audit_log('parameters', 'DELETE', $id, $oldData, null);

      $this->parameterModel->db->transCommit();

      return $position;
    } catch (\Throwable $th) {
      $this->parameterModel->db->transRollback();
      log_message('error', $th->getMessage());
      throw $th;
    }
  }
}
