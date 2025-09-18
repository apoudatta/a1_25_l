<?php

namespace App\Models;

use CodeIgniter\Model;

class MealTokenModel extends Model
{
    protected $table      = 'meal_tokens';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'user_id',
        'token_code',
        'meal_type_id',
        'meal_date',
        'cafeteria_id',
        'status',
        'source',
        'created_at',
        'redeemed_at',
    ];
}
