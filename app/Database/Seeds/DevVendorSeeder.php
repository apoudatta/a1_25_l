<?php
// app/Database/Seeds/DevVendorSeeder.php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Config\Database;

class DevVendorSeeder extends Seeder
{
    public function run()
    {
        $db      = Database::connect();
        $builder = $db->table('users');

        // Only insert if not already present
        if (! $builder->where('email', 'vendor@bkash.test')->countAllResults()) {
            $builder->insert([
                'employee_id'   => 'VEND001',
                'name'          => 'Dev Vendor',
                'email'         => 'vendor@bkash.test',
                'user_type'     => 'VENDOR',
                'login_method'  => 'LOCAL',
                'status'        => 'ACTIVE',
                'password_hash' => password_hash('VendorPass123', PASSWORD_BCRYPT),
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
