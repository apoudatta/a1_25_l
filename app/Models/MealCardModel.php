<?php
namespace App\Models;

use CodeIgniter\Model;

class MealCardModel extends Model
{
    protected $table         = 'meal_card';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id','employee_id','card_id','card_code','status'];
    protected $useTimestamps = true; // maps to created_at / updated_at

    protected $validationRules = [
        'user_id'     => 'permit_empty|is_natural_no_zero',
        'employee_id' => 'permit_empty|max_length[20]',
        'card_code'   => 'required|max_length[64]',
        'status'      => 'required|in_list[ACTIVE,INACTIVE]',
    ];

    protected $validationMessages = [
        'card_code' => [
            'required'   => 'Card code is required.',
            'max_length' => 'Card code must be at most 64 characters.',
        ],
        'status' => [
            'required' => 'Status is required.',
            'in_list'  => 'Status must be ACTIVE or INACTIVE.',
        ],
    ];
}
