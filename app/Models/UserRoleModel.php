<?php

namespace App\Models;

use CodeIgniter\Model;

class UserRoleModel extends Model
{
    protected $table            = 'user_roles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType    = 'array';
    protected $protectFields = true;

    protected $allowedFields = [
        'user_id',  // FK -> users.id
        'role_id',  // FK -> roles.id
    ];

    protected $useTimestamps = false;

    protected $validationRules = [
        'user_id' => 'required|is_natural_no_zero',
        'role_id' => 'required|is_natural_no_zero',
    ];
}
