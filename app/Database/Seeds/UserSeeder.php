<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $faker = \Faker\Factory::create();

        $data = [];

        $password = password_hash('password123', PASSWORD_BCRYPT);

        for ($i = 0; $i < 10000; $i++) {
            $data[] = [
                'fullname' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'username' => $faker->unique()->userName,
                'password' => $password,
                'status_aktif' => $faker->randomElement(['1', '2']),
                'modified_by' => 'Seeder',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

        }

        $this->db->table('users')->insertBatch($data);
    }
}
