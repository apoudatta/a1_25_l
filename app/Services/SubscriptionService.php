<?php namespace App\Services;

use App\Models\MealSubscriptionModel;
use App\Models\GuestSubscriptionModel;
use App\Models\InternBatchModel;
use CodeIgniter\I18n\Time;
use Exception;

class SubscriptionService
{
    protected $mealModel;
    protected $guestModel;
    protected $internBatchModel;

    public function __construct()
    {
        $this->mealModel         = new MealSubscriptionModel();
        $this->guestModel        = new GuestSubscriptionModel();
        $this->internBatchModel  = new InternBatchModel();
    }

    /**
     * Validate that start <= end and not in blackout dates
     */
    protected function validateDateRange(string $start, string $end)
    {
        $startDate = Time::parse($start)->startOfDay();
        $endDate   = Time::parse($end)->endOfDay();

        if ($startDate->isAfter($endDate)) {
            throw new Exception('Start date must be <= end date');
        }

        // TODO: check against cutoff times, holidays, Ramadan dates
    }

    /**
     * Create an employee subscription
     */
    public function createEmployee(array $data)
    {
        // expected keys: user_id, subscription_type, start_date, end_date
        $this->validateDateRange($data['start_date'], $data['end_date']);
        
        $data['status'] = 'ACTIVE';

        return $this->mealModel->insert($data);
    }

    /**
     * Cancel a subscription immediately
     */
    public function cancelEmployee(int $id)
    {
        $subscription = $this->mealModel->find($id);
        if (! $subscription) {
            throw new Exception('Subscription not found');
        }

        return $this->mealModel->update($id, ['status' => 'CANCELLED']);
    }

    /**
     * Create a guest subscription
     */
    public function createGuest(array $data)
    {
        // expected keys: employee_id, guest_name, meal_date
        $date = Time::parse($data['meal_date'])->format('Y-m-d');

        // Enforce 1 meal per day
        $exists = $this->guestModel
            ->where('employee_id', $data['employee_id'])
            ->where('meal_date', $date)
            ->countAllResults() > 0;
        if ($exists) {
            throw new Exception('Guest meal already booked for this date');
        }

        // TODO: validate date against rules

        return $this->guestModel->insert(array_merge($data, ['meal_date' => $date]));
    }

    /**
     * Bulk create interns via batch
     */
    public function bulkCreateInterns(int $batchId, array $entries)
    {
        // entries: array of ['intern_name','cafeteria_id','start_date','end_date']
        foreach ($entries as $row) {
            $this->validateDateRange($row['start_date'], $row['end_date']);
            
            $this->internBatchModel->insert([
                'batch_id'     => $batchId,
                'intern_name'  => $row['intern_name'],
                'cafeteria_id' => $row['cafeteria_id'],
                'start_date'   => $row['start_date'],
                'end_date'     => $row['end_date'],
            ]);
        }

        return true;
    }
}