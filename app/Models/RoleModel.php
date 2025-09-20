<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table            = 'roles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType    = 'array';
    protected $protectFields = true;

    protected $allowedFields = [
        'name',
        'description',
    ];

    protected $useTimestamps = false;

    protected $validationRules = [
        'name'        => 'required|string|max_length[50]',
        'description' => 'permit_empty|string',
    ];
}
