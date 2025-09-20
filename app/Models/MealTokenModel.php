<?php

namespace App\Models;

use CodeIgniter\Model;

class MealTokenModel extends Model
{
    protected $table            = 'meal_tokens';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType    = 'array';
    protected $protectFields = true;

    protected $allowedFields = [
        'user_id',        // FK -> users.id
        'subs_id',        // FK -> meal_subscriptions.id (logical)
        'meal_type_id',   // FK -> meal_types.id
        'emp_type_id',    // FK -> employment_types.id (legacy 0 may appear as "ALL")
        'cafeteria_id',   // FK -> cafeterias.id
        'token_code',     // unique
        'meal_date',
        'created_at',
    ];

    // Only created_at exists (DATETIME)
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = null;

    protected $validationRules = [
        'user_id'      => 'required|is_natural_no_zero',
        'subs_id'      => 'required|integer',
        'meal_type_id' => 'required|is_natural_no_zero',
        'emp_type_id'  => 'permit_empty|integer', // change to is_natural_no_zero if you migrate 0 => NULL
        'cafeteria_id' => 'required|is_natural_no_zero',
        'token_code'   => 'required|string|max_length[100]|is_unique[meal_tokens.token_code,id,{id}]',
        'meal_date'    => 'required|valid_date[Y-m-d]',
    ];
}
