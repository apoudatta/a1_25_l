<?php

namespace App\Controllers\Vendor;

use App\Controllers\BaseController;
use App\Models\MealTokenModel;
use App\Models\MealTypeModel;

class Meals extends BaseController
{
    public function index()
    {
        $today = date('Y-m-d');
        $tokenModel   = new MealTokenModel();
        $mealTypeModel = new MealTypeModel();

        // Get counts per meal type
        $builder = $tokenModel->builder();
        $rows = $builder
            ->select('meal_type_id, COUNT(*) as cnt')
            ->where('meal_date', $today)
            ->groupBy('meal_type_id')
            ->get()
            ->getResult();

        $data = [];
        foreach ($rows as $r) {
            $mt = $mealTypeModel->find($r->meal_type_id);
            $data[] = [
                'type' => $mt ? $mt['name'] : 'Unknown',
                'count'=> $r->cnt
            ];
        }

        return view('vendor/meals/index', ['data' => $data, 'date' => $today]);
    }
}
