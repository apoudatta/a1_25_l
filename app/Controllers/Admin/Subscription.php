<?php namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MealSubscriptionModel;
use App\Models\CutoffTimeModel;
use App\Models\PublicHolidayModel;
use App\Models\MealTypeModel;
use App\Models\CafeteriaModel;
use App\Models\UserModel;

class Subscription extends BaseController
{
    protected $subs;
    protected $cutoffTimes;
    protected $holidays;
    protected $mealTypes;
    protected $cafeterias;
    protected $users;
    protected $db;

    public function __construct()
    {
        $this->subs        = new MealSubscriptionModel();
        $this->cutoffTimes = new CutoffTimeModel();
        $this->holidays    = new PublicHolidayModel();
        $this->mealTypes   = new MealTypeModel();
        $this->cafeterias  = new CafeteriaModel();
        $this->users       = new UserModel();
        $this->db          = db_connect();
    }

    /** GET /subscription/new */
    public function new()
    {
        // Default to Lunch (id = 1) like before
        $mealTypeId = 1;

        // Active cutoff row for this meal
        $cut = $this->cutoffTimes
            ->select('max_horizon_days, cut_off_time, lead_days')
            ->where('is_active', 1)
            ->where('cutoff_date', null)
            ->where('meal_type_id', $mealTypeId)
            ->orderBy('updated_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();

        $cutoffDays = (int)($cut['max_horizon_days'] ?? 30);
        $cutOffTime = (string)($cut['cut_off_time'] ?? '22:00:00');
        $leadDays   = (int)($cut['lead_days'] ?? 0);

        // Dates already registered by this user (to shade in the calendar)
        $userId = (int)(session('user_id') ?? 0);
        $registeredDates = array_column(
            $this->subs
                ->select('subs_date')
                ->where('user_id', $userId)
                ->where('meal_type_id', $mealTypeId)
                ->whereIn('status', ['ACTIVE','PENDING','REDEEMED'])
                ->findAll(),
            'subs_date'
        );

        // Public holidays
        $publicHolidays = array_column(
            $this->holidays->where('is_active', 1)->findAll(),
            'holiday_date'
        );

        return view('admin/subscription/new', [
            'cutoffDays'      => $cutoffDays,
            'cutOffTime'      => $cutOffTime,
            'leadDays'        => $leadDays,
            'registeredDates' => $registeredDates,
            'publicHolidays'  => $publicHolidays,
            'mealTypes'       => $this->mealTypes->where('id', $mealTypeId)->findAll(),
            'cafeterias'      => $this->cafeterias->where('is_active', 1)->findAll(),
            'validation'      => \Config\Services::validation(),
        ]);
    }

    /** POST /admin/subscription/store */
    public function store()
    {
        // --------------- validate ---------------
        $rules = [
            'meal_type_id' => 'required|integer',
            'meal_dates'   => 'required|string',   // CSV from flatpickr
            'cafeteria_id' => 'required|integer',
            'employee_id'  => 'required|integer',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('validation', $this->validator);
        }

        $userId      = (int) $this->request->getPost('employee_id');
        $mealTypeId  = (int) $this->request->getPost('meal_type_id');
        $cafeteriaId = (int) $this->request->getPost('cafeteria_id');
        $rawDates    = explode(',', (string) $this->request->getPost('meal_dates'));
        $empTypeId   = 1; // EMPLOYEE (ALL employees)

        $tz = new \DateTimeZone('Asia/Dhaka');

        // Parse "d/m/Y" -> "Y-m-d"
        $dates = [];
        foreach ($rawDates as $r) {
            $r = trim($r);
            if ($r === '') continue;
            $dt = \DateTime::createFromFormat('d/m/Y', $r, $tz);
            if (! $dt) {
                return redirect()->back()->withInput()->with('error', "Invalid date format: {$r}");
            }
            $dates[] = $dt->format('Y-m-d');
        }
        if (!$dates) {
            return redirect()->back()->withInput()->with('error', 'Please select at least one date.');
        }

        // ---------------- cutoff window ----------------
        $cut = $this->cutoffTimes
            ->select('max_horizon_days, lead_days, cut_off_time')
            ->where('is_active', 1)
            ->where('cutoff_date', null)
            ->where('meal_type_id', $mealTypeId)
            ->orderBy('updated_at', 'DESC')->orderBy('id', 'DESC')
            ->first();

        $now   = new \DateTime('now', $tz);
        $start = (clone $now)->modify('+' . (int) ($cut['lead_days'] ?? 0) . ' days')->setTime(0, 0, 0);
        $end   = (clone $now)->modify('+' . (int) ($cut['max_horizon_days'] ?? 30) . ' days')->setTime(23, 59, 59);

        // Public holidays + weekly holidays
        $holidays = array_column($this->holidays->where('is_active', 1)->findAll(), 'holiday_date');

        foreach ($dates as $dstr) {
            $d = \DateTime::createFromFormat('Y-m-d', $dstr, $tz);
            if (!$d) {
                return redirect()->back()->withInput()->with('error', "Invalid date: {$dstr}");
            }
            if ($d < $start || $d > $end) {
                return redirect()->back()->withInput()->with('error', "Date {$dstr} is outside the allowed window.");
            }
            if (in_array($dstr, $holidays, true)) {
                return redirect()->back()->withInput()->with('error', "Cannot subscribe on public holiday: {$dstr}");
            }
            // Friday (5) & Saturday (6)
            if (in_array((int) $d->format('w'), [5, 6], true)) {
                return redirect()->back()->withInput()->with('error', "Cannot subscribe on weekly holiday: {$dstr}");
            }
        }

        // Block duplicates (ACTIVE|PENDING) for those dates
        $already = $this->subs
            ->where('user_id', $userId)
            ->where('meal_type_id', $mealTypeId)
            ->whereIn('status', ['ACTIVE', 'PENDING'])
            ->whereIn('subs_date', $dates)
            ->countAllResults();

        if ($already > 0) {
            return redirect()->back()->withInput()->with('error', 'Some of the dates are already subscribed.');
        }

        // Pick status from approval flow (emp_type_id = 0 => ALL; here you set 1 for EMPLOYEE)
        $flow = $this->db->table('approval_flows')
            ->where('is_active', 1)
            ->where('meal_type_id', $mealTypeId)
            ->where('emp_type_id', $empTypeId)
            ->orderBy('effective_date', 'DESC')
            ->get(1)->getFirstRow('array');

        $status = ($flow && strtoupper($flow['type']) === 'MANUAL') ? 'PENDING' : 'ACTIVE';

        // User share from contributions (emp_type_id ALL; cafeteria match or NULL)
        $userTk = $this->resolveUserTk($mealTypeId, $empTypeId, $cafeteriaId);
        if ($userTk === null) {
            return redirect()->back()->withInput()->with('error', 'Please configure Contributions for this meal type first.');
        }

        // ---------------- insert subscriptions + approvals in a TX ----------------
        $db = $this->db;
        $db->transStart();

        $rows   = [];
        $nowStr = date('Y-m-d H:i:s');
        foreach ($dates as $dstr) {
            $rows[] = [
                'user_id'      => $userId,
                'meal_type_id' => $mealTypeId,
                'emp_type_id'  => $empTypeId,                 // ALL employees bucket
                'cafeteria_id' => $cafeteriaId,
                'subs_date'    => $dstr,
                'status'       => $status,
                'price'        => number_format((float) $userTk, 2, '.', ''),
                'created_by'   => (int) (session('user_id') ?? 0),
                'unsubs_by'    => 0,
                'created_at'   => $nowStr,
                'updated_at'   => $nowStr,
            ];
        }
        if (! empty($rows)) {
            $this->subs->insertBatch($rows);
        }

        // ---------- create approvals for PENDING only ----------
        if ($status === 'PENDING') {

            // get the first step of this flow (if configured)
            $step = $flow
                ? $db->table('approval_steps')
                    ->where('flow_id', (int) $flow['id'])
                    ->orderBy('step_order', 'ASC')
                    ->get(1)->getFirstRow('array')
                : null;

            // Resolve approver target
            $approverRole   = null;
            $approverUserId = null;

            if ($step) {
                $type = strtoupper((string) $step['approver_type']);
                if ($type === 'ROLE' && !empty($step['approver_role'])) {
                    $approverRole = (int) $step['approver_role'];
                } elseif ($type === 'USER' && !empty($step['approver_user_id'])) {
                    $approverUserId = (int) $step['approver_user_id'];
                } elseif ($type === 'LINE_MANAGER') {
                    // find user's line manager
                    $lm = $db->table('users')->select('line_manager_id')->where('id', $userId)->get()->getFirstRow('array');
                    if (!empty($lm['line_manager_id'])) {
                        $approverUserId = (int) $lm['line_manager_id'];
                    } elseif (!empty($step['fallback_role'])) {
                        $approverRole = (int) $step['fallback_role'];
                    }
                }
            }            

            // IDs we just created with PENDING status
            $subsIds = array_column(
                $db->table('meal_subscriptions')
                    ->select('id')
                    ->where('user_id', $userId)
                    ->where('meal_type_id', $mealTypeId)
                    ->where('cafeteria_id', $cafeteriaId)
                    ->whereIn('subs_date', $dates)
                    ->where('status', 'PENDING')
                    ->get()->getResultArray(),
                'id'
            );

            if ($subsIds) {
                // skip ones that already have an approval row
                $existingIds = array_column(
                    $db->table('meal_approvals')->select('subs_id')->whereIn('subs_id', $subsIds)->get()->getResultArray(),
                    'subs_id'
                );
                $subsToCreate = array_values(array_diff($subsIds, $existingIds));

                if ($subsToCreate) {
                    $apRows = [];
                    foreach ($subsToCreate as $sid) {
                        $apRows[] = [
                            'subs_id'         => (int) $sid,
                            'approver_role'   => $approverRole,           // may be null
                            'approver_user_id'=> $approverUserId,        // may be null
                            'approved_by'     => 0,
                            'approval_status' => 'PENDING',
                            'approved_at'     => null,
                            'created_at'      => $nowStr,
                            'updated_at'      => $nowStr,
                        ];
                    }
                    $db->table('meal_approvals')->insertBatch($apRows);
                }
            }
        }

        $db->transComplete();
        if ($db->transStatus() === false) {
            return redirect()->back()->withInput()->with('error', 'Failed to save subscription.');
        }

        return redirect()->to('subscription')->with('success', 'Subscription submitted.');
    }


    /** GET /admin/subscription (My subscriptions) */
    public function history()
    {
        $uid = (int)(session('user_id') ?? 0);

        // Last 1 year window based on created_at (Asia/Dhaka)
        $tz         = new \DateTimeZone('Asia/Dhaka');
        $oneYearAgo = (new \DateTime('now', $tz))->modify('-1 year')->format('Y-m-d H:i:s');

        $rows = $this->subs
            ->select("meal_subscriptions.*,
                    meal_subscriptions.subs_date AS subscription_date,
                    users.name,
                    users.employee_id,
                    meal_types.name AS meal_type_name,
                    cafeterias.name AS caffname,
                    ct.cut_off_time AS cutoff_time,
                    ct.lead_days AS lead_days", false)
            ->join('users', 'users.id = meal_subscriptions.user_id', 'left')
            ->join('meal_types', 'meal_types.id = meal_subscriptions.meal_type_id', 'left')
            ->join('cafeterias', 'cafeterias.id = meal_subscriptions.cafeteria_id', 'left')
            ->join('cutoff_times ct', 'ct.meal_type_id = meal_subscriptions.meal_type_id AND ct.is_active = 1', 'left')
            ->where('meal_subscriptions.user_id', $uid)
            ->where('meal_subscriptions.meal_type_id', 1) // keep Lunch filter as before
            ->where('meal_subscriptions.created_at >=', $oneYearAgo)
            ->orderBy('meal_subscriptions.created_at','DESC')
            ->orderBy('meal_subscriptions.id','DESC')
            ->findAll();

        return view('admin/subscription/subscription_view', ['subs' => $rows]);
    }


    /** GET /admin/subscription/all-subscriptions */
    public function allSubscriptions()
    {
        // Last 1 year window based on created_at
        $tz         = new \DateTimeZone('Asia/Dhaka');
        $oneYearAgo = (new \DateTime('now', $tz))->modify('-1 year')->format('Y-m-d H:i:s');

        $rows = $this->subs
            ->select("meal_subscriptions.*,
                    meal_subscriptions.subs_date AS subscription_date,
                    users.employee_id,
                    users.name,
                    meal_types.name AS meal_type_name,
                    cafeterias.name AS caffname,
                    ct.cut_off_time AS cutoff_time,
                    ct.lead_days AS lead_days", false)
            ->join('users', 'users.id = meal_subscriptions.user_id', 'left')
            ->join('meal_types', 'meal_types.id = meal_subscriptions.meal_type_id', 'left')
            ->join('cafeterias', 'cafeterias.id = meal_subscriptions.cafeteria_id', 'left')
            ->join('cutoff_times ct', 'ct.meal_type_id = meal_subscriptions.meal_type_id AND ct.is_active = 1', 'left')
            ->where('meal_subscriptions.status <>', 'REDEEMED')
            ->where('meal_subscriptions.meal_type_id', 1)
            ->where('meal_subscriptions.created_at >=', $oneYearAgo)
            ->orderBy('meal_subscriptions.created_at','DESC')
            ->orderBy('meal_subscriptions.id','DESC')
            ->findAll();

        return view('admin/subscription/all_subscription', ['subs' => $rows]);
    }


    /** GET /employees/active-list (unchanged; used by the form’s select) */
    public function activeList()
    {
        $employees = $this->users
            ->where('status', 'ACTIVE')
            ->where('user_type', 'EMPLOYEE')
            ->select('id, name')
            ->orderBy('name')
            ->findAll();

        return $this->response->setJSON($employees);
    }

    /** POST /subscription/unsubscribe_single/(:num) */
    public function unsubscribeSingle($id)
    {
        $id = (int)$id;
        $ok = $this->subs->update($id, [
            'status'     => 'CANCELLED',
            'unsubs_by'  => (int)(session('user_id') ?? 0),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()
            ->with($ok ? 'success' : 'error', $ok ? 'Unsubscribed.' : 'Unable to unsubscribe.');
    }

    /** POST /subscription/unsubscribe_bulk */
    public function unsubscribe_bulk()
    {
        $ids    = array_values(array_filter((array) $this->request->getPost('subscription_ids'), 'is_numeric'));
        $remark = trim((string) $this->request->getPost('remark'));

        if (empty($ids)) {
            return redirect()->back()->with('error', 'No subscriptions selected.');
        }

        $now = date('Y-m-d H:i:s');

        // 1) Bulk cancel subscriptions
        $this->subs
            ->whereIn('id', $ids)
            ->set('status', 'CANCELLED')
            ->set('updated_at', $now)
            ->set('unsubs_by', session('user_id') ?? 0)
            ->update();

        // 2) Log a remark row for each subscription (AUTO_INCREMENT id)
        $db      = db_connect();
        $builder = $db->table('remarks');

        $rows = [];
        foreach ($ids as $sid) {
            $rows[] = [
                'subs_id'         => (int) $sid,
                'remark'          => $remark,
                'created_at'      => $now,
            ];
        }
        if ($rows) {
            $builder->insertBatch($rows);
        }

        return redirect()->back()->with('success', 'Selected subscriptions unsubscribed.');
    }



    // --- Helper: user share from meal_contributions ---
    /**
     * Find the best matching user contribution (user share) for a meal type.
     * Priority:
     * 1) exact emp_type + exact cafeteria
     * 2) exact emp_type + cafeteria NULL
     * 3) emp_type = ALL(0) + exact cafeteria
     * 4) emp_type = ALL(0) + cafeteria NULL
     * Returns float or null when not configured.
     */
    private function resolveUserTk(int $mealTypeId, int $empTypeId, ?int $cafeteriaId): ?float
    {
        $b = $this->db->table('meal_contributions')
            ->select('user_tk')
            ->where('is_active', 1)
            ->where('meal_type_id', $mealTypeId);

        // ---- Preference ordering (no backticks) ----
        // exact emp_type first, then ALL(0)
        $b->orderBy("(emp_type_id = {$empTypeId})", 'DESC', false);

        // exact cafeteria first, then NULL
        if ($cafeteriaId === null) {
            $b->orderBy("(cafeteria_id IS NULL)", 'DESC', false);
        } else {
            $cafeteriaId = (int) $cafeteriaId;
            $b->orderBy("(cafeteria_id = {$cafeteriaId})", 'DESC', false);
        }

        // stable last tie-breaker
        $b->orderBy('id', 'DESC');

        // ---- Filters (match exact/NULL combos) ----
        if ($cafeteriaId === null) {
            $b->groupStart()
                ->where('cafeteria_id', null)
            ->groupEnd();
        } else {
            $b->groupStart()
                ->where('cafeteria_id', $cafeteriaId)
                ->orWhere('cafeteria_id', null)
            ->groupEnd();
        }

        $b->groupStart()
            ->where('emp_type_id', $empTypeId)
            ->orWhere('emp_type_id', 0)   // ALL
        ->groupEnd();

        $row = $b->get(1)->getFirstRow('array');
        return $row ? (float) $row['user_tk'] : null;
    }

}
