<?php

namespace App\Models;

use CodeIgniter\Model;

class MealReferenceModel extends Model
{
    protected $table            = 'meal_reference';
    protected $primaryKey       = 'id';

    protected $useAutoIncrement = true;

    protected $returnType    = 'array';
    protected $protectFields = true;

    protected $allowedFields = [
        'subs_id',
        'ref_id',
        'ref_name',
        'ref_phone',
        'otp',
        'created_at',
    ];

    // Only created_at exists in the table; let CI fill it on insert
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = null;

    protected $validationRules = [
        'subs_id'   => 'required|integer',
        'ref_id'    => 'permit_empty|string|max_length[15]',
        'ref_name'  => 'permit_empty|string|max_length[50]',
        'ref_phone' => 'permit_empty|string|max_length[15]',
        'otp'       => 'permit_empty|integer',
    ];
}
