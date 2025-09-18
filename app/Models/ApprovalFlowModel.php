<?php namespace App\Models;

use CodeIgniter\Model;

class ApprovalFlowModel extends Model
{
    protected $table         = 'approval_flows';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'meal_type_id',   // FK to meal_types
        'user_type',      // ENUM('EMPLOYEE','GUEST','INTERN')
        'is_active',      // TINYINT(1)
        'type',           // ENUM('MANUAL','AUTO')
        'effective_date', // DATE
    ];
}