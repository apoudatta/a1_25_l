<?php

namespace App\Models;

use CodeIgniter\Model;

class MealApprovalModel extends Model
{
    protected $table            = 'meal_approvals';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType       = 'array';
    protected $protectFields    = true;

    // PENDING | ACTIVE | REJECTED
    public const STATUS_PENDING  = 'PENDING';
    public const STATUS_ACTIVE   = 'ACTIVE';
    public const STATUS_REJECTED = 'REJECTED';

    protected $allowedFields = [
        'subs_id',
        'approver_role',
        'approver_user_id',
        'approved_by',
        'approval_status',
        'approved_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'subs_id'        => 'required|is_natural_no_zero',
        'approver_role'  => 'permit_empty|integer',
        'approver_user_id'=> 'permit_empty|integer',
        'approved_by'    => 'permit_empty|integer',
        'approval_status'=> 'required|in_list[PENDING,ACTIVE,REJECTED]',
        'approved_at'    => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];
}
