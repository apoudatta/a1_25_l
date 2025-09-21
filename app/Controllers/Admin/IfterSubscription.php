<?php namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MealSubscriptionModel;
use App\Models\CutoffTimeModel;
use App\Models\PublicHolidayModel;
use App\Models\MealTypeModel;
use App\Models\CafeteriaModel;
use App\Models\ApprovalFlowModel;
use App\Models\ApprovalStepModel;
use App\Models\UserModel;

class IfterSubscription extends BaseController
{
    private const MEAL_TYPE_ID = 2;  // Ifter
    private const EMPLOYEE_ID  = 1;  // EMPLOYEE

    protected \CodeIgniter\Database\BaseConnection $db;

    protected MealSubscriptionModel $subs;
    protected CutoffTimeModel       $cutoffs;
    protected PublicHolidayModel    $holidays;
    protected MealTypeModel         $mealTypes;
    protected CafeteriaModel        $cafeterias;
    protected ApprovalFlowModel     $flows;
    protected ApprovalStepModel     $steps;
    protected UserModel             $users;

    public function __construct()
    {
        $this->db         = db_connect();
        $this->subs       = new MealSubscriptionModel();
        $this->cutoffs    = new CutoffTimeModel();
        $this->holidays   = new PublicHolidayModel();
        $this->mealTypes  = new MealTypeModel();
        $this->cafeterias = new CafeteriaModel();
        $this->flows      = new ApprovalFlowModel();
        $this->steps      = new ApprovalStepModel();
        $this->users      = new UserModel();
    }

    # -----------------------------------------------------------
    # GET: New form
    # -----------------------------------------------------------
    public function new()
    {
        $tz    = new \DateTimeZone('Asia/Dhaka');
        $today = (new \DateTime('now', $tz))->format('Y-m-d');

        // Ramadan window (latest row)
        $rc = $this->db->table('ramadan_config')->orderBy('id', 'DESC')->get(1)->getFirstRow('array');
        if (!$rc) {
            return redirect()->back()->with('error', 'Ramadan window is not configured yet.');
        }
        $ramadanStart = $rc['start_date'];
        $ramadanEnd   = $rc['end_date'];

        // Active cutoff (prefer generic rows with NULL cutoff_date)
        $cut = $this->cutoffs
            ->select('max_horizon_days, cut_off_time, lead_days')
            ->where('meal_type_id', self::MEAL_TYPE_ID)
            ->where('is_active', 1)
            ->orderBy('cutoff_date IS NULL', 'DESC', false)
            ->orderBy('id', 'DESC')
            ->get(1)->getFirstRow('array');

        $maxHorizon = (int)($cut['max_horizon_days'] ?? 30);
        $cutTime    = (string)($cut['cut_off_time'] ?? '22:00:00');
        $leadDays   = (int)($cut['lead_days'] ?? 1);

        $windowStart = max($today, $ramadanStart);
        $windowEnd   = min(
            $ramadanEnd,
            (new \DateTime($today, $tz))->modify("+{$maxHorizon} days")->format('Y-m-d')
        );

        $publicHolidays = $this->holidays->select('holiday_date')
            ->where('is_active', 1)
            ->where('holiday_date >=', $windowStart)
            ->where('holiday_date <=', $windowEnd)
            ->findColumn('holiday_date') ?? [];

        $registeredDates = $this->subs->select('subs_date')
            ->where('user_id', (int)session('user_id'))
            ->where('meal_type_id', self::MEAL_TYPE_ID)
            ->whereIn('status', ['ACTIVE','PENDING','REDEEMED'])
            ->where('subs_date >=', $windowStart)
            ->where('subs_date <=', $windowEnd)
            ->orderBy('subs_date', 'ASC')
            ->findColumn('subs_date') ?? [];

        return view('admin/ifter_subscription/new', [
            'reg_start_date'  => $windowStart,
            'reg_end_date'    => $windowEnd,
            'cut_off_time'    => $cutTime,
            'lead_days'       => $leadDays,
            'max_horizon'     => $maxHorizon,
            'publicHolidays'  => $publicHolidays,
            'registeredDates' => $registeredDates,
            'mealTypes'       => $this->mealTypes->where('id', self::MEAL_TYPE_ID)->findAll(),
            'cafeterias'      => $this->cafeterias->where('is_active', 1)->orderBy('name')->findAll(),
            'validation'      => \Config\Services::validation(),
        ]);
    }

    # -----------------------------------------------------------
    # POST: Save CSV dates (d/m/Y), 1 row per day into meal_subscriptions
    # -----------------------------------------------------------
    public function store()
    {
        $rules = [
            'meal_type_id' => 'required|integer',
            'cafeteria_id' => 'required|integer',
            'meal_dates'   => 'required|string',
            'remark'       => 'permit_empty|string',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $userId      = (int) session('user_id');
        $mealTypeId  = (int) $this->request->getPost('meal_type_id');
        $cafeteriaId = (int) $this->request->getPost('cafeteria_id');
        $remark      = trim((string) $this->request->getPost('remark'));
        $empTypeId   = self::EMPLOYEE_ID;

        if ($mealTypeId !== self::MEAL_TYPE_ID) {
            return redirect()->back()->withInput()->with('error', 'Invalid meal type for Ifter.');
        }

        // Parse CSV dates: d/m/Y -> Y-m-d (Asia/Dhaka)
        $tz       = new \DateTimeZone('Asia/Dhaka');
        $rawParts = array_filter(array_map('trim', explode(',', (string) $this->request->getPost('meal_dates'))));
        $dates    = [];
        foreach ($rawParts as $p) {
            $dt = \DateTime::createFromFormat('d/m/Y', $p, $tz);
            if (!$dt) {
                return redirect()->back()->withInput()->with('error', "Invalid date: {$p}");
            }
            $dates[] = $dt->format('Y-m-d');
        }
        $dates = array_values(array_unique($dates));
        sort($dates);
        if (empty($dates)) {
            return redirect()->back()->withInput()->with('error', 'No valid dates provided.');
        }

        // Ramadan window
        $rc = $this->db->table('ramadan_config')->orderBy('id','DESC')->get(1)->getFirstRow('array');
        if (!$rc) {
            return redirect()->back()->withInput()->with('error', 'Ramadan window is not configured yet.');
        }
        $ramadanStart = $rc['start_date'];
        $ramadanEnd   = $rc['end_date'];

        // Active cutoff
        $cut = $this->cutoffs
            ->select('max_horizon_days, cut_off_time, lead_days')
            ->where('meal_type_id', self::MEAL_TYPE_ID)
            ->where('is_active', 1)
            ->orderBy('cutoff_date IS NULL', 'DESC', false)
            ->orderBy('id', 'DESC')
            ->get(1)->getFirstRow('array');
        $maxHorizon = (int)($cut['max_horizon_days'] ?? 30);
        $cutTime    = (string)($cut['cut_off_time'] ?? '22:00:00');
        $leadDays   = (int)($cut['lead_days'] ?? 1);

        $today     = (new \DateTime('now', $tz))->format('Y-m-d');
        $windowEnd = min(
            $ramadanEnd,
            (new \DateTime($today, $tz))->modify("+{$maxHorizon} days")->format('Y-m-d')
        );

        // Preload holidays for checks
        $holidays = $this->holidays->select('holiday_date')
            ->where('is_active', 1)
            ->where('holiday_date >=', $today)
            ->where('holiday_date <=', $windowEnd)
            ->findColumn('holiday_date') ?? [];

        // Validate each date: window + horizon + holidays + Fri/Sat + lead/cutoff
        foreach ($dates as $d) {
            if (!$this->isDateAllowed($d, $ramadanStart, $ramadanEnd, $today, $windowEnd, $leadDays, $cutTime, $holidays)) {
                return redirect()->back()->withInput()->with('error', "Date not allowed: {$d}");
            }
        }

        // Prevent duplicates (ACTIVE/PENDING) for same date(s)
        $dupCount = $this->subs
            ->where('user_id', $userId)
            ->where('meal_type_id', self::MEAL_TYPE_ID)
            ->whereIn('status', ['ACTIVE','PENDING'])
            ->whereIn('subs_date', $dates)
            ->countAllResults();
        if ($dupCount > 0) {
            return redirect()->back()->withInput()->with('error', 'You already have subscriptions on one or more selected dates.');
        }

        // Approval flow (prefer exact empType, fallback ALL(0))
        $flow = $this->flows
            ->where('meal_type_id', self::MEAL_TYPE_ID)
            ->where('is_active', 1)
            ->groupStart()
                ->where('emp_type_id', $empTypeId)
                ->orWhere('emp_type_id', 0)
            ->groupEnd()
            ->orderBy("emp_type_id = {$empTypeId}", 'DESC', false)
            ->orderBy('effective_date', 'DESC')
            ->get(1)->getFirstRow('array');

        $status = ($flow && strtoupper((string)$flow['type']) === 'MANUAL') ? 'PENDING' : 'ACTIVE';

        // Resolve user pay (user_tk) from meal_contributions (cafeteria/null & empType/ALL fallback)
        $userTk = $this->resolveUserTk($mealTypeId, $empTypeId, $cafeteriaId);

        $now = date('Y-m-d H:i:s');
        $rows = [];
        foreach ($dates as $d) {
            $rows[] = [
                'user_id'      => $userId,
                'meal_type_id' => self::MEAL_TYPE_ID,
                'emp_type_id'  => $empTypeId,
                'cafeteria_id' => $cafeteriaId,
                'subs_date'    => $d,
                'status'       => $status,
                'price'        => $userTk,   // can be NULL if not configured
                'created_by'   => $userId,
                'unsubs_by'    => 0,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        // TX: insert subs + approvals (if manual) + remarks (optional)
        $this->db->transStart();
        $this->subs->insertBatch($rows);

        // Re-fetch IDs we just inserted
        $inserted = $this->subs->select('id, subs_date')
            ->where('user_id', $userId)
            ->where('meal_type_id', self::MEAL_TYPE_ID)
            ->where('cafeteria_id', $cafeteriaId)
            ->whereIn('subs_date', $dates)
            ->where('created_at', $now)
            ->findAll();

        // Add one remark row per subs if remark present
        if ($remark !== '' && !empty($inserted)) {
            $rb = $this->db->table('remarks');
            $remarkRows = [];
            foreach ($inserted as $it) {
                $remarkRows[] = [
                    'subs_id'        => (int)$it['id'],
                    'remark'         => $remark,
                    'approver_remark'=> null,
                    'created_at'     => $now,
                ];
            }
            if ($remarkRows) {
                $rb->insertBatch($remarkRows);
            }
        }

        // Create approvals for first step if MANUAL
        if ($status === 'PENDING' && $flow) {
            $firstStep = $this->steps
                ->where('flow_id', (int)$flow['id'])
                ->orderBy('step_order', 'ASC')
                ->get(1)->getFirstRow('array');

            if (!$firstStep) {
                $this->db->transRollback();
                return redirect()->back()->withInput()->with('error', 'Approval flow has no steps configured.');
            }

            // Resolve approver (ROLE / USER / LINE_MANAGER with fallback_role)
            [$approverRole, $approverUserId] = $this->resolveApprover($firstStep, $userId);

            $ab = $this->db->table('meal_approvals');
            $appRows = [];
            foreach ($inserted as $it) {
                $appRows[] = [
                    'subs_id'         => (int)$it['id'],
                    'approver_role'   => $approverRole,
                    'approver_user_id'=> $approverUserId,
                    'approved_by'     => 0,
                    'approval_status' => 'PENDING',
                    'approved_at'     => null,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }
            if ($appRows) {
                $ab->insertBatch($appRows);
            }
        }

        $this->db->transComplete();
        if ($this->db->transStatus() === false) {
            return redirect()->back()->withInput()->with('error', 'Failed to save subscriptions.');
        }

        $msg = ($status === 'PENDING') ? 'Subscriptions pending approval.' : 'Subscriptions active.';
        return redirect()->to('admin/ifter-subscription')->with('success', $msg);
    }

    /**
     * Unified list: history (me) or all (admin)
     * Route examples:
     *   - /admin/ifter-subscription/history         -> browse('me')
     *   - /admin/ifter-subscription/all-ifter-list  -> browse('all')
     */
    public function index(string $scope = 'me')
    {
        $uid = (int)(session('user_id') ?? 0);

        // Last 1 year window based on created_at (Asia/Dhaka)
        $tz         = new \DateTimeZone('Asia/Dhaka');
        $oneYearAgo = (new \DateTime('now', $tz))->modify('-1 year')->format('Y-m-d H:i:s');

        $builder = $this->subs
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
            ->where('meal_subscriptions.meal_type_id', self::MEAL_TYPE_ID)
            ->where('meal_subscriptions.created_at >=', $oneYearAgo);

        // Scope filter
        if ($scope === 'me') {
            $builder->where('meal_subscriptions.user_id', $uid);
        }

        $rows = $builder
            ->orderBy('meal_subscriptions.created_at', 'DESC')
            ->orderBy('meal_subscriptions.id', 'DESC')
            ->findAll();

        // Choose view by scope
        $view = ($scope === 'all')
            ? 'admin/ifter_subscription/all_list'
            : 'admin/ifter_subscription/history';

        return view($view, ['subs' => $rows]);
    }


    # -----------------------------------------------------------
    # POST: Unsubscribe multiple (ids[]) + single remark for all
    # -----------------------------------------------------------
    public function unsubscribe_bulk()
    {
        $ids    = array_map('intval', (array)$this->request->getPost('ids'));
        $remark = trim((string)$this->request->getPost('remark'));

        if (empty($ids)) {
            return redirect()->back()->with('error', 'No subscriptions selected.');
        }

        $now = date('Y-m-d H:i:s');

        $this->db->transStart();

        $this->subs->whereIn('id', $ids)
            ->set('status', 'CANCELLED')
            ->set('unsubs_by', (int)session('user_id'))
            ->set('updated_at', $now)
            ->update();

        if ($remark !== '') {
            $rb = $this->db->table('remarks');
            $rows = [];
            foreach ($ids as $sid) {
                $rows[] = [
                    'subs_id'         => $sid,
                    'remark'          => $remark,
                    'approver_remark' => null,
                    'created_at'      => $now,
                ];
            }
            if ($rows) $rb->insertBatch($rows);
        }

        $this->db->transComplete();

        return redirect()->back()->with('success', 'Selected subscriptions cancelled.');
    }

    # -----------------------------------------------------------
    # GET: Unsubscribe one
    # -----------------------------------------------------------
    public function unsubscribeSingle(int $id)
    {
        $this->subs->update($id, [
            'status'     => 'CANCELLED',
            'unsubs_by'  => (int)session('user_id'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('success', 'Unsubscribed.');
    }

    # -----------------------------------------------------------
    # Helpers
    # -----------------------------------------------------------
    private function isDateAllowed(
        string $d,
        string $ramadanStart,
        string $ramadanEnd,
        string $today,
        string $windowEnd,
        int $leadDays,
        string $cutOffTime,
        array $holidays
    ): bool {
        // Ramadan window
        if ($d < $ramadanStart || $d > $ramadanEnd) return false;

        // Horizon window
        if ($d < $today || $d > $windowEnd) return false;

        // Holidays
        if (in_array($d, $holidays, true)) return false;

        // Friday=5, Saturday=6 (PHP: Sunday=0)
        $w = (int) (new \DateTime($d))->format('w');
        if (in_array($w, [5, 6], true)) return false;

        // Lead days + cutoff time: allow only if now <= (D - leadDays @ cutOffTime)
        $tz   = new \DateTimeZone('Asia/Dhaka');
        $gate = (new \DateTime($d . ' ' . $cutOffTime, $tz))->modify("-{$leadDays} days");
        $now  = new \DateTime('now', $tz);
        return $now <= $gate;
    }

    /**
     * Resolve user pay (user_tk) with cafeteria/NULL and empType/ALL(0) fallback.
     * Returns float|null.
     */
    private function resolveUserTk(int $mealTypeId, int $empTypeId, int $cafeteriaId): ?float
    {
        $b = $this->db->table('meal_contributions')
            ->select('user_tk')
            ->where('meal_type_id', $mealTypeId)
            ->where('is_active', 1);

        // Prefer exact cafeteria, else NULL
        $b->groupStart()
            ->where('cafeteria_id', $cafeteriaId)
            ->orWhere('cafeteria_id', null)
        ->groupEnd();

        // Prefer exact empType, else ALL(0)
        $b->groupStart()
            ->where('emp_type_id', $empTypeId)
            ->orWhere('emp_type_id', 0)
        ->groupEnd();

        // Ordering: exact cafeteria first, exact empType first, newest effective_date first
        $b->orderBy("cafeteria_id IS NULL", 'ASC', false)
          ->orderBy("emp_type_id = {$empTypeId}", 'DESC', false)
          ->orderBy('effective_date', 'DESC');

        $row = $b->get(1)->getFirstRow('array');
        return $row ? (float)$row['user_tk'] : null;
    }

    /**
     * Return [approverRole, approverUserId] for the step.
     */
    private function resolveApprover(array $step, int $userId): array
    {
        $type = strtoupper((string)($step['approver_type'] ?? 'ROLE'));

        if ($type === 'ROLE' && !empty($step['approver_role'])) {
            return [(int)$step['approver_role'], null];
        }

        if ($type === 'USER' && !empty($step['approver_user_id'])) {
            return [null, (int)$step['approver_user_id']];
        }

        if ($type === 'LINE_MANAGER') {
            $lm = $this->users->select('line_manager_id')->where('id', $userId)->get(1)->getFirstRow('array');
            if (!empty($lm['line_manager_id'])) {
                return [null, (int)$lm['line_manager_id']];
            }
            if (!empty($step['fallback_role'])) {
                return [(int)$step['fallback_role'], null];
            }
        }

        // Fallback to role if nothing is set
        if (!empty($step['fallback_role'])) {
            return [(int)$step['fallback_role'], null];
        }

        return [null, null];
    }
}
