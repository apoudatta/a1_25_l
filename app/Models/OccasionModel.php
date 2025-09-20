<?php

namespace App\Models;

use CodeIgniter\Model;

class OccasionModel extends Model
{
    protected $table            = 'occasions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType    = 'array';
    protected $protectFields = true;

    protected $allowedFields = [
        'tag',
        'name',
        'occasion_date',
    ];

    protected $useTimestamps = false;

    protected $validationRules = [
        'tag'           => 'required|string|max_length[100]',
        'name'          => 'required|string|max_length[50]',
        'occasion_date' => 'required|valid_date[Y-m-d]',
    ];
}
