<?php

namespace App\Models;

use CodeIgniter\Model;

class MealSubscriptionModel extends Model
{
    protected $table            = 'meal_subscriptions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType       = 'array';
    protected $protectFields    = true;

    // ACTIVE | CANCELLED | REDEEMED | NO_SHOW | PENDING
    public const STATUS_ACTIVE    = 'ACTIVE';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_REDEEMED  = 'REDEEMED';
    public const STATUS_NO_SHOW   = 'NO_SHOW';
    public const STATUS_PENDING   = 'PENDING';

    protected $allowedFields = [
        'user_id',
        'meal_type_id',
        'emp_type_id',   // FK -> employment_types.id (legacy 0 may exist)
        'cafeteria_id',
        'subs_date',
        'status',
        'price',
        'created_by',
        'unsubs_by',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'user_id'      => 'required|integer',
        'meal_type_id' => 'required|is_natural_no_zero',
        'emp_type_id'  => 'permit_empty|integer',
        'cafeteria_id' => 'required|integer',
        'subs_date'    => 'required|valid_date[Y-m-d]',
        'status'       => 'required|in_list[ACTIVE,CANCELLED,REDEEMED,NO_SHOW,PENDING]',
        'price'        => 'permit_empty|decimal',
        'created_by'   => 'permit_empty|integer',
        'unsubs_by'    => 'permit_empty|integer',
    ];
}
