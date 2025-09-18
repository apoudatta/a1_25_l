<?php

namespace App\Controllers\Vendor;

use App\Controllers\BaseController;
use App\Models\MealSubscriptionModel;

class OrderHistory extends BaseController
{
    public function index()
    {
        $model = new MealSubscriptionModel();

        // Read filter inputs or default to last 7 days
        $start = $this->request->getGet('start_date') 
               ?? date('Y-m-d', strtotime('-6 days'));
        $end   = $this->request->getGet('end_date')
               ?? date('Y-m-d');

        // Fetch aggregated counts per day and meal type
        $builder = $model->builder();
        $builder->select('DATE(start_date) AS day, meal_type_id, COUNT(*) AS cnt')
                ->where('start_date >=', $start)
                ->where('end_date <=',   $end)
                ->groupBy('day, meal_type_id')
                ->orderBy('day', 'DESC');
        $rows = $builder->get()->getResult();

        return view('vendor/order_history/index', [
            'rows'      => $rows,
            'start'     => $start,
            'end'       => $end,
        ]);
    }

    public function export()
    {
        $start = $this->request->getPost('start_date');
        $end   = $this->request->getPost('end_date');

        // Reuse same query
        $model   = new MealSubscriptionModel();
        $builder = $model->builder();
        $builder->select('DATE(start_date) AS day, meal_type_id, COUNT(*) AS cnt')
                ->where('start_date >=', $start)
                ->where('end_date <=',   $end)
                ->groupBy('day, meal_type_id')
                ->orderBy('day', 'DESC');
        $rows = $builder->get()->getResult();

        // Build CSV
        $csv  = "Date,Meal Type ID,Count\n";
        foreach ($rows as $r) {
            $csv .= "{$r->day},{$r->meal_type_id},{$r->cnt}\n";
        }

        return $this->response
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="order_history_{$start}_to_{$end}.csv"')
            ->setBody($csv);
    }
}
