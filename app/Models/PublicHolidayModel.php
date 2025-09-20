<?php

namespace App\Models;

use CodeIgniter\Model;

class PublicHolidayModel extends Model
{
    protected $table            = 'public_holidays';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType    = 'array';
    protected $protectFields = true;

    protected $allowedFields = [
        'holiday_date',
        'description',
        'is_active',
        'created_by',
        'created_at',
        'updated_at',
    ];

    // created_at/updated_at are DATETIME with defaults
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'holiday_date' => 'required|valid_date[Y-m-d]',
        'description'  => 'permit_empty|string|max_length[100]',
        'is_active'    => 'in_list[0,1]',
        'created_by'   => 'permit_empty|integer',
    ];
}
