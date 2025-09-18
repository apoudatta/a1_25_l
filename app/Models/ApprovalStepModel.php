<?php namespace App\Models;

use CodeIgniter\Model;

class ApprovalStepModel extends Model
{
    protected $table      = 'approval_steps';
    protected $primaryKey = 'id';
    // We handle timestamps manually or via flows; no CI auto-timestamps here
    protected $useTimestamps = false;

    protected $allowedFields = [
        'flow_id',          // FK to approval_flows
        'step_order',       // INT: position in sequence
        'approver_role',    // INT FK to roles
        'approver_type',    // ENUM('ROLE','USER','LINE_MANAGER')
        'approver_user_id', // INT FK to users (nullable)
        'fallback_role',    // INT FK to roles for escalation (nullable)
    ];
}