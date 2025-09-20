<?php

namespace App\Models;

use CodeIgniter\Model;

class MealCardModel extends Model
{
    protected $table            = 'meal_cards';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType       = 'array';
    protected $protectFields    = true;

    public const STATUS_ACTIVE   = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    protected $allowedFields = [
        'user_id',
        'employee_id',
        'card_code',   // unique
        'status',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true; // created_at/updated_at are TIMESTAMP columns
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'user_id'     => 'permit_empty|integer',
        'employee_id' => 'permit_empty|string|max_length[20]',
        'card_code'   => 'required|string|max_length[64]|is_unique[meal_cards.card_code,id,{id}]',
        'status'      => 'required|in_list[ACTIVE,INACTIVE]',
    ];
}
