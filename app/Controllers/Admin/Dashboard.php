<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MealSubscriptionModel;
use App\Models\MealTokenModel;
use App\Models\MealApprovalModel;
use App\Models\UserModel;
use App\Models\MealTypeModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $today = date('Y-m-d');

        // --- read selected filters (GET) ---
        $employeeType = trim((string) $this->request->getGet('employee_type') ?? '');
        $mealTypeId   = trim((string) $this->request->getGet('meal_type_id') ?? '');
        $cafeteriaId  = trim((string) $this->request->getGet('cafeteria_id') ?? '');
        $startDate    = trim((string) $this->request->getGet('start_date') ?? $today);
        $endDate      = trim((string) $this->request->getGet('end_date') ?? $today);

        // normalize dates
        if ($startDate === '') $startDate = $today;
        if ($endDate   === '') $endDate   = $today;
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $db = \Config\Database::connect();

        // groups for matching employee_type dropdown
        // $internTypes = ['FTC','INTERN','NEW JOINER'];
        // Load active employment types (intern-facing) and merge for the dropdown/order
        $internTypes = $db->table('employment_types')
            ->select('id,name')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();


        



        $internTypeNames = array_map(static fn($r) => (string) $r['name'], $internTypes);
        $guestTypes  = ['HR GUEST','PROJECT GUEST','PERSONAL GUEST'];
        $allEmployeeTypes   = array_merge(['EMPLOYEE'], $internTypeNames, $guestTypes); // for the view dropdown

        // helper: common filters
        $applyCommon = function (\CodeIgniter\Database\BaseBuilder $qb, string $dateCol)
            use ($mealTypeId, $cafeteriaId, $startDate, $endDate) {
            $qb->where("$dateCol >=", $startDate)
            ->where("$dateCol <=", $endDate);
            if ($mealTypeId !== '')  $qb->where('meal_type_id', $mealTypeId);
            if ($cafeteriaId !== '') $qb->where('cafeteria_id', $cafeteriaId);
            return $qb;
        };

        // ----------------------------------------------------------------
        // 1) KPI: Registrations (ACTIVE) — sum of three sources by filter
        // ----------------------------------------------------------------
        $registrations = 0;

        // A) EMPLOYEE (meal_subscription_details)
        $empCount = 0;
        if ($employeeType === '' || strtoupper($employeeType) === 'EMPLOYEE') {
            $q = $db->table('meal_subscription_details');
            $applyCommon($q, 'subscription_date');
            $q->where('status', 'ACTIVE');
            $empCount = (int) $q->countAllResults();
        }

        // B) INTERN (intern_subscriptions)
        $internCount = 0;
        if ($employeeType === '' || in_array(strtoupper($employeeType), $internTypeNames, true)) {
            // get empTypeID by employeeType name
            $map       = array_column($internTypes, 'id', 'name');   // ['New Joiner' => 7, 'FTC' => 3, ...]
            $mapUpper  = array_change_key_case($map, CASE_UPPER);    // keys uppercased
            $employeeType = strtoupper(trim($employeeType));         // e.g. 'FTC'
            $empTypeID    = $mapUpper[$employeeType] ?? null;        // -> 3 (or null if not found)

            $q = $db->table('intern_subscriptions');
            $applyCommon($q, 'subscription_date');
            $q->where('status', 'ACTIVE');
            if (($employeeType !== '') && ($empTypeID !== null)) {
                $q->where('employment_type_id', $empTypeID);
            }
            $internCount = (int) $q->countAllResults();
        }

        // C) GUEST (guest_subscriptions)
        $guestCount = 0;
        if ($employeeType === '' || in_array(strtoupper($employeeType), $guestTypes, true)) {
            $q = $db->table('guest_subscriptions');
            $applyCommon($q, 'subscription_date');
            $q->where('status', 'ACTIVE');
            if ($employeeType !== '') {
                switch (strtoupper($employeeType)) {
                    case 'HR GUEST':       $q->where('guest_type', 'HR GUEST'); break;
                    case 'PROJECT GUEST':  $q->where('guest_type', 'PROJECT GUEST'); break;
                    case 'PERSONAL GUEST': $q->where('guest_type', 'PERSONAL GUEST'); break;
                }
            }
            $guestCount = (int) $q->countAllResults();
        }

        $registrations = $empCount + $internCount + $guestCount;

        // ----------------------------------------------------------------
        // 2) KPI: Meals Consumed (REDEEMED) — sum across the three sources
        // ----------------------------------------------------------------
        $consumed = 0;

        // EMPLOYEE consumed
        $empConsumed = 0;
        if ($employeeType === '' || strtoupper($employeeType) === 'EMPLOYEE') {
            $q = $db->table('meal_subscription_details');
            $applyCommon($q, 'subscription_date');
            $q->where('status', 'REDEEMED');
            $empConsumed = (int) $q->countAllResults();
        }

        // INTERN consumed
        $internConsumed = 0;
        if ($employeeType === '' || in_array(strtoupper($employeeType), $internTypes, true)) {
            $q = $db->table('intern_subscriptions');
            $applyCommon($q, 'subscription_date');
            $q->where('status', 'REDEEMED');
            if ($employeeType !== '') {
                switch (strtoupper($employeeType)) {
                    case 'FTC':        $q->where('subscription_type', 'FTC'); break;
                    case 'INTERN':     $q->where('subscription_type', 'Intern'); break;
                    case 'NEW JOINER': $q->where('subscription_type', 'New joiner'); break;
                }
            }
            $internConsumed = (int) $q->countAllResults();
        }

        // GUEST consumed
        $guestConsumed = 0;
        if ($employeeType === '' || in_array(strtoupper($employeeType), $guestTypes, true)) {
            $q = $db->table('guest_subscriptions');
            $applyCommon($q, 'subscription_date');
            $q->where('status', 'REDEEMED');
            if ($employeeType !== '') {
                switch (strtoupper($employeeType)) {
                    case 'HR GUEST':       $q->where('guest_type', 'HR GUEST'); break;
                    case 'PROJECT GUEST':  $q->where('guest_type', 'PROJECT GUEST'); break;
                    case 'PERSONAL GUEST': $q->where('guest_type', 'PERSONAL GUEST'); break;
                }
            }
            $guestConsumed = (int) $q->countAllResults();
        }

        $consumed = $empConsumed + $internConsumed + $guestConsumed;

        // ----------------------------------------------------------------
        // 3) KPI: Pending approvals (employee-only, by subscription window)
        // ----------------------------------------------------------------
        $pending = 0;
        if ($employeeType === '' || strtoupper($employeeType) === 'EMPLOYEE') {
            $q = $db->table('meal_approvals ma')
                ->where('ma.approval_status', 'PENDING')
                ->join('meal_subscriptions ms', 'ms.id = ma.subscription_id', 'left')
                ->where('ms.start_date <=', $endDate)
                ->where('ms.end_date >=', $startDate);

            if ($mealTypeId !== '')  $q->where('ms.meal_type_id', $mealTypeId);
            if ($cafeteriaId !== '') $q->where('ms.cafeteria_id', $cafeteriaId);

            $pending = (int) $q->countAllResults();
        }

        

        $mealTypes = $db->table('meal_types')
            ->select('id, name')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $cafeterias = $db->table('cafeterias')
            ->select('id, name')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

        return view('admin/dashboard/index', [
            'registrations'  => $registrations,
            'consumed'       => $consumed,
            'pending'        => $pending,

            'employeeTypes'  => $allEmployeeTypes,
            'mealTypes'      => $mealTypes,
            'cafeterias'     => $cafeterias,

            'employee_type'  => $employeeType,
            'meal_type_id'   => $mealTypeId,
            'cafeteria_id'   => $cafeteriaId,
            'start_date'     => $startDate,
            'end_date'       => $endDate,
        ]);
    }

    public function trendData()
    {
        $model = new MealSubscriptionModel();
        $labels = [];
        $data   = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $count = $model
                ->where('start_date <=', $day)
                ->where('end_date >=', $day)
                ->countAllResults();
            $labels[] = date('M j', strtotime($day)); // e.g. "Jun 11"
            $data[]   = $count;
        }
        return $this->response->setJSON([
            'labels' => $labels,
            'data'   => $data,
        ]);
    }

    /**
     * Return today's meal-type distribution for doughnut chart
     */
    public function distData()
    {
        $tokenModel = new MealTokenModel();
        $mealTypeModel = new MealTypeModel();

        $today = date('Y-m-d');
        // Get counts grouped by meal_type_id
        $builder = $tokenModel->builder();
        $rows = $builder
            ->select('meal_type_id, COUNT(*) as cnt')
            ->where('meal_date', $today)
            ->where('status', 'REDEEMED')
            ->groupBy('meal_type_id')
            ->get()
            ->getResult();

        $labels = [];
        $data   = [];
        foreach ($rows as $r) {
            $mt = $mealTypeModel->find($r->meal_type_id);
            $labels[] = $mt ? $mt['name'] : 'Unknown';
            $data[]   = (int) $r->cnt;
        }

        return $this->response->setJSON([
            'labels' => $labels,
            'data'   => $data,
        ]);
    }
}
