<?php

namespace App\Controllers\Vendor;

use App\Controllers\BaseController;

class Registrations extends BaseController
{
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function daily()
    {
        // Today in Asia/Dhaka, matching business day boundaries
        $today = (new \DateTime('now', new \DateTimeZone('Asia/Dhaka')))->format('Y-m-d');

        // All registrations for today (exclude cancelled)
        $subs = $this->db->table('meal_subscriptions ms')
            ->select('
                ms.id, ms.subs_date, ms.status,
                mt.name  AS meal_type_name,
                cf.name  AS cafeteria_name
            ')
            ->join('meal_types   mt', 'mt.id = ms.meal_type_id',   'left')
            ->join('cafeterias   cf', 'cf.id = ms.cafeteria_id',   'left')
            ->where('ms.subs_date', $today)
            ->whereIn('ms.status', ['ACTIVE', 'REDEEMED'])
            ->orderBy('ms.user_id', 'ASC')
            ->get()
            ->getResultArray();

        return view('vendor/registrations/daily', [
            'subs' => $subs,
            'date' => $today,
        ]);
    }

    public function monthly()
    {
        // Month range in Asia/Dhaka
        $tz         = new \DateTimeZone('Asia/Dhaka');
        $now        = new \DateTime('now', $tz);
        $monthStart = (clone $now)->modify('first day of this month')->format('Y-m-d');
        $monthEnd   = (clone $now)->modify('last day of this month')->format('Y-m-d');

        // Count registrations per day this month (exclude cancelled)
        $rows = $this->db->table('meal_subscriptions AS ms')
            // ->select('DATE(subs_date) AS day, COUNT(*) AS cnt', false)
            ->select('
                ms.id, ms.subs_date, ms.status,
                mt.name  AS meal_type_name,
                cf.name  AS cafeteria_name,
                count(subs_date) as total
            ')
            ->join('meal_types   mt', 'mt.id = ms.meal_type_id',   'left')
            ->join('cafeterias   cf', 'cf.id = ms.cafeteria_id',   'left')
            ->where('subs_date >=', $monthStart)
            ->where('subs_date <=', $monthEnd)
            ->whereIn('status', ['ACTIVE', 'REDEEMED'])
            ->groupBy('subs_date')
            ->groupBy('meal_type_id')
            ->groupBy('cafeteria_id')
            ->orderBy('subs_date', 'ASC')
            ->get()
            ->getResultArray(); 

       
        return view('vendor/registrations/monthly', [
            'rows' => $rows,
            'date' => $now->format('F Y'),
        ]);
    }
}
