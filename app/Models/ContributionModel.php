<?php namespace App\Models;

use CodeIgniter\Model;

class ContributionModel extends Model
{
    protected $table         = 'meal_contributions';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'meal_type_id',
        'user_type',
        'company_contribution',
        'user_contribution',
        'base_price',
        'company_tk',
        'user_tk',
        //'cafeteria_id',
        'effective_date',
    ];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
