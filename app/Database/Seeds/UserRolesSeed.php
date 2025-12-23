<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserRolesSeed extends Seeder
{
    public function run()
    {
        $data = [
            [
                'user_id' => '1',
                'role_id' => '1',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'user_id' => '2',
                'role_id' => '2',
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        // Using Query Builder
        // $this->db->table('users')->insert($data);
        $this->db->table('userroles')->insertBatch($data);
    }
}
