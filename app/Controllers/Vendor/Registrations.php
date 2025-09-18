<?php

namespace App\Controllers\Vendor;

use App\Controllers\BaseController;
use App\Models\MealSubscriptionModel;

class Registrations extends BaseController
{
    public function daily()
    {
        $today = date('Y-m-d');
        $model = new MealSubscriptionModel();

        // List all registrations for today
        $subs = $model
            ->where('start_date <=', $today)
            ->where('end_date >=', $today)
            ->orderBy('user_id')
            ->findAll();

        return view('vendor/registrations/daily', [
            'subs' => $subs,
            'date' => $today
        ]);
    }

    public function monthly()
    {
        $monthStart = date('Y-m-01');
        $monthEnd   = date('Y-m-t');
        $model = new MealSubscriptionModel();

        // Count registrations per day in this month
        $builder = $model->builder();
        $rows = $builder
            ->select('DATE(start_date) as day, COUNT(*) as cnt')
            ->where("start_date BETWEEN '{$monthStart}' AND '{$monthEnd}'", null, false)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->getResult();

        return view('vendor/registrations/monthly', [
            'rows'       => $rows,
            'monthLabel' => date('F Y'),
        ]);
    }
}
