<?php

namespace App\Models;

use CodeIgniter\Model;

class GuestSubscriptionModel extends Model
{
    protected $table      = 'guest_subscriptions';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'user_id',
        'batch_id',
        'guest_name',
        'phone',
        'guest_type',
        'subscription_date',
        'meal_type_id',
        'cafeteria_id',
		'guest_type',
        'status',
        'remark',
        'approver_remark',
        'otp',
    ];
	 protected $useTimestamps = true;
     protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
