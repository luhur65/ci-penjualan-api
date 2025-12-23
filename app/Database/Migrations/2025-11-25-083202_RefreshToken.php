<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RefreshToken extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'token_hash' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('refreshtokens');
    }

    public function down()
    {
        $this->forge->dropTable('refreshtokens');
    }
}
