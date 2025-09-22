<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class ReportController extends BaseController
{
    /** Fetch dropdown data once */
    private const EMP_TYPES = [1];
    private const GUEST_TYPES = [8,9,10];
    private const INTERN_TYPES = [2,3,4,5,6];
    protected \CodeIgniter\Database\BaseConnection $db;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect(); // or db_connect();
    }

    private function getLookups() {
        return [
            'employmentTypes' => $this->getEmploymentTypes(),
            'mealTypes'       => $this->getMealTypes(),
            'cafeterias'      => $this->getCafeterias(),
        ];
    }
    private function getEmploymentTypes() {
        return $this->db->table('employment_types')
        ->select('id, name')
        ->where('is_active', 1)
        ->orderBy('name', 'ASC')
        ->get()
        ->getResultArray();
    }
    private function getMealTypes() {
        return $this->db->table('meal_types')
            ->select('id, name')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();
    }
    private function getCafeterias() {
        return $this->db->table('cafeterias')
            ->select('id, name')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();
    }

    /** Default landing – redirect to first report */
    public function index()
    {
        return redirect()->to(site_url('report/meal-charge-list-for-payroll'));
    }

    /** 1) Meal Charge list for payroll */
    public function mealChargeListForPayroll(): \CodeIgniter\HTTP\ResponseInterface
    {
        $month = (int) ($this->request->getGet('month') ?? date('n'));   // 1..12
        $year  = (int) ($this->request->getGet('year')  ?? date('Y'));
        $empId = trim((string) $this->request->getGet('employee_id'));

        // Selected type from UI (value = employment_types.id)
        $typeRaw = (string) ($this->request->getGet('type') ?? '');
        $typeId  = ctype_digit($typeRaw) ? (int) $typeRaw : 0;

        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = (new \DateTimeImmutable($start))->modify('last day of this month')->format('Y-m-d');
        $monthYearLabel = date("F'Y", strtotime($start));

        $db = db_connect();

        // Main query
        $b  = $db->table('meal_subscriptions ms')
            ->select("
                u.employee_id                           AS emp_id,
                COALESCE(u.name,'')                     AS emp_name,
                COALESCE(u.designation,'')              AS designation,
                COALESCE(u.division, '')                AS division,
                u.department                            AS job_location,
                COUNT(ms.id)                            AS day_count,
                ROUND(SUM(COALESCE(ms.price,0)),2)      AS meal_charge
            ", false)
            ->join('users u', 'u.id = ms.user_id', 'left')
            ->where('ms.subs_date >=', $start)
            ->where('ms.subs_date <=', $end)
            ->whereIn('ms.status', ['ACTIVE','REDEEMED'])
            ->groupBy('u.id')
            ->orderBy('u.employee_id', 'ASC');

        if ($empId !== '') {
            $b->where('u.employee_id', $empId);
        }
        if ($typeId > 0) {
            // assumes ms.emp_type_id references employment_types.id
            $b->where('ms.emp_type_id', $typeId);
        }

        $rows = $b->get()->getResultArray();

        foreach ($rows as &$r) {
            $r['month_year'] = $monthYearLabel; // add month label
        }
        unset($r);

        $payload = [
            'title'   => "Meal Charge list for payroll",
            'filters' => [
                'month'       => $month,
                'year'        => $year,
                'employee_id' => $empId,
                'type'        => $typeRaw, // keep original (string) for the form
            ],
            'rows'             => $rows,
            'employmentTypes'  => $this->getEmploymentTypes(),
        ];

        return $this->response->setBody(view('report/meal_charge_list_for_payroll', $payload));
    }

    


    /** 2) Meal Report for billing (e.g., vendor/cafeteria billing) */
    public function mealReportForBilling(): \CodeIgniter\HTTP\ResponseInterface
    {
        $type = (string) ($this->request->getGet('type') ?? '');
        $typeId  = ctype_digit($type) ? (int) $type : 0;

        
        
        $month = (int) ($this->request->getGet('month') ?? date('n'));   // 1..12
        $year  = (int) ($this->request->getGet('year')  ?? date('Y'));
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = (new \DateTimeImmutable($start))->modify('last day of this month')->format('Y-m-d');
        

        
        // Aggregate by employee for the month using single-table ms
        $empRows = $this->mealReportQueryForBilling(self::EMP_TYPES, $start, $end, $typeId);
        $internRows = $this->mealReportQueryForBilling(self::INTERN_TYPES, $start, $end, $typeId);
        $guestRows = $this->mealReportQueryForBilling(self::GUEST_TYPES, $start, $end, $typeId);

        $payload = [
            'title'      => "Meal Report for billing",
            'filters'    => [
                'month' => $month,
                'year'  => $year,
                'type'  => $type,
            ],
            'rowsEmp'    => $empRows,
            'rowsIntern' => $internRows,
            'rowsGuest'  => $guestRows,
            'employmentTypes'  => $this->getEmploymentTypes(),
        ];

        return $this->response->setBody(view('report/meal_report_for_billing', $payload));
    }

    private function mealReportQueryForBilling($empTypeId, $start, $end, $typeId = 0): array
    {
        $db = db_connect();
        $monthYearLabel = date("F'Y", strtotime($start));

        $q = $db->table('meal_subscriptions ms')
            ->select("
                u.employee_id  AS emp_id,
                u.name         AS emp_name,
                u.designation  AS designation,
                u.division     AS division,
                u.department   AS department,
                u.phone        AS phone,
                c.name         AS caff_name,
                et.name        AS emp_type_name,
                mr.ref_id,
                mr.ref_name,
                COUNT(ms.id)   AS day_count,
                ROUND(SUM(COALESCE(ms.price,0)),2) AS full_meal_cost
            ", false)
            ->join('users u',           'u.id = ms.user_id',       'left')
            ->join('meal_reference mr', 'mr.subs_id = ms.id',      'left')
            ->join('cafeterias c',      'c.id = ms.cafeteria_id',  'left')
            ->join('employment_types et', 'et.id = ms.emp_type_id', 'left')
            ->where('ms.subs_date >=', $start)
            ->where('ms.subs_date <=', $end)
            ->whereIn('ms.emp_type_id', $empTypeId)
            ->whereIn('ms.status', ['ACTIVE','REDEEMED'])
            ->groupBy('u.id, u.employee_id, u.name, u.designation, u.division, u.department, u.phone, c.name, et.name, mr.ref_id, mr.ref_name') // Include non-aggregated columns in GROUP BY
            ->orderBy('u.employee_id', 'ASC');

        if ($typeId > 0) {
            $q->where('ms.emp_type_id', $typeId);
        }

        $rows = $q->get()->getResultArray();

        foreach ($rows as &$r) {
            $r['month_year'] = $monthYearLabel;
        }
        unset($r);

        return $rows;
    }




    /** 3) Meal Detail Report (line items) */
    public function mealDetailReport(): \CodeIgniter\HTTP\ResponseInterface
    {
        $empId = trim((string) $this->request->getGet('employee_id'));
        $month = $this->request->getGet('month');                       // 1..12
        $year  = (int) ($this->request->getGet('year') ?? date('Y'));   // default current year

        $monthInt = ($month !== null && $month !== '') ? (int) $month : null;

        $db          = db_connect();
        $headerDates = [];
        $row         = null;

        // Only when employee_id AND month are provided
        if ($empId !== '' && $monthInt) {
            $start = sprintf('%04d-%02d-01', $year, $monthInt);
            $end   = (new \DateTimeImmutable($start))->modify('last day of this month')->format('Y-m-d');

            $dates = $db->table('meal_subscriptions ms')
                ->select('ms.subs_date AS d', false)
                ->join('users u', 'u.id = ms.user_id', 'left')
                ->where('u.employee_id', $empId)
                ->where('ms.subs_date >=', $start)
                ->where('ms.subs_date <=', $end)
                ->whereIn('ms.status', ['ACTIVE','REDEEMED'])
                ->orderBy('ms.subs_date', 'ASC')
                ->get()->getResultArray();

            foreach ($dates as $d) {
                $headerDates[] = date('j-M-y', strtotime($d['d'])); // e.g., 1-Jan-25
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

        return $this->response->setBody(view('report/meal_detail_report', $payload));
    }



    /** 4) Daily Meal Report (by day) */
    public function dailyMealReport(): \CodeIgniter\HTTP\ResponseInterface
    {
        // Default to today if not provided
        $date = trim((string) ($this->request->getGet('date') ?? ''));
        if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $empTypeId   = (int) ($this->request->getGet('type') ?? 0);
        $mealTypeId  = (int) ($this->request->getGet('meal_type_id') ?? 0);
        $cafeteriaId = (int) ($this->request->getGet('cafeteria_id') ?? 0);

        $db = db_connect();
        $b  = $db->table('meal_subscriptions ms')
            ->select("
                ms.id,
                ms.subs_date,
                ms.status,
                ms.price,
                u.employee_id  AS emp_id,
                u.name         AS emp_name,
                mt.name        AS meal_type,
                c.name         AS location,
                et.name        AS emp_type
            ", false)
            ->join('users u',       'u.id = ms.user_id', 'left')
            ->join('meal_types mt', 'mt.id = ms.meal_type_id', 'left')
            ->join('employment_types et', 'et.id = ms.emp_type_id', 'left')
            ->join('cafeterias c',  'c.id = ms.cafeteria_id', 'left')
            ->where('ms.subs_date', $date);

        if ($empTypeId > 0)  $b->where('ms.emp_type_id', $empTypeId);
        if ($mealTypeId > 0)  $b->where('ms.meal_type_id', $mealTypeId);
        if ($cafeteriaId > 0) $b->where('ms.cafeteria_id', $cafeteriaId);

        $rows = $b->orderBy('u.name', 'ASC')->orderBy('ms.id', 'ASC')->get()->getResultArray();

        return $this->response->setBody(
            view('report/daily_meal_report', array_merge(
                $this->getLookups(),
                [
                    'title'   => 'Daily Meal Report',
                    'filters' => [
                        'date'         => $date,
                        'emp_type_id'  => $empTypeId,
                        'meal_type_id' => $mealTypeId,
                        'cafeteria_id' => $cafeteriaId,
                    ],
                    'rows'    => $rows,
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
        $empTypeId   = (int) ($this->request->getGet('emp_type_id') ?? 0);
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
        $b  = $db->table('meal_subscriptions ms')
            ->select("
                ms.subs_date,
                c.name AS location,
                COUNT(*)                                   AS subscription_count,
                SUM(CASE WHEN ms.status = 'REDEEMED' THEN 1 ELSE 0 END) AS consumption_count
            ", false)
            ->join('cafeterias c', 'c.id = ms.cafeteria_id', 'left')
            ->where('ms.subs_date >=', $start)
            ->where('ms.subs_date <=', $end)
            ->groupBy('ms.subs_date')
            ->groupBy('ms.cafeteria_id');

        if ($empTypeId > 0)  $b->where('ms.emp_type_id', $empTypeId);
        if ($mealTypeId > 0)  $b->where('ms.meal_type_id', $mealTypeId);
        if ($cafeteriaId > 0) $b->where('ms.cafeteria_id', $cafeteriaId);

        $data = $b->orderBy('ms.subs_date', 'ASC')->orderBy('c.name', 'ASC')->get()->getResultArray();

        $rows = [];
        foreach ($data as $r) {
            $ts = strtotime($r['subs_date']);
            $rows[] = [
                'subs_date'          => (string) $r['subs_date'],
                'month'              => date('M', $ts),
                'year'               => date('Y', $ts),
                'subscription_count' => (int) $r['subscription_count'],
                'consumption_count'  => (int) $r['consumption_count'],
                'location'           => (string) ($r['location'] ?? ''),
            ];
        }

        return $this->response->setBody(
            view('report/food_consumption_report', array_merge(
                $this->getLookups(),
                [
                    'title'           => 'Food Consumption Report',
                    'filters'         => [
                        'date'         => $date,
                        'month'        => ($date !== '') ? (int) date('n', strtotime($date)) : $month,
                        'year'         => ($date !== '') ? (int) date('Y', strtotime($date)) : $year,
                        'emp_type_id'  => $empTypeId,
                        'meal_type_id' => $mealTypeId,
                        'cafeteria_id' => $cafeteriaId,
                    ],
                    'rows'            => $rows,
                ]
            ))
        );
    }




}
