<?php

namespace App\Models;

use CodeIgniter\Model;

class MealCostModel extends Model
{
    protected $table            = 'meal_costs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType    = 'array';
    protected $protectFields = true;

    // Note: no created_at here; DB will set it automatically
    protected $allowedFields = [
        'cafeteria_id',    // nullable
        'meal_type_id',
        'base_price',
        'effective_date',
        'is_active',
    ];

    // Table has no updated_at; let DB default handle created_at
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = null;

    protected $validationRules = [
        'cafeteria_id'   => 'permit_empty|integer',
        'meal_type_id'   => 'required|is_natural_no_zero',
        'base_price'     => 'required|decimal',
        'effective_date' => 'required|valid_date[Y-m-d]',
        'is_active'      => 'in_list[0,1]',
    ];
}
