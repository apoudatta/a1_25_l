<?php

namespace App\Controllers\Vendor;

use App\Controllers\BaseController;

class Meals extends BaseController
{
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        // Today in Asia/Dhaka to match business day boundaries
        $today = (new \DateTime('now', new \DateTimeZone('Asia/Dhaka')))->format('Y-m-d');

        // Count non-cancelled registrations per meal type for today
        $rows = $this->db->table('meal_subscriptions ms')
            ->select('mt.name AS type, COUNT(*) AS cnt', false)
            ->join('meal_types mt', 'mt.id = ms.meal_type_id', 'left')
            ->where('ms.subs_date', $today)
            ->whereIn('ms.status', ['ACTIVE', 'REDEEMED'])
            ->groupBy('mt.id, mt.name')
            ->orderBy('mt.id', 'ASC')
            ->get()
            ->getResult();

        // Keep the exact data shape expected by your view
        $data = [];
        foreach ($rows as $r) {
            $data[] = [
                'type'  => (string) $r->type,
                'count' => (int) $r->cnt,
            ];
        }

        return view('vendor/meals/index', [
            'data' => $data,
            'date' => $today,
        ]);
    }
}
