<?php namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MealSubscriptionModel;
use App\Models\CutoffTimeModel;
use App\Models\PublicHolidayModel;
use App\Models\MealTypeModel;
use App\Models\CafeteriaModel;
use App\Models\EmploymentTypeModel;
use App\Libraries\CutoffResolver;
use App\Services\Intern\CsvSubscriptionImporter;

class InternSubscription extends BaseController
{
    private const MEAL_TYPE_ID = 1;                 // Lunch
    private const EMP_TYPE_IDS = [2, 3, 4, 5, 6, 7];

    protected $subs;
    protected $cutoffModel;
    protected $holidayModel;
    protected $cafeteriaModel;
    protected $mealTypeModel;
    protected $employmentTypeModel;

    protected $csvSubscriptionImporter;
    protected $cutoffResolver;
    protected $db;

    public function __construct()
    {
        helper(['form','url']);

        $this->subs            = new MealSubscriptionModel();
        $this->cutoffModel     = new CutoffTimeModel();
        $this->holidayModel    = new PublicHolidayModel();
        $this->cafeteriaModel  = new CafeteriaModel();
        $this->mealTypeModel   = new MealTypeModel();
        $this->employmentTypeModel = new EmploymentTypeModel();

        $this->csvSubscriptionImporter = new CsvSubscriptionImporter();
        $this->cutoffResolver          = new CutoffResolver(
            new CutoffTimeModel(),
            new PublicHolidayModel()
        );

        $this->db = db_connect();
    }

    /** GET /intern-subscriptions/new */
    public function new()
    {
        // default Lunch (1) like your view
        $mealTypeId = self::MEAL_TYPE_ID;

        $cut = $this->cutoffModel
            ->select('max_horizon_days, cut_off_time, lead_days')
            ->where('is_active', 1)
            ->where('meal_type_id', $mealTypeId)
            ->where('cutoff_date', null)
            ->orderBy('updated_at','DESC')->orderBy('id','DESC')
            ->first();

        $cutoffDays   = (int)($cut['max_horizon_days'] ?? 30);
        $cut_off_time = (string)($cut['cut_off_time'] ?? '22:00:00');
        $lead_days    = (int)($cut['lead_days'] ?? 1);

        $today      = date('Y-m-d');
        $cutoffDate = date('Y-m-d', strtotime("+{$cutoffDays} days"));

        $publicHolidays = $this->holidayModel
            ->select('holiday_date')
            ->where('is_active', 1)
            ->where('holiday_date >=', $today)
            ->where('holiday_date <=', $cutoffDate)
            ->orderBy('holiday_date', 'ASC')
            ->findColumn('holiday_date');

        return view('admin/intern_subscription/new', [
            'cutoffDays'      => $cutoffDays,
            'cut_off_time'    => $cut_off_time,
            'lead_days'       => $lead_days,
            'publicHolidays'  => $publicHolidays,
            'mealTypes'       => $this->mealTypeModel->findAll(),
            'cafeterias'      => $this->cafeteriaModel->where('is_active',1)->findAll(),
            'validation'      => \Config\Services::validation(),
        ]);
    }


    /** GET /admin/intern_subscription/index */
    public function index()
    {
        $empTypeId = self::EMP_TYPE_IDS; // INTERN,FTC [2, 3, 4, 5, 6, 7]

        $this->subs
            ->select("meal_subscriptions.*,
                      meal_types.name  AS meal_type_name,
                      cafeterias.name  AS caffname,
                      et.name               AS emp_type_name,
                      mr.ref_id,
                      mr.ref_name,
                      mr.ref_phone,
                      mr.otp                AS otp,
                      users.employee_id,
                      users.name,
                      ct.cut_off_time  AS cutoff_time,
                      ct.lead_days     AS lead_days", false)
            ->join('meal_types', 'meal_types.id = meal_subscriptions.meal_type_id', 'left')
            ->join('cafeterias', 'cafeterias.id = meal_subscriptions.cafeteria_id', 'left')
            ->join('employment_types et', 'et.id = meal_subscriptions.emp_type_id', 'left')
            ->join('meal_reference mr', 'mr.subs_id = meal_subscriptions.id', 'left')
            ->join('users', 'users.id = meal_subscriptions.user_id', 'left')
            ->join('cutoff_times ct', 'ct.meal_type_id = meal_subscriptions.meal_type_id AND ct.is_active = 1', 'left');

          

            if (is_array($empTypeId)) {
                $this->subs->whereIn('meal_subscriptions.emp_type_id', $empTypeId);
            } else {
                $this->subs->where('meal_subscriptions.emp_type_id', $empTypeId);
            }

            $this->subs->orderBy('meal_subscriptions.id', 'DESC');

            $rows = $this->subs->findAll();

        return view('admin/intern_subscription/index', ['subs' => $rows]);
    }

    /** POST /guest-subscriptions/process-upload */
    public function processUpload()
    {
        if (! $this->validateUpload()) {               // <- call the helper (don’t pass its return into validate())
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }
        //$this->dd('dsf');

        $mealTypeId = (int)$this->request->getPost('meal_type_id');
        $dates = $this->parseMealDates();

        // Resolve flow & status
        

        // Parse rows from uploaded Excel
        $filePath = $this->handleUploadedFile();
        $rows = $this->csvSubscriptionImporter->parseExcel($filePath); // each row: [name, phone, cafeteriaName]
        $this->deleteFileIfExists($filePath);

        // Insert everything
        $result = $this->insertSubscriptionsBulk($mealTypeId, $dates, $rows);
        if ($result[0] !== true) {
            return redirect()->to('intern-subscriptions')->with('success', $result[1]);
        }

        return redirect()->to('intern-subscriptions')->with('success', 'Excel processed and subscriptions created.');
    }

    /** POST /intern-subscriptions/unsubscribe/(:num) */
    public function unsubscribe($id)
    {
        $ok = $this->subs->update((int)$id, [
            'status'     => 'CANCELLED',
            'unsubs_by'  => (int)(session('user_id') ?? 0),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with($ok ? 'success' : 'error', $ok ? 'Unsubscribed successfully.' : 'Unable to unsubscribe.');
    }

    /** POST /intern-subscriptions/unsubscribe-bulk */
    public function unsubscribe_bulk()
    {
        $ids    = array_values(array_filter((array)$this->request->getPost('subscription_ids'), 'is_numeric'));
        $remark = trim((string)$this->request->getPost('remark'));

        if (empty($ids)) {
            return redirect()->back()->with('error', 'No subscriptions selected.');
        }

        $now = date('Y-m-d H:i:s');

        $this->subs
            ->whereIn('id', $ids)
            ->set('status', 'CANCELLED')
            ->set('updated_at', $now)
            ->set('unsubs_by', session('user_id') ?? 0)
            ->update();

        // Log remarks per subs_id
        $builder = $this->db->table('remarks');
        $rows = [];
        foreach ($ids as $sid) {
            $rows[] = [
                'subs_id'         => (int)$sid,
                'remark'          => $remark,
                'approver_remark' => '',
                'created_at'      => $now,
            ];
        }
        if ($rows) $builder->insertBatch($rows);

        return redirect()->back()->with('success', 'Selected subscriptions unsubscribed.');
    }

    // --------------------------- helpers ---------------------------
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
        return $this->validate($rules); // <- returns bool
    }

    protected function parseMealDates(): array
    {
        $tz    = new \DateTimeZone('Asia/Dhaka');
        $raw   = explode(',', (string)$this->request->getPost('meal_dates'));
        $dates = [];
        foreach ($raw as $r) {
            $r = trim($r);
            if ($r === '') continue;
            $dt = \DateTime::createFromFormat('d/m/Y', $r, $tz);
            if (! $dt) {
                throw new \RuntimeException("Invalid date: {$r}");
            }
            $dates[] = $dt->format('Y-m-d');
        }
        sort($dates);
        return $dates;
    }

    protected function handleUploadedFile(): string
    {
        $file = $this->request->getFile('xlsx_file');
        if (! $file || ! $file->isValid()) {
            throw new \RuntimeException('Invalid XLSX upload.');
        }
        $path = WRITEPATH . 'uploads/' . $file->getRandomName();
        $file->move(WRITEPATH . 'uploads', basename($path));
        return $path;
    }

    protected function deleteFileIfExists(string $filePath): void
    {
        if (is_file($filePath) && file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    protected function getApprovalFlow(int $mealTypeId, int $empTypeId)
    {
        return $this->db->table('approval_flows')
            ->where('meal_type_id', $mealTypeId)
            ->whereIn('emp_type_id', [$empTypeId, 0])
            ->where('is_active', 1)
            ->orderBy("(emp_type_id = {$empTypeId})", 'DESC', false)
            ->orderBy('effective_date', 'DESC')
            ->get(1)->getFirstRow('array');
    }

    protected function insertSubscriptionsBulk(
        int $mealTypeId,
        array $dates,
        array $rows,         // parsed from excel: [ [name, phone, cafeteriaName], ... ]
    ) {
        if (empty($dates) || empty($rows)) {
            return [true];
        }

        $firstSelectedDate = reset($dates);

        // Track NEW JOINERs already inserted in this request (by userRefId)
        $newJoinerInserted = [];
        $result = [true];

        $db = $this->db;
        $db->transStart();

        $nowStr  = date('Y-m-d H:i:s');
        $userId  = (int)(session('user_id') ?? 0);
        $namesToSms = []; // phone => [otp, cafeteriaName, dates[]]

        //foreach ($rows as [$name, $phone, $cafeteriaName]) {
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
            $empTypeId = $this->resolveEmploymentTypeIdByName($userTypeRaw);
            if ($empTypeId === null) {
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


            $flow = $this->getApprovalFlow($mealTypeId, $empTypeId);
            $status = ($flow && strtoupper($flow['type']) === 'MANUAL') ? 'PENDING' : 'ACTIVE';

            // Resolve user share for the intern type
            $userTk = $this->resolveUserTk($mealTypeId, $empTypeId, $cafeteriaId);
            if ($userTk === null) {
                $db->transComplete();
                return [false, "Missing Contributions for {$name} ({$phone})"];
            }

            $otp = ($status === 'ACTIVE') ? $this->getOtp() : null;

            foreach ($datesToInsert as $dstr) {
                $this->subs->insert([
                    'user_id'      => $userId,
                    'meal_type_id' => $mealTypeId,
                    'emp_type_id'  => $empTypeId,
                    'cafeteria_id' => $cafeteriaId,
                    'subs_date'    => $dstr,
                    'status'       => $status,
                    'price'        => number_format((float)$userTk, 2, '.', ''),
                    'created_by'   => $userId,
                    'unsubs_by'    => 0,
                    'created_at'   => $nowStr,
                    'updated_at'   => $nowStr,
                ]);

                $sid = (int)$this->subs->getInsertID();

                // store brief remark with intern identity
                $remark = trim((string) $this->request->getPost('remark'));
                $db->table('remarks')->insert([
                    'subs_id'         => $sid,
                    'remark'          => $remark,
                    'created_at'      => $nowStr,
                ]);

                // Add meal reference table intern info
                $db->table('meal_reference')->insert([
                    'subs_id'      => $sid,
                    'ref_id'       => $userRefId,
                    'ref_name'     => $name,
                    'ref_phone'    => $phone,
                    'otp'          => $otp,
                ]);

                // Mark NEW JOINER as done after the first insert
                if ($isNewJoiner) {
                    $newJoinerInserted[$userRefId] = true;
                    break; // stop looping further dates for this NEW JOINER
                }
            }

            if ($status === 'ACTIVE' && $otp) {
                $namesToSms[$phone] = [$otp, $cafeteriaName, $dates];
            }
        }

        // Create approvals for PENDING status using first step
        if ($status === 'PENDING' && $flow) {
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
                    $lm = $db->table('users')->select('line_manager_id')->where('id', (int)(session('user_id') ?? 0))->get()->getFirstRow('array');
                    if (!empty($lm['line_manager_id'])) {
                        $approverUser = (int)$lm['line_manager_id'];
                    } elseif (!empty($step['fallback_role'])) {
                        $approverRole = (int)$step['fallback_role'];
                    }
                }
            }

            // All just-created PENDING rows for this uploader and intern type
            $subsIds = array_column(
                $db->table('meal_subscriptions')
                    ->select('id')
                    ->where('user_id', (int)(session('user_id') ?? 0))
                    ->where('meal_type_id', $mealTypeId)
                    ->where('emp_type_id', $empTypeId)
                    ->where('status', 'PENDING')
                    ->whereIn('subs_date', $dates)
                    ->get()->getResultArray(),
                'id'
            );

            if ($subsIds) {
                $existing = array_column(
                    $db->table('meal_approvals')->select('subs_id')->whereIn('subs_id',$subsIds)->get()->getResultArray(),
                    'subs_id'
                );
                $toCreate = array_values(array_diff($subsIds, $existing));

                if ($toCreate) {
                    $apRows = [];
                    foreach ($toCreate as $sid) {
                        $apRows[] = [
                            'subs_id'          => (int)$sid,
                            'approver_role'    => $approverRole,
                            'approver_user_id' => $approverUser,
                            'approved_by'      => 0,
                            'approval_status'  => 'PENDING',
                            'approved_at'      => null,
                            'created_at'       => $nowStr,
                            'updated_at'       => $nowStr,
                        ];
                    }
                    $db->table('meal_approvals')->insertBatch($apRows);
                }
            }
        }

        // Queue SMS per intern (ACTIVE)
        if (!empty($namesToSms)) {
            foreach ($namesToSms as $msisdn => [$otp, $cafeteriaName, $datesArr]) {
                $niceDates = $this->formatDatesCompact($datesArr);
                $message   = "bKash Lunch OTP: {$otp}. Valid once per day on {$niceDates}, at Cafeteria {$cafeteriaName} only. Thank you";
                $this->send_sms($msisdn, $message);
            }
        }

        $db->transComplete();
        if (! $db->transStatus()) {
            return [false, 'Failed to create subscriptions.'];
        }

        return $result;
    }

    private function resolveEmploymentTypeIdByName(string $typeName): ?int
    {
        $db   = db_connect();
        $row  = $db->table('employment_types')
                ->select('id')
                ->where('is_active', 1)
                ->where('name', $typeName) // MySQL default collations are case-insensitive
                ->get()
                ->getRowArray();

        $id = $row['id'] ?? null;

        return $id;
    }

    protected function resolveCafeteriaId(?string $name): ?int
    {
        if (!$name) return null;
        $row = $this->cafeteriaModel->select('id')->where('name', $name)->where('is_active',1)->first();
        return $row['id'] ?? null;
    }

    /** Contribution resolver for user_tk */
    private function resolveUserTk(int $mealTypeId, int $empTypeId, ?int $cafeteriaId): ?float
    {
        $b = $this->db->table('meal_contributions')
            ->select('user_tk')
            ->where('is_active', 1)
            ->where('meal_type_id', $mealTypeId);

        // prioritize exact emp_type then ALL(0)
        $b->orderBy("(emp_type_id = {$empTypeId})", 'DESC', false);

        // prioritize exact cafeteria then NULL
        if ($cafeteriaId === null) {
            $b->orderBy('(cafeteria_id IS NULL)', 'DESC', false);
        } else {
            $cafeteriaId = (int)$cafeteriaId;
            $b->orderBy("(cafeteria_id = {$cafeteriaId})", 'DESC', false);
        }

        $b->orderBy('id','DESC');

        // cafeteria filter
        if ($cafeteriaId === null) {
            $b->groupStart()->where('cafeteria_id', null)->groupEnd();
        } else {
            $b->groupStart()->where('cafeteria_id', $cafeteriaId)->orWhere('cafeteria_id', null)->groupEnd();
        }

        // emp type filter
        $b->groupStart()->where('emp_type_id', $empTypeId)->orWhere('emp_type_id', 0)->groupEnd();

        $row = $b->get(1)->getFirstRow('array');
        return $row ? (float)$row['user_tk'] : null;
    }
    public function getCutOffInfo(int $mealTypeId)
    {
        $info = $this->cutoffResolver->getDefault($mealTypeId);
        return $this->respond([
            'cutoffDays' => $info['days'],
            'leadDays'   => $info['lead_days'],
            'cutOffTime' => $info['time'],
        ]);
    }


}
