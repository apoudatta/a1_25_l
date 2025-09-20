<?php

namespace App\Models;

use CodeIgniter\Model;

class ApprovalFlowModel extends Model
{
    protected $table            = 'approval_flows';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType       = 'array';
    protected $protectFields    = true;

    // MANUAL | AUTO
    public const TYPE_MANUAL = 'MANUAL';
    public const TYPE_AUTO   = 'AUTO';

    protected $allowedFields = [
        'meal_type_id',
        'emp_type_id',     // FK -> employment_types.id (note: some rows may contain 0 if you kept legacy semantics)
        'is_active',
        'type',
        'effective_date',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'meal_type_id'   => 'required|is_natural_no_zero',
        'emp_type_id'    => 'permit_empty|integer', // if you migrate 0=>NULL, change to 'permit_empty|is_natural_no_zero'
        'is_active'      => 'in_list[0,1]',
        'type'           => 'required|in_list[MANUAL,AUTO]',
        'effective_date' => 'permit_empty|valid_date[Y-m-d]',
    ];
}
