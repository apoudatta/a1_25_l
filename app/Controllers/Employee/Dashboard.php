<?php namespace App\Controllers\Employee;

use App\Controllers\BaseController;
use App\Models\MealSubscriptionDetailModel;
use App\Models\GuestSubscriptionModel;
use DateTime;
use DateTimeZone;

class Dashboard extends BaseController
{
    protected $MealSubscriptionDetailModel;
    protected $GuestSubscriptionModel;

    public function __construct()
    {
        $this->MealSubscriptionDetailModel= new MealSubscriptionDetailModel();
        $this->GuestSubscriptionModel  = new GuestSubscriptionModel();
    }

    public function index()
    {
        $today = date('Y-m-d');

        // --- filters (GET) ---
        $cafeteriaId = trim((string) $this->request->getGet('cafeteria_id') ?? '');
        $startDate   = trim((string) $this->request->getGet('start_date') ?? $today);
        $endDate     = trim((string) $this->request->getGet('end_date') ?? $today);

        if ($startDate === '') $startDate = $today;
        if ($endDate   === '') $endDate   = $today;
        if ($startDate > $endDate) { [$startDate, $endDate] = [$endDate, $startDate]; }

        $db     = \Config\Database::connect();
        $userId = (int) session()->get('user_id');

        // ---------------------------
        // 1) Registrations (employee’s own)
        // from: meal_subscription_details (ACTIVE)
        // ---------------------------
        $regQB = $db->table('meal_subscription_details')
            ->where('user_id', $userId)
            ->where('status', 'ACTIVE')
            ->where('subscription_date >=', $startDate)
            ->where('subscription_date <=', $endDate);

        if ($cafeteriaId !== '') {
            $regQB->where('cafeteria_id', $cafeteriaId);
        }
        $totalReg = (int) $regQB->countAllResults();

        // ---------------------------
        // 2) Meals Consumed (employee’s own)
        // from: meal_subscription_details (REDEEMED)
        // ---------------------------
        $consQB = $db->table('meal_subscription_details')
            ->where('user_id', $userId)
            ->where('status', 'REDEEMED')
            ->where('subscription_date >=', $startDate)
            ->where('subscription_date <=', $endDate);

        if ($cafeteriaId !== '') {
            $consQB->where('cafeteria_id', $cafeteriaId);
        }
        $totalConsumed = (int) $consQB->countAllResults();

        // ---------------------------
        // 3) Guest subscriptions (your guests)
        // ACTIVE and REDEEMED counts (pick one in the view)
        // ---------------------------
        $guestActiveQB = $db->table('guest_subscriptions')
            ->where('user_id', $userId)
            ->where('status', 'ACTIVE')
            ->where('subscription_date >=', $startDate)
            ->where('subscription_date <=', $endDate);

        if ($cafeteriaId !== '') {
            $guestActiveQB->where('cafeteria_id', $cafeteriaId);
        }
        $guestActive = (int) $guestActiveQB->countAllResults();

        $guestConsumedQB = $db->table('guest_subscriptions')
            ->where('user_id', $userId)
            ->where('status', 'REDEEMED')
            ->where('subscription_date >=', $startDate)
            ->where('subscription_date <=', $endDate);

        if ($cafeteriaId !== '') {
            $guestConsumedQB->where('cafeteria_id', $cafeteriaId);
        }
        $guestConsumed = (int) $guestConsumedQB->countAllResults();

        // Dropdown: cafeterias
        $cafeterias = $db->table('cafeterias')
            ->select('id, name')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

        return view('employee/dashboard', [
            'registrations'   => $totalReg,
            'consumed'        => $totalConsumed,

            // Use whichever KPI you prefer:
            'guest'           => $guestActive,     // ACTIVE guests (current behavior)
            'guest_consumed'  => $guestConsumed,   // or show this in the card instead

            // dropdown data + selected values
            'cafeterias'      => $cafeterias,
            'cafeteria_id'    => $cafeteriaId,
            'start_date'      => $startDate,
            'end_date'        => $endDate,
        ]);
    }

}
