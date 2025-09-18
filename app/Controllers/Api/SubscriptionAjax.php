<?php namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\CutoffTimeModel;
use App\Models\PublicHolidayModel;
use App\Models\MealSubscriptionModel;

class SubscriptionAjax extends BaseController
{
    /** GET /api/subscription-settings */
    public function settings()
    {
        $cutoff = (new CutoffTimeModel())
            ->where('cutoff_date', null)
            ->where('is_active', 1)
            ->first();

        if (! $cutoff) {
            return $this->response
                        ->setStatusCode(404)
                        ->setJSON(['error'=>'No global cutoff settings.']);
        }

        return $this->response->setJSON([
            'cut_off_time'      => $cutoff['cut_off_time'],
            'lead_days'         => (int)$cutoff['lead_days'],
            'max_horizon_days'  => (int)$cutoff['max_horizon_days'],
        ]);
    }

    /** GET /api/public-holidays?month=YYYY-MM */
    public function holidays()
    {
        $month = $this->request->getGet('month');
        $m     = (new PublicHolidayModel())
                  ->where('is_active',1);

        if ($month) {
            $m->where("DATE_FORMAT(holiday_date,'%Y-%m')", $month);
        }

        $dates = array_map(fn($h)=> $h['holiday_date'], $m->findAll());
        return $this->response->setJSON(['holidays'=>$dates]);
    }

    /** POST /api/subscriptions/check-overlap */
    public function checkOverlap()
    {
        $p    = $this->request->getJSON(true);
        $user = session('user_id');
        $subM = new MealSubscriptionModel();

        $conflicts = $subM
           ->where('user_id',       $user)
           ->where('meal_type_id',  $p['meal_type_id'])
           ->groupStart()
             ->where('start_date <=',$p['end_date'])
             ->where('end_date >=',  $p['start_date'])
           ->groupEnd()
           ->findAll();

        return $this->response->setJSON([
            'conflict' => count($conflicts)>0,
            'existing' => $conflicts
        ]);
    }
}
