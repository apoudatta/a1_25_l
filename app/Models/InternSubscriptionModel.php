<?php

namespace App\Models;

use CodeIgniter\Model;

class InternSubscriptionModel extends Model
{
    protected $table      = 'intern_subscriptions';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'batch_id',
        'meal_type_id',
        'user_reference_id',
        'intern_name',
        'phone',
        'subscription_date',
        'employment_type_id',
        'cafeteria_id',
        'status',
        'remark',
        'approver_remark',
        'otp',
    ];
	public    $useTimestamps = false;
}
