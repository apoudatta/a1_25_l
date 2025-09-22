<?php

namespace App\Controllers\Vendor;

use App\Controllers\BaseController;

class OrderHistory extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();

        // Read filter inputs or default to last 7 days (Asia/Dhaka)
        $tz     = new \DateTimeZone('Asia/Dhaka');
        $today  = new \DateTime('now', $tz);
        $start  = $this->request->getGet('start_date') ?: $today->modify('-6 days')->format('Y-m-d');
        $end    = $this->request->getGet('end_date')   ?: (new \DateTime('now', $tz))->format('Y-m-d');

        // Ensure start <= end
        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        // Aggregate counts per day and meal type from meal_subscriptions
        // Excluding CANCELLED to reflect actual registered/served/no_show volumes
        $rows = $db->table('meal_subscriptions as ms')
            ->select('DATE(ms.subs_date) AS day, COUNT(*) AS cnt, meal_types.name AS meal_type_name', false)
            ->join('meal_types', 'meal_types.id = ms.meal_type_id', 'left')
            ->where('ms.subs_date >=', $start)
            ->where('ms.subs_date <=', $end)
            ->whereIn('status', ['ACTIVE', 'REDEEMED']) // exclude CANCELLED
            // ->where('status', 'REDEEMED') // ← if you ever need only consumed history
            ->groupBy('day, meal_type_id')
            ->orderBy('day', 'DESC')
            ->get()
            ->getResult();

        return view('vendor/order_history/index', [
            'rows'  => $rows,
            'start' => $start,
            'end'   => $end,
        ]);
    }

    public function export()
    {
        $db = \Config\Database::connect();

        // Read POST range; if missing, default to last 7 days
        $tz     = new \DateTimeZone('Asia/Dhaka');
        $today  = new \DateTime('now', $tz);
        $start  = $this->request->getPost('start_date') ?: $today->modify('-6 days')->format('Y-m-d');
        $end    = $this->request->getPost('end_date')   ?: (new \DateTime('now', $tz))->format('Y-m-d');

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        // Same query as index()
        $rows = $db->table('meal_subscriptions')
            ->select('DATE(subs_date) AS day, meal_type_id, COUNT(*) AS cnt', false)
            ->where('subs_date >=', $start)
            ->where('subs_date <=', $end)
            ->whereIn('status', ['ACTIVE', 'PENDING', 'REDEEMED', 'NO_SHOW'])
            ->groupBy('day, meal_type_id')
            ->orderBy('day', 'DESC')
            ->get()
            ->getResult();

        // Build CSV (same columns as before)
        $csv = "Date,Meal Type ID,Count\n";
        foreach ($rows as $r) {
            $csv .= "{$r->day},{$r->meal_type_id},{$r->cnt}\n";
        }

        return $this->response
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="order_history_{$start}_to_{$end}.csv"')
            ->setBody($csv);
    }
}
