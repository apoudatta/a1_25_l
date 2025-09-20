<?php

namespace App\Models;

use CodeIgniter\Model;

class RamadanConfigModel extends Model
{
    protected $table            = 'ramadan_config';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType    = 'array';
    protected $protectFields = true;

    protected $allowedFields = [
        'year',
        'start_date',
        'end_date',
    ];

    protected $useTimestamps = false;

    protected $validationRules = [
        'year'       => 'required|integer',
        'start_date' => 'required|valid_date[Y-m-d]',
        'end_date'   => 'required|valid_date[Y-m-d]',
    ];
}
