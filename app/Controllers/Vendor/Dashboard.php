<?php

namespace App\Controllers\Vendor;

use App\Controllers\BaseController;

class Dashboard extends BaseController
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

        // === registrations ===
        // All registered meals for today excluding CANCELLED.
        // (Includes ACTIVE, PENDING, REDEEMED, and any NO_SHOW flagged later.)
        $registrations = (int) $this->db->table('meal_subscriptions')
            ->where('subs_date', $today)
            ->whereIn('status', ['ACTIVE', 'REDEEMED'])
            ->countAllResults();

        // === redeemed ===
        // Meals actually consumed today.
        $redeemed = (int) $this->db->table('meal_subscriptions')
            ->where('subs_date', $today)
            ->where('status', 'REDEEMED')
            ->countAllResults();

        // === pending ===
        // Still to be served today (planned but not redeemed).
        // We treat ACTIVE + PENDING as "awaiting redemption".
        $pending = (int) $this->db->table('meal_subscriptions')
            ->where('subs_date', $today)
            ->whereIn('status', ['ACTIVE'])
            ->countAllResults();

        return view('vendor/dashboard', [
            'registrations' => $registrations,
            'redeemed'      => $redeemed,
            'pending'       => $pending,
        ]);
    }
}
