<?php namespace App\Models;

use CodeIgniter\Model;

class MealApprovalModel extends Model
{
    protected $table         = 'meal_approvals';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'subscription_type',   // ENUM('EMPLOYEE','GUEST','INTERN')
        'subscription_id',     // INT: FK to the subscription table
        'step_id',             // INT: FK to approval_steps
        'approver_role',       // INT: which role to notify (nullable)
        'approver_user_id',    // INT: specific user (nullable)
        'approval_status',     // ENUM('PENDING','APPROVED','REJECTED')
        'approved_by',         // INT: user who clicked approve (nullable)
        'approved_at',         // DATETIME
        'remarks',             // TEXT: comments
    ];
}