<?php

namespace App\Models;

use CodeIgniter\Model;

class ContributionModel extends Model
{
    protected $table            = 'meal_contributions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType    = 'array';
    protected $protectFields = true;

    // Make sure every column you write is listed here
    protected $allowedFields = [
        'meal_type_id',
        'emp_type_id',
        'cafeteria_id',
        'base_price',
        'company_tk',
        'user_tk',
        'is_active',
        // include if your table has it; otherwise harmless to leave out
        'effective_date',
        // DO NOT include updated_at (table doesn't have it)
        // created_at is set by DB default; we don’t need to write it
    ];

    // IMPORTANT: this table does not have updated_at
    protected $useTimestamps = false;   // turn off auto timestamps
    protected $createdField  = 'created_at';
    protected $updatedField  = null;

    protected $validationRules = [
        'meal_type_id'   => 'required|is_natural_no_zero',
        'emp_type_id'    => 'permit_empty|integer',
        'cafeteria_id'   => 'permit_empty|integer',
        'base_price'     => 'required|decimal',
        'company_tk'     => 'required|decimal',
        'user_tk'        => 'required|decimal',
        'is_active'      => 'in_list[0,1]',
        'effective_date' => 'permit_empty|valid_date[Y-m-d]',
    ];
}
