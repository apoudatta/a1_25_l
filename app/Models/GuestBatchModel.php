<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\GuestSubscriptionModel;

class GuestBatchModel extends Model
{
    protected $table         = 'guest_batches';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'user_id',
        'meal_type_id',
        'cafeteria_id',
        'start_date',
        'end_date',
        'status',
        'remark',
        'subscription_type'
    ];

    /**
     * Get all per-day detail rows for a given subscription.
     *
     * @param  int  $subscriptionId
     * @return array
     */
    public function getDetails(int $subscriptionId): array
    {
        return (new GuestSubscriptionModel())
            ->where('subscription_id', $subscriptionId)
            ->orderBy('subscription_date', 'ASC')
            ->findAll();
    }
}

