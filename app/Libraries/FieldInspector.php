<?php
/**
* File ini tidak dipakai dimana pun, karena sudah diterapkan di CustomModel.php
*/
namespace App\Libraries;

use CodeIgniter\Model;
use Config\Database;

class FieldInspector
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Ambil field length dari model yang diberikan
     */
    public function getFieldLength(Model $model): array
    {
        $table = $model->getTable();

        $query   = $this->db->query("SHOW COLUMNS FROM `{$table}`");
        $columns = $query->getResult();

        $data = [];

        foreach ($columns as $column) {
            preg_match('/\((.*?)\)/', $column->Type, $matches);
            $length = isset($matches[1]) ? (int)$matches[1] : null;

            $data[$column->Field] = $length;
        }

        return $data;
    }
}
