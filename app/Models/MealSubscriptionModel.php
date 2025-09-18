<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\MealSubscriptionDetailModel;

class MealSubscriptionModel extends Model
{
    protected $table         = 'meal_subscriptions';
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
        'approver_remark',
        'created_by',
    ];

    /**
     * Get all per-day detail rows for a given subscription.
     *
     * @param  int  $subscriptionId
     * @return array
     */
    public function getDetails(int $subscriptionId): array
    {
        return (new MealSubscriptionDetailModel())
            ->where('subscription_id', $subscriptionId)
            ->orderBy('subscription_date', 'ASC')
            ->findAll();
    }

    // quick lookup for meal type name (join-free)
    public function getMealTypeName(int $mealTypeId): ?string
    {
        $db = $this->db;
        $row = $db->table('meal_types')->select('name')->where('id', $mealTypeId)->get()->getRowArray();
        return $row['name'] ?? null;
    }
}

