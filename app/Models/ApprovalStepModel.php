<?php

namespace App\Models;

use CodeIgniter\Model;

class ApprovalStepModel extends Model
{
    protected $table            = 'approval_steps';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType       = 'array';
    protected $protectFields    = true;

    // ROLE | USER | LINE_MANAGER
    public const TYPE_ROLE         = 'ROLE';
    public const TYPE_USER         = 'USER';
    public const TYPE_LINE_MANAGER = 'LINE_MANAGER';

    protected $allowedFields = [
        'flow_id',
        'approver_role',
        'approver_user_id',
        'fallback_role',
        'step_order',
        'approver_type',
    ];

    protected $useTimestamps = false;

    protected $validationRules = [
        'flow_id'         => 'required|is_natural_no_zero',
        'approver_role'   => 'permit_empty|integer',
        'approver_user_id'=> 'permit_empty|integer',
        'fallback_role'   => 'permit_empty|integer',
        'step_order'      => 'required|is_natural_no_zero',
        'approver_type'   => 'required|in_list[ROLE,USER,LINE_MANAGER]',
    ];
}
