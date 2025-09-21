<?php namespace App\Models;

use CodeIgniter\Model;

class MealSubscriptionDetailModel extends Model
{
    protected $table         = 'meal_subscription_details';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'user_id',
        'subscription_id',
        'subscription_date',
        'status',
        'meal_type_id',
        'cafeteria_id',
        'remark',
        'approver_remark',
        'created_by',
        'unsubs_by',
    ];

    public function getSubscriptionDetailsByMealSubscriptions(int $subscriptionId)
    {
        return $this->select('
                                meal_subscriptions.*, 
                                meal_subscription_details.subscription_date, 
                                meal_subscription_details.status
                            ')
                    ->join('meal_subscriptions','meal_subscriptions.id = meal_subscription_details.subscription_id ')
                    ->where('meal_subscription_details.subscription_id',$subscriptionId)
                    ->findAll();
    }
}
