<?php

namespace App\Models;

use CodeIgniter\Model;

class EmploymentTypeModel extends Model
{
    protected $table         = 'employment_types';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'name',
        'description',
        'is_active',
    ];

    // Validate "name" and keep it unique (by name) while editing
    protected $validationRules = [
        'name'       => 'required|min_length[2]|max_length[100]',
        'is_active'  => 'permit_empty|in_list[0,1]',
        'description'=> 'permit_empty|string',
    ];

    protected $validationMessages = [
        'name' => [
            'required'  => 'Name is required.',
            'is_unique' => 'This name already exists.',
        ],
    ];
}
