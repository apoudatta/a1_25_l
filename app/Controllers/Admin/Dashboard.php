<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MealTypeModel;
use CodeIgniter\Database\BaseBuilder;
use DateTime;
use DateTimeZone;

class Dashboard extends BaseController
{
    private const EMPLOYEE_TYPE = 1;
    private const GUEST_TYPES   = [8, 9, 10];

    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    /**
     * Dashboard main view
     */
    public function index()
    {
        $today = date('Y-m-d');

        // --- Read filters (GET) ---
        $employeeType = trim((string) ($this->request->getGet('employee_type') ?? '')); // can be name or id
        $mealTypeId   = trim((string) ($this->request->getGet('meal_type_id')   ?? ''));
        $cafeteriaId  = trim((string) ($this->request->getGet('cafeteria_id')   ?? ''));
        $startDate    = trim((string) ($this->request->getGet('start_date')     ?? $today));
        $endDate      = trim((string) ($this->request->getGet('end_date')       ?? $today));

        // Normalize dates
        if ($startDate === '') $startDate = $today;
        if ($endDate   === '') $endDate   = $today;
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $db = db_connect();

        // --- Lookups for filters (used by the view) ---
        $employmentTypes = $db->table('employment_types')
            ->select('id, name')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

        // Map name (upper) -> id for filtering by name
        $empTypeMapByName = array_change_key_case(array_column($employmentTypes, 'id', 'name'), CASE_UPPER);

        // Meal types (ACTIVE)
        $mealTypes = $db->table('meal_types')
            ->select('id, name')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

        // Cafeterias (ACTIVE)
        $cafeterias = $db->table('cafeterias')
            ->select('id, name')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

        // Resolve employee type filter → emp_type_id (int) or null
        $empTypeId = null;
        if ($employeeType !== '') {
            if (ctype_digit($employeeType)) {
                $empTypeId = (int) $employeeType;
            } else {
                $key = strtoupper($employeeType);
                $empTypeId = $empTypeMapByName[$key] ?? null;
            }
        }

        // Helper: apply common filters to a builder (date, meal type, cafeteria)
        $applyCommon = function (BaseBuilder $qb, string $dateCol) use ($startDate, $endDate, $mealTypeId, $cafeteriaId): void {
            $qb->where("$dateCol >=", $startDate)
               ->where("$dateCol <=", $endDate);

            if ($mealTypeId !== '' && ctype_digit($mealTypeId)) {
                $qb->where('meal_type_id', (int) $mealTypeId);
            }
            if ($cafeteriaId !== '' && ctype_digit($cafeteriaId)) {
                $qb->where('cafeteria_id', (int) $cafeteriaId);
            }
        };

        // Helper: apply employee type filter (if provided)
        $applyEmpType = function (BaseBuilder $qb) use ($empTypeId): void {
            if ($empTypeId !== null) {
                $qb->where('emp_type_id', $empTypeId);
            }
        };

        // ------------------------------------------------
        // KPI 1: Registrations = ACTIVE + REDEEMED
        // ------------------------------------------------
        $qb1 = $db->table('meal_subscriptions ms')
            ->select('COUNT(*) AS c', false)
            ->groupStart() // status in (ACTIVE, REDEEMED)
                ->where('ms.status', 'ACTIVE')
                ->orWhere('ms.status', 'REDEEMED')
            ->groupEnd();

        $applyCommon($qb1, 'ms.subs_date');
        $applyEmpType($qb1);

        $registrations = (int) ($qb1->get()->getFirstRow('array')['c'] ?? 0);

        // ------------------------------------------------
        // KPI 2: Meals Consumed = REDEEMED
        // ------------------------------------------------
        $qb2 = $db->table('meal_subscriptions ms')
            ->select('COUNT(*) AS c', false)
            ->where('ms.status', 'REDEEMED');

        $applyCommon($qb2, 'ms.subs_date');
        $applyEmpType($qb2);

        $consumed = (int) ($qb2->get()->getFirstRow('array')['c'] ?? 0);

        // ------------------------------------------------
        // KPI 3: Pending approvals (join with subscriptions to respect filters)
        // ------------------------------------------------
        $qb3 = $db->table('meal_approvals ma')
            ->select('COUNT(*) AS c', false)
            ->join('meal_subscriptions ms', 'ms.id = ma.subs_id', 'inner')
            ->where('ma.approval_status', 'PENDING');

        $applyCommon($qb3, 'ms.subs_date');
        $applyEmpType($qb3);

        $pending = (int) ($qb3->get()->getFirstRow('array')['c'] ?? 0);

        return view('dashboard/admin_dashboard', [
            'registrations'  => $registrations,
            'consumed'       => $consumed,
            'pending'        => $pending,

            // filter dropdown data
            'employeeTypes'  => array_column($employmentTypes, 'name'), // view expects names list
            'mealTypes'      => $mealTypes,
            'cafeterias'     => $cafeterias,

            // selected filter values (echo back to view)
            'employee_type'  => $employeeType,
            'meal_type_id'   => $mealTypeId,
            'cafeteria_id'   => $cafeteriaId,
            'start_date'     => $startDate,
            'end_date'       => $endDate,
        ]);
    }

    /**
     * Last 7 days registration trend (respects same filters).
     * Returns: { labels: [Mon 1, ...], data: [n, ...] }
     */
    public function trendData()
    {
        $today = date('Y-m-d');

        // optional same filters
        $employeeType = trim((string) ($this->request->getGet('employee_type') ?? ''));
        $mealTypeId   = trim((string) ($this->request->getGet('meal_type_id')   ?? ''));
        $cafeteriaId  = trim((string) ($this->request->getGet('cafeteria_id')   ?? ''));

        $db = db_connect();

        // Employment type map
        $employmentTypes = $db->table('employment_types')->select('id, name')->where('is_active', 1)->get()->getResultArray();
        $empTypeMapByName = array_change_key_case(array_column($employmentTypes, 'id', 'name'), CASE_UPPER);
        $empTypeId = null;
        if ($employeeType !== '') {
            $empTypeId = ctype_digit($employeeType) ? (int) $employeeType : ($empTypeMapByName[strtoupper($employeeType)] ?? null);
        }

        $labels = [];
        $data   = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days", strtotime($today)));

            $qb = $db->table('meal_subscriptions ms')
                ->select('COUNT(*) AS c', false)
                ->groupStart()
                    ->where('ms.status', 'ACTIVE')
                    ->orWhere('ms.status', 'REDEEMED')
                ->groupEnd()
                ->where('ms.subs_date', $day);

            if ($mealTypeId !== '' && ctype_digit($mealTypeId)) {
                $qb->where('ms.meal_type_id', (int) $mealTypeId);
            }
            if ($cafeteriaId !== '' && ctype_digit($cafeteriaId)) {
                $qb->where('ms.cafeteria_id', (int) $cafeteriaId);
            }
            if ($empTypeId !== null) {
                $qb->where('ms.emp_type_id', $empTypeId);
            }

            $count = (int) ($qb->get()->getFirstRow('array')['c'] ?? 0);

            $labels[] = date('M j', strtotime($day)); // e.g., "Sep 19"
            $data[]   = $count;
        }

        return $this->response->setJSON([
            'labels' => $labels,
            'data'   => $data,
        ]);
    }

    /**
     * Today's consumed distribution by meal type (doughnut chart).
     * Returns: { labels:[...], data:[...] }
     */
    public function distData()
    {
        $today = date('Y-m-d');

        // optional same filters
        $employeeType = trim((string) ($this->request->getGet('employee_type') ?? ''));
        $cafeteriaId  = trim((string) ($this->request->getGet('cafeteria_id')   ?? ''));

        $db = db_connect();
        $mealTypeModel = new MealTypeModel();

        // Employment type map
        $employmentTypes = $db->table('employment_types')->select('id, name')->where('is_active', 1)->get()->getResultArray();
        $empTypeMapByName = array_change_key_case(array_column($employmentTypes, 'id', 'name'), CASE_UPPER);
        $empTypeId = null;
        if ($employeeType !== '') {
            $empTypeId = ctype_digit($employeeType) ? (int) $employeeType : ($empTypeMapByName[strtoupper($employeeType)] ?? null);
        }

        $qb = $db->table('meal_subscriptions ms')
            ->select('ms.meal_type_id, COUNT(*) AS cnt', false)
            ->where('ms.subs_date', $today)
            ->where('ms.status', 'REDEEMED')
            ->groupBy('ms.meal_type_id');

        if ($cafeteriaId !== '' && ctype_digit($cafeteriaId)) {
            $qb->where('ms.cafeteria_id', (int) $cafeteriaId);
        }
        if ($empTypeId !== null) {
            $qb->where('ms.emp_type_id', $empTypeId);
        }

        $rows = $qb->get()->getResult();

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

    public function employeeDashboard()
    {
        // Inputs (Y-m-d). Defaults to current month in Asia/Dhaka.
        $startDateIn = trim((string) ($this->request->getGet('start_date') ?? ''));
        $endDateIn   = trim((string) ($this->request->getGet('end_date') ?? ''));
        $cafeteriaId = (int) ($this->request->getGet('cafeteria_id') ?? 0);

        $tz = new DateTimeZone('Asia/Dhaka');
        if ($startDateIn === '' || $endDateIn === '') {
            $today     = new DateTime('now', $tz);
            $startDate = (clone $today)->modify('first day of this month')->format('Y-m-d');
            $endDate   = (clone $today)->modify('last day of this month')->format('Y-m-d');
        } else {
            $startDate = $startDateIn;
            $endDate   = $endDateIn;
        }

        $userId = (int) (session('user_id') ?? 0);

        // Cafeteria dropdown
        $cafeterias = $this->db->table('cafeterias')
            ->select('id, name')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        // Common WHERE for date/cafeteria
        $periodWhere = [
            'subs_date >=' => $startDate,
            'subs_date <=' => $endDate,
        ];
        if ($cafeteriaId > 0) {
            $periodWhere['cafeteria_id'] = $cafeteriaId;
        }

        // ===== Employee (self) stats =====
        $empWhere = $periodWhere + [
            'user_id'     => $userId,
            'emp_type_id' => self::EMPLOYEE_TYPE,
        ];
        $empCounts = $this->countByStatus($empWhere);

        // Map to legacy keys
        $totalActive    = $empCounts['ACTIVE']   ?? 0;
        $totalPending   = $empCounts['PENDING']  ?? 0;
        $totalConsumed  = $empCounts['REDEEMED'] ?? 0;
        $totalCancelled = $empCounts['CANCELLED']?? 0;

        // ===== Guests created by me (guest types only) =====
        $guestWhere = $periodWhere + [
            'created_by' => $userId,
        ];
        $guestCounts = $this->countByStatus($guestWhere, true); // restrict to guest types

        $guestActive   = $guestCounts['ACTIVE']   ?? 0;
        $guestConsumed = $guestCounts['REDEEMED'] ?? 0;

        return view('dashboard/employee_dashboard', [
            // unchanged payload
            'registrations'   => $totalActive,
            'pending'         => $totalPending,
            'cancelled'       => $totalCancelled,
            'consumed'        => $totalConsumed,

            // unchanged guest keys
            'guest'           => $guestActive,
            'guest_consumed'  => $guestConsumed,

            // unchanged filter data
            'cafeterias'      => $cafeterias,
            'cafeteria_id'    => $cafeteriaId,
            'start_date'      => $startDate,
            'end_date'        => $endDate,
        ]);
    }

    /**
     * Returns counts by status from `meal_subscriptions`.
     * If $guestOnly is true, restricts to guest emp_type_ids.
     *
     * @param array $where   Column => value pairs
     * @param bool  $guestOnly
     * @return array         ['ACTIVE'=>n,'PENDING'=>n,'REDEEMED'=>n,'CANCELLED'=>n,'NO_SHOW'=>n]
     */
    private function countByStatus(array $where, bool $guestOnly = false): array
    {
        $tb = $this->db->table('meal_subscriptions')
            ->select('status, COUNT(*) AS c', false)
            ->where($where);

        if ($guestOnly) {
            $tb->whereIn('emp_type_id', self::GUEST_TYPES);
        }

        $rows = $tb->groupBy('status')->get()->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['c'];
        }
        foreach (['ACTIVE','PENDING','REDEEMED','CANCELLED','NO_SHOW'] as $k) {
            $out[$k] = $out[$k] ?? 0;
        }
        return $out;
    }
}
