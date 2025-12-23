<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoleSeed extends Seeder
{
    public function run()
    {
        $data = [
            [
                'rolename' => 'Admin',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'rolename' => 'User',
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        // Using Query Builder
        // $this->db->table('users')->insert($data);
        $this->db->table('roles')->insertBatch($data);
    }
}
