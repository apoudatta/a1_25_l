<?php namespace App\Models;

use CodeIgniter\Model;

class MealTypeModel extends Model
{
    protected $table      = 'meal_types';
    protected $primaryKey = 'id';
    protected $allowedFields = [
      'name', 'description', 'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
