<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class ReportController extends BaseController
{
    /** Fetch dropdown data once */
    protected function getLookups(): array
    {
        $db = db_connect();

        $mealTypes = $db->table('meal_types')
            ->select('id, name')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        $cafeterias = $db->table('cafeterias')
            ->select('id, name')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        return [
            'mealTypes'  => $mealTypes,
            'cafeterias' => $cafeterias,
        ];
    }

    /** Default landing – redirect to first report */
    public function index()
    {
        return redirect()->to(site_url('admin/report/meal-charge-list-for-payroll'));
    }

    /** 1) Meal Charge list for payroll */
    public function mealChargeListForPayroll(): \CodeIgniter\HTTP\ResponseInterface
    {
        $month = (int) ($this->request->getGet('month') ?? date('n'));   // 1..12
        $year  = (int) ($this->request->getGet('year')  ?? date('Y'));
        $empId = trim((string) $this->request->getGet('employee_id'));
        $type  = trim((string) $this->request->getGet('type')); // EMPLOYEE / INTERN / GUEST / etc.

        // First & last date of the selected month
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = (new \DateTimeImmutable($start))->modify('last day of this month')->format('Y-m-d');

        $db = db_connect();

        // NOTE:
        // - We count chargeable days from meal_subscription_details (exclude CANCELLED/PENDING)
        // - We sum per-day user_tk from meal_contributions chosen by effective_date <= subscription_date
        //   (so if contribution changes mid-month, it’s still exact).
        $sql = "
            SELECT
                u.employee_id                           AS emp_id,
                COALESCE(u.name,'')                     AS emp_name,
                COALESCE(u.designation,'')              AS designation,
                COALESCE(u.division, u.department, '')  AS division,
                u.department                            AS job_location,
                DATE_FORMAT(?, '%M''%Y')                AS month_year,   -- << fixed
                COUNT(msd.id)                           AS day_count,
                ROUND(SUM(COALESCE(mc.user_tk,0)),2)    AS meal_charge
            FROM meal_subscription_details msd
            JOIN meal_subscriptions ms ON ms.id = msd.subscription_id
            JOIN users u               ON u.id  = msd.user_id
            LEFT JOIN meal_contributions mc
                ON mc.meal_type_id  = msd.meal_type_id
                AND (mc.cafeteria_id IS NULL OR mc.cafeteria_id = msd.cafeteria_id)
                AND mc.user_type     = ms.subscription_type
                AND mc.effective_date = (
                        SELECT MAX(m2.effective_date)
                        FROM meal_contributions m2
                        WHERE m2.meal_type_id  = msd.meal_type_id
                        AND (m2.cafeteria_id IS NULL OR m2.cafeteria_id = msd.cafeteria_id)
                        AND m2.user_type     = ms.subscription_type
                        AND m2.effective_date <= msd.subscription_date
                )
            WHERE msd.subscription_date BETWEEN ? AND ?
            AND msd.status IN ('ACTIVE','REDEEMED','NO_SHOW')
        ";

        $binds = [$start, $start, $end];

        if ($empId !== '') {
            $sql   .= " AND u.employee_id = ? ";
            $binds[] = $empId;
        }
        if ($type !== '') {
            $sql   .= " AND ms.subscription_type = ? ";
            $binds[] = $type;
        }

        $sql .= "
            GROUP BY u.id, u.employee_id, u.name, u.designation, u.division, u.department, ms.subscription_type
            ORDER BY u.employee_id
        ";

        $rows = $db->query($sql, $binds)->getResultArray();

        // Lookups for possible future dropdowns (meal types, cafeterias) not needed here
        $payload = [
            'title'   => "Meal Charge list for payroll",
            'filters' => [
                'month'       => $month,
                'year'        => $year,
                'employee_id' => $empId,
                'type'        => $type,
            ],
            'rows'    => $rows,
        ];

        return $this->response->setBody(view('admin/report/meal_charge_list_for_payroll', $payload));
    }


    /** 2) Meal Report for billing (e.g., vendor/cafeteria billing) */
    public function mealReportForBilling(): \CodeIgniter\HTTP\ResponseInterface
    {
        $month = (int) ($this->request->getGet('month') ?? date('n'));   // 1..12
        $year  = (int) ($this->request->getGet('year')  ?? date('Y'));
        $type  = trim((string) $this->request->getGet('type')); // EMPLOYEE/INTERN/GUEST/OS/Security Guard/Support Staff

        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = (new \DateTimeImmutable($start))->modify('last day of this month')->format('Y-m-d');
        $monthYearLabel = date("F'Y", strtotime($start));

        $db = db_connect();

        $employeeTypes = ['EMPLOYEE', 'OS', 'Security Guard', 'Support Staff'];
        $wantEmp    = ($type === '' || in_array($type, $employeeTypes, true));
        $wantIntern = ($type === '' || $type === 'INTERN');
        $wantGuest  = ($type === '' || $type === 'GUEST');

        /* ----------------- EMPLOYEE LIST (meal_subscription_details) ----------------- */
        $empRows = [];
        if ($wantEmp) {
            $sql = "
                SELECT
                    u.employee_id  AS emp_id,
                    u.name         AS emp_name,
                    u.designation  AS designation,
                    u.division     AS division,
                    u.phone        AS mobile,
                    COUNT(msd.id)                           AS day_count,
                    ROUND(SUM(COALESCE(mc.user_tk,0) + COALESCE(mc.company_tk,0)),2) AS full_meal_cost
                FROM meal_subscription_details msd
                JOIN meal_subscriptions ms  ON ms.id  = msd.subscription_id
                JOIN users u                ON u.id   = msd.user_id
                LEFT JOIN meal_contributions mc
                    ON mc.meal_type_id  = msd.meal_type_id
                    AND (mc.cafeteria_id IS NULL OR mc.cafeteria_id = msd.cafeteria_id)
                    AND mc.user_type     = ms.subscription_type
                    AND mc.effective_date = (
                            SELECT MAX(m2.effective_date)
                            FROM meal_contributions m2
                            WHERE m2.meal_type_id  = msd.meal_type_id
                            AND (m2.cafeteria_id IS NULL OR m2.cafeteria_id = msd.cafeteria_id)
                            AND m2.user_type     = ms.subscription_type
                            AND m2.effective_date <= msd.subscription_date
                    )
                WHERE msd.subscription_date BETWEEN ? AND ?
                AND msd.status IN ('ACTIVE','REDEEMED')   -- was: = 'REDEEMED'

            ";
            $binds = [$start, $end];

            if ($type !== '' && in_array($type, $employeeTypes, true)) {
                $sql   .= " AND ms.subscription_type = ? ";
                $binds[] = $type;
            }

            $sql .= "
                GROUP BY u.id, u.employee_id, u.name, u.designation, u.division, u.department, u.phone
                ORDER BY u.employee_id
            ";

            $empRows = $db->query($sql, $binds)->getResultArray();
            foreach ($empRows as &$r) { $r['month_year'] = $monthYearLabel; }
            unset($r);
        }

        /* ----------------- INTERN LIST (intern_subscriptions) ----------------- */
        // Per requirement: no Designation/Division columns for Intern
        $internRows = [];
        if ($wantIntern) {
            $sql = "
                SELECT
                    isub.user_reference_id AS intern_id,
                    COALESCE(isub.intern_name,'') AS intern_name,
                    COUNT(isub.id) AS day_count,
                    ROUND(SUM(COALESCE(mc.user_tk,0) + COALESCE(mc.company_tk,0)),2) AS full_meal_cost
                FROM intern_subscriptions isub
                LEFT JOIN meal_contributions mc
                    ON mc.meal_type_id  = isub.meal_type_id
                    AND (mc.cafeteria_id IS NULL OR mc.cafeteria_id = isub.cafeteria_id)
                    AND mc.user_type     = 'INTERN'
                    AND mc.effective_date = (
                            SELECT MAX(m2.effective_date)
                            FROM meal_contributions m2
                            WHERE m2.meal_type_id  = isub.meal_type_id
                            AND (m2.cafeteria_id IS NULL OR m2.cafeteria_id = isub.cafeteria_id)
                            AND m2.user_type     = 'INTERN'
                            AND m2.effective_date <= isub.subscription_date
                    )
                WHERE isub.subscription_date BETWEEN ? AND ?
                AND isub.status IN ('ACTIVE','REDEEMED')
                GROUP BY isub.user_reference_id, isub.intern_name
                ORDER BY isub.user_reference_id
            ";
            $internRows = $db->query($sql, [$start, $end])->getResultArray();
            foreach ($internRows as &$r) { $r['month_year'] = $monthYearLabel; }
            unset($r);
        }

        /* ----------------- GUEST LIST (guest_subscriptions + cafeterias + users) ----------------- */
        $guestRows = [];
        if ($wantGuest) {
            $sql = "
                SELECT
                    gs.guest_name  AS guest_name,
                    c.name         AS location,
                    u.employee_id  AS requestor_emp_id,
                    u.name         AS requestor_name,
                    u.division     AS requestor_division,
                    gs.guest_type     AS guest_type,
                    COUNT(gs.id)   AS day_count,
                    ROUND(SUM(COALESCE(mc.user_tk,0) + COALESCE(mc.company_tk,0)),2) AS full_meal_cost
                FROM guest_subscriptions gs
                LEFT JOIN cafeterias c ON c.id = gs.cafeteria_id
                LEFT JOIN users u      ON u.id = gs.user_id  -- requestor
                LEFT JOIN meal_contributions mc
                    ON mc.meal_type_id  = gs.meal_type_id
                    AND (mc.cafeteria_id IS NULL OR mc.cafeteria_id = gs.cafeteria_id)
                    AND mc.user_type     = 'GUEST'
                    AND mc.effective_date = (
                            SELECT MAX(m2.effective_date)
                            FROM meal_contributions m2
                            WHERE m2.meal_type_id  = gs.meal_type_id
                            AND (m2.cafeteria_id IS NULL OR m2.cafeteria_id = gs.cafeteria_id)
                            AND m2.user_type     = 'GUEST'
                            AND m2.effective_date <= gs.subscription_date
                    )
                WHERE gs.subscription_date BETWEEN ? AND ?
                AND gs.status IN ('ACTIVE','REDEEMED')
            ";
            $binds = [$start, $end];

            // If user selected Type=GUEST we still show; if selected an employee subtype, guest table will be empty anyway
            $sql .= "
                GROUP BY gs.guest_name, c.name, u.employee_id, u.name, u.division, u.department
                ORDER BY gs.guest_name
            ";

            $guestRows = $db->query($sql, $binds)->getResultArray();
            foreach ($guestRows as &$r) { $r['month_year'] = $monthYearLabel; }
            unset($r);
        }

        $payload = [
            'title'   => "Meal Report for billing",
            'filters' => [
                'month' => $month,
                'year'  => $year,
                'type'  => $type,
            ],
            'rowsEmp'    => $empRows,
            'rowsIntern' => $internRows,
            'rowsGuest'  => $guestRows,
        ];

        return $this->response->setBody(view('admin/report/meal_report_for_billing', $payload));
    }


    /** 3) Meal Detail Report (line items) */
    public function mealDetailReport(): \CodeIgniter\HTTP\ResponseInterface
    {
        $empId = trim((string) $this->request->getGet('employee_id'));
        $month = $this->request->getGet('month');                       // 1..12
        $year  = (int) ($this->request->getGet('year') ?? date('Y'));   // default current year

        $monthInt = ($month !== null && $month !== '') ? (int) $month : null;

        $db          = db_connect();
        $headerDates = [];  // column headers: "1-Jan-25", ...
        $row         = null; // one row for the chosen employee+month

        // Show data only when employee_id AND month are provided
        if ($empId !== '' && $monthInt) {
            $start = sprintf('%04d-%02d-01', $year, $monthInt);
            $end   = (new \DateTimeImmutable($start))->modify('last day of this month')->format('Y-m-d');

            // Availing = REDEEMED days (change to IN ('ACTIVE','REDEEMED') if you want active billed days here too)
            $sql = "
                SELECT DATE(msd.subscription_date) AS d
                FROM meal_subscription_details msd
                JOIN users u ON u.id = msd.user_id
                WHERE u.employee_id = ?
                AND msd.subscription_date BETWEEN ? AND ?
                AND msd.status IN ('ACTIVE','REDEEMED')
                ORDER BY d ASC
            ";
            $dates = $db->query($sql, [$empId, $start, $end])->getResultArray();

            foreach ($dates as $d) {
                $headerDates[] = date('j-M-y', strtotime($d['d'])); // 1-Jan-25
            }

            $row = [
                'emp_id'     => $empId,
                'month_name' => date('F', strtotime($start)),
                'dates'      => $headerDates,
            ];
        }

        $payload = [
            'title'       => 'Meal Detail Report',
            'filters'     => [
                'employee_id' => $empId,
                'month'       => $monthInt,
                'year'        => $year,
            ],
            'headerDates' => $headerDates,
            'row'         => $row,
        ];

        return $this->response->setBody(view('admin/report/meal_detail_report', $payload));
    }


    /** 4) Daily Meal Report (by day) */
    public function dailyMealReport(): \CodeIgniter\HTTP\ResponseInterface
    {
        // Default to today's date if none provided
        $date = trim((string) ($this->request->getGet('date') ?? ''));
        if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $type        = trim((string) $this->request->getGet('type'));      // 'EMPLOYEE' | <employment_types.name> | 'GUEST' | ''
        $mealTypeId  = (int) ($this->request->getGet('meal_type_id') ?? 0);
        $cafeteriaId = (int) ($this->request->getGet('cafeteria_id') ?? 0);

        $rows = [];
        $db = db_connect();

        // Load active employment types (intern-facing) and merge for the dropdown/order
        $employmentTypes = $db->table('employment_types')
            ->select('name')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

        $etypeNames = array_map(static fn($r) => (string) $r['name'], $employmentTypes);
        $allTypes   = array_merge(['EMPLOYEE'], $etypeNames, ['GUEST']); // for the view dropdown

        // Which sections to include?
        $wantEmp    = ($type === '' || $type === 'EMPLOYEE');
        $wantGuest  = ($type === '' || $type === 'GUEST');
        $wantIntern = ($type === '' || in_array($type, $etypeNames, true));

        /* ---------------- EMPLOYEE ---------------- */
        if ($wantEmp) {
            $sql = "
                SELECT
                    u.employee_id                           AS id,
                    u.name                                  AS name,
                    DATE(msd.subscription_date)             AS date_val,
                    ms.subscription_type                    AS emp_type,
                    mt.name                                 AS meal_type,
                    cf.name                                 AS location
                FROM meal_subscription_details msd
                JOIN meal_subscriptions ms ON ms.id = msd.subscription_id
                JOIN users u               ON u.id  = msd.user_id
                LEFT JOIN meal_types mt    ON mt.id = msd.meal_type_id
                LEFT JOIN cafeterias cf    ON cf.id = msd.cafeteria_id
                WHERE DATE(msd.subscription_date) = ?
                AND ms.status = 'ACTIVE'
                AND msd.status IN ('ACTIVE','REDEEMED')
            ";
            $binds = [$date];
            if ($mealTypeId)  { $sql .= " AND msd.meal_type_id = ? ";  $binds[] = $mealTypeId; }
            if ($cafeteriaId) { $sql .= " AND msd.cafeteria_id  = ? "; $binds[] = $cafeteriaId; }

            // Only narrow when user explicitly picked EMPLOYEE
            if ($type === 'EMPLOYEE') {
                $sql   .= " AND ms.subscription_type = ? ";
                $binds[] = 'EMPLOYEE';
            }

            foreach ($db->query($sql, $binds)->getResultArray() as $r) {
                $rows[] = [
                    'id'        => (string) ($r['id'] ?? ''),
                    'name'      => (string) ($r['name'] ?? ''),
                    'date_val'  => (string) ($r['date_val'] ?? ''),
                    'emp_type'  => (string) ($r['emp_type'] ?? 'EMPLOYEE'),
                    'meal_type' => (string) ($r['meal_type'] ?? ''),
                    'location'  => (string) ($r['location'] ?? ''),
                ];
            }
        }

        /* ---------------- INTERN (employment_type_id) ---------------- */
        if ($wantIntern) {
            $sql = "
                SELECT
                    ''                                      AS id,
                    isub.intern_name                        AS name,
                    DATE(isub.subscription_date)            AS date_val,
                    COALESCE(et.name, 'INTERN')             AS emp_type,
                    mt.name                                 AS meal_type,
                    cf.name                                 AS location
                FROM intern_subscriptions isub
                LEFT JOIN employment_types et ON et.id = isub.employment_type_id
                LEFT JOIN meal_types mt        ON mt.id = isub.meal_type_id
                LEFT JOIN cafeterias cf        ON cf.id = isub.cafeteria_id
                WHERE DATE(isub.subscription_date) = ?
                AND isub.status IN ('ACTIVE','REDEEMED')
            ";
            $binds = [$date];
            if ($mealTypeId)  { $sql .= " AND isub.meal_type_id = ? ";  $binds[] = $mealTypeId; }
            if ($cafeteriaId) { $sql .= " AND isub.cafeteria_id  = ? "; $binds[] = $cafeteriaId; }

            // If a specific employment type (from table) was chosen, filter by it
            if ($type !== '' && in_array($type, $etypeNames, true)) {
                $sql   .= " AND et.name = ? ";
                $binds[] = $type;
            }

            foreach ($db->query($sql, $binds)->getResultArray() as $r) {
                $rows[] = [
                    'id'        => '',
                    'name'      => (string) ($r['name'] ?? ''),
                    'date_val'  => (string) ($r['date_val'] ?? ''),
                    'emp_type'  => (string) ($r['emp_type'] ?? 'INTERN'),
                    'meal_type' => (string) ($r['meal_type'] ?? ''),
                    'location'  => (string) ($r['location'] ?? ''),
                ];
            }
        }

        /* ---------------- GUEST ---------------- */
        if ($wantGuest) {
            $sql = "
                SELECT
                    ''                                      AS id,
                    gs.guest_name                           AS name,
                    DATE(gs.subscription_date)              AS date_val,
                    'GUEST'                                 AS emp_type,
                    mt.name                                 AS meal_type,
                    cf.name                                 AS location
                FROM guest_subscriptions gs
                LEFT JOIN meal_types mt ON mt.id = gs.meal_type_id
                LEFT JOIN cafeterias cf ON cf.id = gs.cafeteria_id
                WHERE DATE(gs.subscription_date) = ?
                AND gs.status IN ('ACTIVE','REDEEMED')
            ";
            $binds = [$date];
            if ($mealTypeId)  { $sql .= " AND gs.meal_type_id = ? ";  $binds[] = $mealTypeId; }
            if ($cafeteriaId) { $sql .= " AND gs.cafeteria_id  = ? "; $binds[] = $cafeteriaId; }

            foreach ($db->query($sql, $binds)->getResultArray() as $r) {
                $rows[] = [
                    'id'        => '',
                    'name'      => (string) ($r['name'] ?? ''),
                    'date_val'  => (string) ($r['date_val'] ?? ''),
                    'emp_type'  => 'GUEST',
                    'meal_type' => (string) ($r['meal_type'] ?? ''),
                    'location'  => (string) ($r['location'] ?? ''),
                ];
            }
        }

        usort($rows, fn($a,$b) => [$a['emp_type'],$a['name'],$a['id']] <=> [$b['emp_type'],$b['name'],$b['id']]);

        return $this->response->setBody(
            view('admin/report/daily_meal_report', array_merge(
                $this->getLookups(),
                [
                    'title'   => 'Daily Meal Report',
                    'filters' => [
                        'date'         => $date,
                        'type'         => $type,
                        'meal_type_id' => $mealTypeId,
                        'cafeteria_id' => $cafeteriaId,
                    ],
                    'rows'            => $rows,
                    'employmentTypes' => array_map(static fn($n) => ['name' => $n], $allTypes),
                ]
            ))
        );
    }





    /** 5) Food Consumption Report */
    public function foodConsumptionReport(): \CodeIgniter\HTTP\ResponseInterface
    {
        $date        = trim((string) ($this->request->getGet('date') ?? '')); // YYYY-MM-DD (optional)
        $month       = (int) ($this->request->getGet('month') ?? date('n'));
        $year        = (int) ($this->request->getGet('year')  ?? date('Y'));
        $type        = strtoupper(trim((string) ($this->request->getGet('type') ?? ''))); // value from employment_types.name or 'GUEST'
        $mealTypeId  = (int) ($this->request->getGet('meal_type_id') ?? 0);
        $cafeteriaId = (int) ($this->request->getGet('cafeteria_id') ?? 0);

        // Period: prefer exact date; else use month+year
        if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $start = $date;
            $end   = $date;
        } else {
            $start = sprintf('%04d-%02d-01', $year, $month);
            $end   = (new \DateTimeImmutable($start))->modify('last day of this month')->format('Y-m-d');
        }

        $db = db_connect();

        // Active employment types (used for INTERN filter + dropdown)
        $employmentTypes = $db->table('employment_types')
            ->select('name')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();
        $etypeNames = array_map(static fn($r) => (string) $r['name'], $employmentTypes);

        // Merge with static top-levels
        $staticTypes = ['EMPLOYEE', 'GUEST'];
        $allTypes    = array_values(array_unique(array_merge($staticTypes, $etypeNames)));

        // Which sections to include?
        $wantEmp    = ($type === '' || $type === 'EMPLOYEE');
        $wantGuest  = ($type === '' || $type === 'GUEST');
        $wantIntern = ($type === '' || in_array($type, $etypeNames, true));

        $parts = [];
        $binds = [];

        /* -------- employees (meal_subscription_details) -------- */
        if ($wantEmp) {
            $p  = [];
            $p[] = "SELECT DATE(msd.subscription_date) d, msd.cafeteria_id caf_id,
                        SUM(CASE WHEN msd.status='ACTIVE'   THEN 1 ELSE 0 END) active_cnt,
                        SUM(CASE WHEN msd.status='REDEEMED' THEN 1 ELSE 0 END) redeemed_cnt
                    FROM meal_subscription_details msd
                    JOIN meal_subscriptions ms ON ms.id = msd.subscription_id
                    WHERE ms.status = 'ACTIVE'
                    AND msd.status IN ('ACTIVE','REDEEMED')             -- EXCLUDE CANCELLED
                    AND msd.subscription_date BETWEEN ? AND ?";
            $pb = [$start, $end];

            if ($mealTypeId)  { $p[] = "AND msd.meal_type_id = ?";  $pb[] = $mealTypeId; }
            if ($cafeteriaId) { $p[] = "AND msd.cafeteria_id = ?"; $pb[] = $cafeteriaId; }

            // Only narrow when explicitly picking EMPLOYEE
            if ($type === 'EMPLOYEE') {
                $p[]  = "AND ms.subscription_type = ?";
                $pb[] = 'EMPLOYEE';
            }

            $p[] = "GROUP BY d, msd.cafeteria_id";
            $parts[] = implode("\n", $p);
            array_push($binds, ...$pb);
        }

        /* -------- interns (intern_subscriptions + employment_types) -------- */
        if ($wantIntern) {
            $p  = [];
            $p[] = "SELECT DATE(isub.subscription_date) d, isub.cafeteria_id caf_id,
                        SUM(CASE WHEN isub.status='ACTIVE'   THEN 1 ELSE 0 END) active_cnt,
                        SUM(CASE WHEN isub.status='REDEEMED' THEN 1 ELSE 0 END) redeemed_cnt
                    FROM intern_subscriptions isub
                    LEFT JOIN employment_types et ON et.id = isub.employment_type_id
                    WHERE isub.status IN ('ACTIVE','REDEEMED')            -- EXCLUDE CANCELLED
                    AND isub.subscription_date BETWEEN ? AND ?";
            $pb = [$start, $end];

            if ($mealTypeId)  { $p[] = "AND isub.meal_type_id = ?";  $pb[] = $mealTypeId; }
            if ($cafeteriaId) { $p[] = "AND isub.cafeteria_id = ?"; $pb[] = $cafeteriaId; }

            // If a specific employment type (from table) was chosen, filter by that ET name
            if ($type !== '' && in_array($type, $etypeNames, true)) {
                $p[]  = "AND et.name = ?";
                $pb[] = $type;
            }

            $p[] = "GROUP BY d, isub.cafeteria_id";
            $parts[] = implode("\n", $p);
            array_push($binds, ...$pb);
        }

        /* -------- guests -------- */
        if ($wantGuest) {
            $p  = [];
            $p[] = "SELECT DATE(gs.subscription_date) d, gs.cafeteria_id caf_id,
                        SUM(CASE WHEN gs.status='ACTIVE'   THEN 1 ELSE 0 END) active_cnt,
                        SUM(CASE WHEN gs.status='REDEEMED' THEN 1 ELSE 0 END) redeemed_cnt
                    FROM guest_subscriptions gs
                    WHERE gs.status IN ('ACTIVE','REDEEMED')              -- EXCLUDE CANCELLED
                    AND gs.subscription_date BETWEEN ? AND ?";
            $pb = [$start, $end];

            if ($mealTypeId)  { $p[] = "AND gs.meal_type_id = ?";  $pb[] = $mealTypeId; }
            if ($cafeteriaId) { $p[] = "AND gs.cafeteria_id = ?"; $pb[] = $cafeteriaId; }

            $p[] = "GROUP BY d, gs.cafeteria_id";
            $parts[] = implode("\n", $p);
            array_push($binds, ...$pb);
        }

        $rows = [];
        if (! empty($parts)) {
            $union = implode("\nUNION ALL\n", $parts);
            $sql = "
                SELECT t.d, t.caf_id, c.name AS location,
                    SUM(t.active_cnt)   AS subscription_count,
                    SUM(t.redeemed_cnt) AS consumption_count
                FROM ( $union ) t
                LEFT JOIN cafeterias c ON c.id = t.caf_id
                GROUP BY t.d, t.caf_id, c.name
                ORDER BY t.d ASC, c.name ASC
            ";
            $data = $db->query($sql, $binds)->getResultArray();

            foreach ($data as $r) {
                $ts = strtotime($r['d']);
                $rows[] = [
                    'date'               => date('j-M-y', $ts),
                    'month'              => date('M', $ts),
                    'year'               => date('Y', $ts),
                    'subscription_count' => (int) $r['subscription_count'],
                    'consumption_count'  => (int) $r['consumption_count'],
                    'location'           => (string) ($r['location'] ?? ''),
                ];
            }
        }

        return $this->response->setBody(
            view('admin/report/food_consumption_report', array_merge(
                $this->getLookups(),
                [
                    'title'           => 'Food Consumption Report',
                    'filters'         => [
                        'date'         => $date,
                        'month'        => ($date !== '') ? (int) date('n', strtotime($date)) : $month,
                        'year'         => ($date !== '') ? (int) date('Y', strtotime($date)) : $year,
                        'type'         => $type,
                        'meal_type_id' => $mealTypeId,
                        'cafeteria_id' => $cafeteriaId,
                    ],
                    'rows'            => $rows,
                    'employmentTypes' => array_map(static fn($n) => ['name' => $n], $allTypes),
                ]
            ))
        );
    }



}
