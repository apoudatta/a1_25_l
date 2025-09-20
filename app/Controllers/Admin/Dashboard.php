<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MealTypeModel;
use CodeIgniter\Database\BaseBuilder;

class Dashboard extends BaseController
{
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

        return view('admin/dashboard/index', [
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
}
