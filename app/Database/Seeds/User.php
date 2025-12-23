<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class User extends Seeder
{
    public function run()
    {
        $data = [
            [
                'fullname' => 'Admin User',
                'email'    => 'dharmataspusat@gmail.com',
                'username' => 'admin',
                'password' => password_hash('admin123', PASSWORD_BCRYPT),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'fullname' => 'Dharma Tas Pusat',
                'email'    => 'dharmataspusat@gmail.com',
                'username' => 'dharma',
                'password' => password_hash('123456', PASSWORD_BCRYPT),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        // Using Query Builder
        // $this->db->table('users')->insert($data);
        $this->db->table('users')->insertBatch($data);
    }
}
