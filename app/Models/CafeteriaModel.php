<?php

namespace App\Models;

use CodeIgniter\Model;

class CafeteriaModel extends Model
{
    protected $table            = 'cafeterias';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType       = 'array';
    protected $protectFields    = true;

    protected $allowedFields = [
        'name',
        'location',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name'      => 'required|string|max_length[100]',
        'location'  => 'permit_empty|string|max_length[255]',
        'is_active' => 'in_list[0,1]',
    ];
}
