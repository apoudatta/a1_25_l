<?php namespace App\Models;

use CodeIgniter\Model;

class MealCostModel extends Model
{
    protected $table         = 'meal_costs';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'cafeteria_id',
        'meal_type_id',
        'base_price',
        'effective_date',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
