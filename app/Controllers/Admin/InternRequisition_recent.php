<?php namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Intern\CsvSubscriptionImporter;
use App\Models\MealSubscriptionModel;
use App\Models\CutoffTimeModel;
use App\Models\PublicHolidayModel;
use App\Models\MealTypeModel;
use App\Models\CafeteriaModel;
use App\Models\ApprovalFlowModel;
use App\Models\ApprovalStepModel;
use App\Models\UserModel;

class InternRequisition extends BaseController
{
    // Adjust if your ids differ
    private const MEAL_TYPE_ID = 1;                 // Lunch
    private const EMP_TYPE_IDS = [2, 3, 4, 5, 6, 7]; // Allowed intern emp_type_id values

    protected \CodeIgniter\Database\BaseConnection $db;

    protected MealSubscriptionModel $subs;
    protected CutoffTimeModel       $cutoffs;
    protected PublicHolidayModel    $holidays;
    protected MealTypeModel         $mealTypes;
    protected CafeteriaModel        $cafeterias;
    protected ApprovalFlowModel     $flows;
    protected ApprovalStepModel     $steps;
    protected UserModel             $users;
    protected CsvSubscriptionImporter    $importer;
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
        $this->importer   = new CsvSubscriptionImporter();
        $this->db         = db_connect();
    }

    # ------------------------------------------------------------------
    # GET: New form
    # ------------------------------------------------------------------
    public function new()
    {
        $tz    = new \DateTimeZone('Asia/Dhaka');
        $today = (new \DateTime('now', $tz))->format('Y-m-d');

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

        $windowStart = $today;
        $windowEnd   = (new \DateTime($today, $tz))->modify("+{$maxHorizon} days")->format('Y-m-d');

        // Holidays inside window
        $publicHolidays = $this->holidays->select('holiday_date')
            ->where('is_active', 1)
            ->where('holiday_date >=', $windowStart)
            ->where('holiday_date <=', $windowEnd)
            ->findColumn('holiday_date') ?? [];

        // Already registered by current user (requester)
        $registeredDates = $this->subs->select('subs_date')
            ->where('user_id', (int) session('user_id'))
            ->where('meal_type_id', self::MEAL_TYPE_ID)
            ->whereIn('status', ['ACTIVE', 'PENDING', 'REDEEMED'])
            ->where('subs_date >=', $windowStart)
            ->where('subs_date <=', $windowEnd)
            ->orderBy('subs_date', 'ASC')
            ->findColumn('subs_date') ?? [];

        return view('admin/intern_subscription/new', [
            'reg_start_date'  => $windowStart,
            'reg_end_date'    => $windowEnd,
            'cut_off_time'    => $cutTime,
            'lead_days'       => $leadDays,
            'max_horizon'     => $maxHorizon,
            'publicHolidays'  => $publicHolidays,
            'registeredDates' => $registeredDates,
            'mealTypes'       => $this->mealTypes->where('id', self::MEAL_TYPE_ID)->findAll(),
            'cafeterias'      => $this->cafeterias->where('is_active', 1)->orderBy('name')->findAll(),
            'empTypeIds'      => self::EMP_TYPE_IDS, // for a dropdown
            'validation'      => \Config\Services::validation(),
        ]);
    }

    # ------------------------------------------------------------------
    # POST: Create intern requisitions (CSV dates)
    # ------------------------------------------------------------------

    public function index()
    {
        $uid = (int) (session('user_id') ?? 0);
        $builder = $this->subs
            ->select("meal_subscriptions.*,
                      cafeterias.name AS cafeteria_name,
                      meal_types.name AS meal_type_name,
                      users.name      AS requester_name,
                      users.employee_id,
                      ct.cut_off_time AS cutoff_time,
                      ct.lead_days    AS lead_days,
                      mr.ref_name         AS intern_name,
                      mr.ref_phone        AS intern_phone", false)
            ->join('cafeterias', 'cafeterias.id = meal_subscriptions.cafeteria_id', 'left')
            ->join('meal_types', 'meal_types.id = meal_subscriptions.meal_type_id', 'left')
            ->join('users',      'users.id = meal_subscriptions.user_id', 'left')
            ->join('cutoff_times ct', 'ct.meal_type_id = meal_subscriptions.meal_type_id AND ct.is_active = 1', 'left')
            ->join('meal_reference mr', "mr.subs_id = meal_subscriptions.id", 'left')
            ->where('meal_subscriptions.meal_type_id', self::MEAL_TYPE_ID)
            ->whereIn('meal_subscriptions.emp_type_id', self::EMP_TYPE_IDS);

        $rows = $builder
            ->orderBy('meal_subscriptions.subs_date', 'DESC')
            ->orderBy('meal_subscriptions.id', 'DESC')
            ->findAll();

        return view('admin/intern_subscription/index', ['subs' => $rows]);
    }


    # ------------------------------------------------------------------
    # POST: Unsubscribe multiple + add new remark rows (no updates)
    # ------------------------------------------------------------------
    public function unsubscribe_bulk()
    {
        $ids    = array_map('intval', (array) $this->request->getPost('subscription_ids'));
        $remark = trim((string) $this->request->getPost('remark'));

        if (empty($ids)) {
            return redirect()->back()->with('error', 'No subscriptions selected.');
        }

        $now = date('Y-m-d H:i:s');

        $this->db->transStart();

        $this->subs->whereIn('id', $ids)
            ->set('status', 'CANCELLED')
            ->set('unsubs_by', (int) session('user_id'))
            ->set('updated_at', $now)
            ->update();

        if ($remark !== '') {
            $rows = [];
            foreach ($ids as $sid) {
                $rows[] = [
                    'subs_id'         => $sid,
                    'remark'          => $remark,
                    'approver_remark' => null,
                    'created_at'      => $now,
                ];
            }
            if ($rows) {
                $this->db->table('remarks')->insertBatch($rows);
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->back()->with('error', 'Unsubscribe failed.');
        }

        return redirect()->back()->with('success', 'Selected requisitions cancelled.');
    }

    # ------------------------------------------------------------------
    # GET: Cancel one
    # ------------------------------------------------------------------
    public function unsubscribeSingle(int $id)
    {
        $this->subs->update($id, [
            'status'     => 'CANCELLED',
            'unsubs_by'  => (int) session('user_id'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('success', 'Requisition cancelled.');
    }

    public function processUpload()
    {
        if (! $this->validateUpload()) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $mealTypeId = (int) $this->request->getPost('meal_type_id');
        $dates = $this->parseMealDates();
        $filePath = $this->handleUploadedFile();
        $rows = $this->importer->parseExcel($filePath);
        $this->deleteFileIfExists($filePath);
        $result = $this->insertSubscriptions($mealTypeId, $dates, $rows);
        if ($result[0] !== true) {
            return redirect()
                ->to('admin/intern-requisitions')
                ->with('success', $result[1]);
        }

        return redirect()
            ->to('admin/intern-requisitions')
            ->with('success', 'Excel processed and subscriptions created.');
    }

    protected function validateUpload(): bool
    {
        $rules = [
            'meal_type_id' => 'required|integer',
            'meal_dates'   => 'required|string',
            'xlsx_file'    => [
                'rules'  => 'uploaded[xlsx_file]|ext_in[xlsx_file,xlsx,xls]|max_size[xlsx_file,2048]',
                'errors' => [
                    'uploaded' => 'Please choose an Excel file to upload.',
                    'ext_in'   => 'The file must have a .xlsx or .xls extension.',
                    'max_size' => 'The file cannot exceed 2MB.',
                ],
            ],
        ];
        return $this->validate($rules);
    }

    protected function parseMealDates(): array
    {
        $tz    = new \DateTimeZone('Asia/Dhaka');
        $raw   = explode(',', $this->request->getPost('meal_dates'));
        $dates = [];

        foreach ($raw as $r) {
            $dt = \DateTime::createFromFormat('d/m/Y', trim($r), $tz)
                ?: redirect()->back()->withInput()->with('error', "Invalid date: {$r}");
            $dates[] = $dt->format('Y-m-d');
        }

        sort($dates);
        return $dates;
    }

    protected function handleUploadedFile(): string
    {
        $file = $this->request->getFile('xlsx_file');
        if (! $file->isValid()) {
            return redirect()->back()->withInput()->with('error', "Not a valid file");
        }

        $path = WRITEPATH . 'uploads/' . $file->getRandomName();
        $file->move(WRITEPATH . 'uploads', basename($path));
        return $path;
    }

    protected function deleteFileIfExists(string $filePath): void
    {
        if (is_file($filePath) && file_exists($filePath)) {
            try {
                unlink($filePath);
            } catch (\Throwable $e) {
                log_message('error', "Failed to delete file {$filePath}: " . $e->getMessage());
            }
        }
    }

    protected function getApprovalFlow(int $mealTypeId, string $subscription_type)
    {
        return $this->flowModel
            ->where('meal_type_id', $mealTypeId)
            ->where('user_type', $subscription_type)
            ->where('is_active', 1)
            ->where('effective_date <=', date('Y-m-d'))
            ->orderBy('effective_date', 'DESC')
            ->first();
    }

    protected function insertSubscriptions(int $mealTypeId, array $dates, array $rows)
    {
        $db = $this->db;
        if (empty($dates) || empty($rows)) {
            return [true];
        }

        // Take the first date exactly as selected (array order)
        $firstSelectedDate = reset($dates);

        // Track NEW JOINERs already inserted in this request (by userRefId)
        $newJoinerInserted = [];
        $result = [true];

        $remark = $this->request->getPost('remark');

        foreach ($rows as [$userRefId, $userType, $name, $phone, $cafeteriaName]) {
            $userRefId   = trim((string) $userRefId);
            $userTypeRaw = trim((string) $userType);
            $userType    = strtoupper($userTypeRaw); // INTERN/FTC/OS/NEW JOINER (used by approval flow)
            $name        = trim((string) $name);
            $phone       = trim((string) $phone);
            $cafeteriaId = $this->resolveCafeteriaId($cafeteriaName);

            if (!preg_match('/^\d{11}$/', $phone)) {
                // skip or collect for reporting
                $result = [false, "Invalid phone for {$name}: {$phone}. Must be exactly 11 digits."];
                continue;
            }

            // employment_type_id from master (by name = original, not uppercased)
            $employmentTypeId = $this->resolveEmploymentTypeIdByName($userTypeRaw);
            if ($employmentTypeId === null) {
                return redirect()->back()->with(
                    'error',
                    "Employment Type '{$userTypeRaw}' not found or inactive. Please create/activate it first."
                );
            }

            $isNewJoiner = ($userType === 'NEW JOINER');

            // If NEW JOINER for this user was already inserted once, skip all further dates
            if ($isNewJoiner && isset($newJoinerInserted[$userRefId])) {
                continue;
            }

            // Decide which dates to insert
            $datesToInsert = $isNewJoiner ? [$firstSelectedDate] : $dates;

            // Approval flow & status (same for all dates of this row)
            $flow   = $this->getApprovalFlow($mealTypeId, $userType); // unchanged
            $status = ($flow && $flow['type'] === 'MANUAL') ? 'PENDING' : 'ACTIVE';

            // If pending, make sure steps exist
            if ($status === 'PENDING') {
                $steps = $this->getApprovalSteps($flow['id']);
                if (empty($steps)) {
                    return redirect()->back()->with('error', 'Approval flow defined, but no steps configured. Contact admin.');
                }
            }

            foreach ($datesToInsert as $date) {
                // Usual duplicate check (same userRefId + mealType + date)
                if ($this->subscriptionExists($userRefId, $mealTypeId, $date)) {
                    return redirect()->back()->with('error', "Duplicate for {$userRefId} on {$date}");
                }

                // Insert approval steps per subscription when pending
                if ($status === 'PENDING') {
                    $step = $db->table('approval_steps')
                        ->where('flow_id', (int)$flow['id'])
                        ->orderBy('step_order','ASC')
                        ->get(1)->getFirstRow('array');

                    $approverRole = null;
                    $approverUser = null;

                    if ($step) {
                        $type = strtoupper((string)$step['approver_type']);
                        if ($type === 'ROLE' && !empty($step['approver_role'])) {
                            $approverRole = (int)$step['approver_role'];
                        } elseif ($type === 'USER' && !empty($step['approver_user_id'])) {
                            $approverUser = (int)$step['approver_user_id'];
                        } elseif ($type === 'LINE_MANAGER') {
                            $lm = $db->table('users')->select('line_manager_id')->where('id',$employeeUserId)->get()->getFirstRow('array');
                            if (!empty($lm['line_manager_id'])) {
                                $approverUser = (int)$lm['line_manager_id'];
                            } elseif (!empty($step['fallback_role'])) {
                                $approverRole = (int)$step['fallback_role'];
                            }
                        }
                    }

                    $subsIds = array_column(
                        $db->table('meal_subscriptions')
                            ->select('id')
                            ->where('user_id', $employeeUserId)
                            ->where('meal_type_id', $mealTypeId)
                            ->where('emp_type_id', $empTypeId)
                            ->where('cafeteria_id', $cafeteriaId)
                            ->whereIn('subs_date', $dates)
                            ->where('status', 'PENDING')
                            ->get()->getResultArray(),
                        'id'
                    );

                    
                    $this->insertApprovalSteps($batchId, $steps);
                }

                $otp = ($status === 'ACTIVE') ? $this->getOtp() : null;

                // Insert the subscription (no subscription_type anymore)
                $this->internSubscriptionModel->insert([
                    'batch_id'            => $batchId,
                    'meal_type_id'        => $mealTypeId,
                    'user_reference_id'   => $userRefId,
                    'intern_name'         => $name,
                    'phone'               => $phone,
                    'subscription_date'   => $date,
                    'employment_type_id'  => $employmentTypeId, // <-- new
                    'cafeteria_id'        => $cafeteriaId,
                    'status'              => $status,
                    'remark'              => $remark,
                    'otp'                 => $otp,
                ]);


                // Mark NEW JOINER as done after the first insert
                if ($isNewJoiner) {
                    $newJoinerInserted[$userRefId] = true;
                    break; // stop looping further dates for this NEW JOINER
                }
            }

            // Send SMS only when ACTIVE (so OTP exists)
            if(isset($otp) && !empty($otp)){
                // Build the compact date message from the whole $dates array
                $niceDates = $this->formatDatesCompact($datesToInsert); // e.g. "Aug 31, Sep 1-3, 2025"
                // Now compose and send the SMS
                $message = "bKash Lunch OTP: {$otp}. Valid once per day on {$niceDates}, at Cafeteria {$cafeteriaName} only. Thank you";
                $this->send_sms($phone, $message);
            }
        }

        return $result;
    }

    private function resolveEmploymentTypeIdByName(string $typeName): ?int
    {
        $key = strtoupper(trim($typeName));
        if (isset($this->employmentTypeCache[$key])) {
            return $this->employmentTypeCache[$key];
        }

        $db   = db_connect();
        $row  = $db->table('employment_types')
                ->select('id')
                ->where('is_active', 1)
                ->where('name', $typeName) // MySQL default collations are case-insensitive
                ->get()
                ->getRowArray();

        $id = $row['id'] ?? null;
        $this->employmentTypeCache[$key] = $id;

        return $id;
    }

    protected function getApprovalSteps(int $flowId): array
    {
        return $this->stepModel
            ->where('flow_id', $flowId)
            ->orderBy('step_order', 'ASC')
            ->findAll();
    }

    protected function insertApprovalSteps($subsId, $approverRole, $approverUser)
    {
        $apRows = [];
        foreach ($subsId as $sid) {
            $apRows[] = [
                'subs_id'          => (int)$sid,
                'approver_role'    => $approverRole,
                'approver_user_id' => $approverUser,
                'approved_by'      => 0,
                'approval_status'  => 'PENDING',
                'approved_at'      => null,
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s'),
            ];
        }
        $this->db->table('meal_approvals')->insertBatch($apRows);

    }

    protected function resolveCafeteriaId(?string $name): ?int
    {
        if (empty($name)) {
            return null;
        }

        $cafeteria = $this->cafModel
            ->select('id')
            ->where('name', $name)
            ->where('is_active', 1)
            ->first();

        return $cafeteria['id'] ?? null;
    }

    protected function subscriptionExists(string $userRefId, int $mealTypeId, string $date): bool
    {
        return $this->internSubscriptionModel
            ->where('user_reference_id', $userRefId)
            ->where('meal_type_id', $mealTypeId)
            ->where('subscription_date', $date)
            ->whereIn('status', ['ACTIVE', 'PENDING'])
            ->countAllResults() > 0;
    }

    /**
     * Resolve user_tk with cafeteria/NULL and empType/ALL(0) fallback.
     */
    private function resolveUserTk(int $mealTypeId, int $empTypeId, int $cafeteriaId): ?float
    {
        $b = $this->db->table('meal_contributions')
            ->select('user_tk')
            ->where('meal_type_id', $mealTypeId)
            ->where('is_active', 1);

        // Cafeteria exact then NULL
        $b->groupStart()
            ->where('cafeteria_id', $cafeteriaId)
            ->orWhere('cafeteria_id', null)
        ->groupEnd();

        // Emp type exact then ALL(0)
        $b->groupStart()
            ->where('emp_type_id', $empTypeId)
            ->orWhere('emp_type_id', 0)
        ->groupEnd();

        $b->orderBy("cafeteria_id IS NULL", 'ASC', false)
          ->orderBy("emp_type_id = {$empTypeId}", 'DESC', false)
          ->orderBy('effective_date', 'DESC');

        $row = $b->get(1)->getFirstRow('array');
        return $row ? (float) $row['user_tk'] : null;
    }

    /**
     * Return [approverRole, approverUserId] for the step.
     */
    private function resolveApprover(array $step, int $userId): array
    {
        $type = strtoupper((string)($step['approver_type'] ?? 'ROLE'));

        if ($type === 'ROLE' && !empty($step['approver_role'])) {
            return [(int) $step['approver_role'], null];
        }

        if ($type === 'USER' && !empty($step['approver_user_id'])) {
            return [null, (int) $step['approver_user_id']];
        }

        if ($type === 'LINE_MANAGER') {
            $lm = $this->users->select('line_manager_id')->where('id', $userId)->get(1)->getFirstRow('array');
            if (!empty($lm['line_manager_id'])) {
                return [null, (int) $lm['line_manager_id']];
            }
            if (!empty($step['fallback_role'])) {
                return [(int) $step['fallback_role'], null];
            }
        }

        if (!empty($step['fallback_role'])) {
            return [(int) $step['fallback_role'], null];
        }

        return [null, null];
    }
}
