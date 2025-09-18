<?php

namespace App\Controllers\Vendor;

use App\Controllers\BaseController;
use App\Models\MealSubscriptionModel;
use App\Models\MealTokenModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $today = date('Y-m-d');

        // Active registrations for today
        $subModel = new MealSubscriptionModel();
        $registrations = $subModel
            ->where('start_date <=', $today)
            ->where('end_date >=', $today)
            ->countAllResults();

        // Redeemed tokens for today
        $tokenModel = new MealTokenModel();
        $redeemed = $tokenModel
            ->where('meal_date', $today)
            ->where('status', 'REDEEMED')
            ->countAllResults();

        // Pending tokens (generated but not yet redeemed)
        $pending = $tokenModel
            ->where('meal_date', $today)
            ->where('status', 'GENERATED')
            ->countAllResults();

        return view('vendor/dashboard', [
            'registrations' => $registrations,
            'redeemed'      => $redeemed,
            'pending'       => $pending,
        ]);
    }
}
