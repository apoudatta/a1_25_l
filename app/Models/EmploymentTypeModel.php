<?php

namespace App\Models;

use CodeIgniter\Model;

class EmploymentTypeModel extends Model
{
    protected $table            = 'employment_types';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType       = 'array';
    protected $protectFields    = true;

    protected $allowedFields = [
        'name',
        'description',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name'      => 'required|string|max_length[100]',
        'description'=> 'permit_empty|string',
        'is_active' => 'in_list[0,1]',
    ];
}
