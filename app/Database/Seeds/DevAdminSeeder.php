<?php
// app/Database/Seeds/DevAdminSeeder.php
namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Config\Database;

class DevAdminSeeder extends Seeder
{
    public function run()
    {
        $db = Database::connect();
        $builder = $db->table('users');

        // Only insert if not exists
        if (! $builder->where('email','dev@bkash.test')->countAllResults()) {
            $builder->insert([
                'employee_id'   => 'DEV001',
                'name'          => 'Dev Admin',
                'email'         => 'dev@bkash.test',
                'user_type'     => 'ADMIN',
                'login_method'  => 'LOCAL',
                'status'        => 'ACTIVE',
                'password_hash' => password_hash('DevPass123', PASSWORD_BCRYPT),
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
