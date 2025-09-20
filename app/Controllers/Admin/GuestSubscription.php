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

class GuestSubscription extends BaseController
{
    /** employment_types.id for PERSONAL GUEST per your dump */
    private const EMP_TYPE_PERSONAL_GUEST = 10;

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

    /** GET /admin/guest-subscriptions/new */
    public function new()
    {
        // default Lunch (1) like your view
        $mealTypeId = 1;

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

        return view('admin/guest/new', [
            'cutoffDays'      => $cutoffDays,
            'cut_off_time'    => $cut_off_time,
            'lead_days'       => $lead_days,
            'publicHolidays'  => $publicHolidays,
            'mealTypes'       => $this->mealTypeModel->findAll(),
            'cafeterias'      => $this->cafeteriaModel->where('is_active',1)->findAll(),
            'validation'      => \Config\Services::validation(),
        ]);
    }

    /** POST /admin/guest-subscriptions/store */
    public function store()
    {
        $rules = [
            'guest_name'   => 'required|max_length[255]',
            'phone'        => 'required|regex_match[/^[0-9]{11}$/]',
            'meal_type_id' => 'required|is_natural_no_zero',
            'cafeteria_id' => 'required|is_natural_no_zero',
            'meal_dates'   => 'required|string',   // CSV from flatpickr (d/m/Y)
            'remark'       => 'permit_empty|string',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $guestName   = trim((string)$this->request->getPost('guest_name'));
        $phone       = trim((string)$this->request->getPost('phone'));
        $mealTypeId  = (int)$this->request->getPost('meal_type_id');
        $cafeteriaId = (int)$this->request->getPost('cafeteria_id');
        $rawDates    = explode(',', (string)$this->request->getPost('meal_dates'));
        $remarkInput = trim((string)$this->request->getPost('remark'));

        $employeeUserId = (int)(session('user_id') ?? 0);
        $empTypeId      = self::EMP_TYPE_PERSONAL_GUEST;

        $tz = new \DateTimeZone('Asia/Dhaka');

        // Parse dates "d/m/Y" -> "Y-m-d"
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
        if (empty($dates)) {
            return redirect()->back()->withInput()->with('error', 'Please select at least one date.');
        }
        sort($dates);

        // Cutoff window
        $cut = $this->cutoffModel
            ->select('max_horizon_days, lead_days, cut_off_time')
            ->where('is_active', 1)
            ->where('cutoff_date', null)
            ->where('meal_type_id', $mealTypeId)
            ->orderBy('updated_at','DESC')->orderBy('id','DESC')
            ->first();

        if (! $cut) {
            return redirect()->back()->withInput()->with('error', 'Cut-off settings not found.');
        }

        $now   = new \DateTime('now', $tz);
        [$h,$m,$s] = explode(':', (string)$cut['cut_off_time']);
        $cutOffMoment = (clone $now)->setTime((int)$h,(int)$m,(int)$s);

        $daysToAdd = (int)($cut['lead_days'] ?? 0);
        if ($now > $cutOffMoment) $daysToAdd++;

        $earliest = (clone $now)->modify("+{$daysToAdd} days")->setTime(0,0,0);
        $latest   = (clone $now)->modify('+'.(int)($cut['max_horizon_days'] ?? 30).' days')->setTime(23,59,59);

        // Holidays + weekly holidays
        $holidays = array_column($this->holidayModel->where('is_active',1)->findAll(),'holiday_date');

        foreach ($dates as $dstr) {
            $d = \DateTime::createFromFormat('Y-m-d', $dstr, $tz)->setTime(0,0,0);
            if ($d < $earliest || $d > $latest) {
                return redirect()->back()->withInput()->with('error',
                    "Date {$dstr} must be between {$earliest->format('Y-m-d')} and {$latest->format('Y-m-d')}.");
            }
            if (in_array($dstr, $holidays, true)) {
                return redirect()->back()->withInput()->with('error', "Cannot subscribe on public holiday: {$dstr}");
            }
            if (in_array((int)$d->format('w'), [5,6], true)) {
                return redirect()->back()->withInput()->with('error', "Cannot subscribe on weekly holiday: {$dstr}");
            }
        }

        // Prevent duplicates (ACTIVE|PENDING) for those dates for this employee + guest type
        // $already = $this->subs
        //     ->where('user_id', $employeeUserId)
        //     ->where('meal_type_id', $mealTypeId)
        //     ->where('emp_type_id', $empTypeId)
        //     ->whereIn('status', ['ACTIVE','PENDING'])
        //     ->whereIn('subs_date', $dates)
        //     ->countAllResults();
        // if ($already > 0) {
        //     return redirect()->back()->withInput()->with('error', 'Some selected dates are already subscribed.');
        // }

        // Resolve approval flow (prefer exact emp_type, then ALL=0)
        $flow = $this->db->table('approval_flows')
            ->where('is_active', 1)
            ->where('meal_type_id', $mealTypeId)
            ->whereIn('emp_type_id', [$empTypeId, 0])
            ->orderBy("(emp_type_id = {$empTypeId})", 'DESC', false)
            ->orderBy('effective_date', 'DESC')
            ->get(1)->getFirstRow('array');

        $status = ($flow && strtoupper($flow['type']) === 'MANUAL') ? 'PENDING' : 'ACTIVE';

        // Resolve user share for PERSONAL GUEST
        $userTk = $this->resolveUserTk($mealTypeId, $empTypeId, $cafeteriaId);
        if ($userTk === null) {
            return redirect()->back()->withInput()->with('error', 'Please configure Contributions for Personal Guest first.');
        }

        // Prepare SMS & token (one OTP reused per selected dates)
        $otp = ($status === 'ACTIVE') ? $this->getOtp() : null;
        $cafRow = $this->cafeteriaModel->select('name')->find($cafeteriaId);
        $cafeteriaName = $cafRow['name'] ?? 'Cafeteria';

        // Transaction: insert subscriptions (+ approvals if pending) + remarks (+ tokens if active)
        $db = $this->db;
        $db->transStart();

        $nowStr = date('Y-m-d H:i:s');

        // Insert rows into meal_subscriptions
        foreach ($dates as $dstr) {
            $this->subs->insert([
                'user_id'      => $employeeUserId,
                'meal_type_id' => $mealTypeId,
                'emp_type_id'  => $empTypeId,
                'cafeteria_id' => $cafeteriaId,
                'subs_date'    => $dstr,
                'status'       => $status,
                'price'        => number_format((float)$userTk, 2, '.', ''),
                'created_by'   => (int)(session('user_id') ?? 0),
                'unsubs_by'    => 0,
                'created_at'   => $nowStr,
                'updated_at'   => $nowStr,
            ]);

            $sid = (int)$this->subs->getInsertID();

            // Add meal reference table guest info
            $db->table('meal_reference')->insert([
                'subs_id'      => $sid,
                'ref_name'     => $guestName,
                'ref_phone'    => $phone,
                'otp'          => $otp,
            ]);

            // Add a remark row capturing guest info
            $db->table('remarks')->insert([
                'subs_id'         => $sid,
                'remark'          => $remarkInput,
            ]);

        }

        // If flow is MANUAL, create one approval row per PENDING subscription (first step logic)
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

            if ($subsIds) {
                // skip if already approved rows exist
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

        // Queue one SMS with the OTP & all dates (only if ACTIVE)
        if ($status === 'ACTIVE' && $otp) {
            $niceDates = $this->formatDatesCompact($dates); //// e.g. "Aug 31, Sep 1-3, 2025"
            $message   = "bKash Lunch OTP: {$otp}. Valid once per day on {$niceDates}, at Cafeteria {$cafeteriaName} only. Thank you";
            $this->send_sms($phone, $message);
        }

        $db->transComplete();
        if (! $db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Failed to save guest subscription.');
        }

        return redirect()->to('admin/guest-subscriptions')
            ->with('success', $status === 'PENDING' ? 'Subscription pending approval.' : 'Subscription active.');
    }

    /** GET /admin/guest-subscriptions/all-guest-list */
    public function index($listType = null)
    {
        if ($listType === null) {
            $uid = (int) (session('user_id') ?? 0);
            $empTypeId = self::EMP_TYPE_PERSONAL_GUEST;
            $viewPage = 'admin/guest/index';
        }
        elseif ($listType === 'all') {
            $empTypeId = self::EMP_TYPE_PERSONAL_GUEST;
            $viewPage = 'admin/guest/all_guest_list';
        }
        elseif ($listType === 'bulk') {
            $empTypeId = [8,9]; // HR GUEST=8, PROJECT GUEST=9
            $viewPage = 'admin/guest/bulk_list';
        } 
        else {
            throw new \RuntimeException('Invalid list type.');
        }

        $this->subs
            ->select("meal_subscriptions.*,
                      meal_types.name  AS meal_type_name,
                      cafeterias.name  AS caffname,
                      et.name               AS emp_type_name,
                      mr.ref_name           AS ref_name,
                      mr.ref_phone          AS ref_phone,
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

            if ($listType === null) {
                $this->subs->where('meal_subscriptions.user_id', $uid);
            }

            if (is_array($empTypeId)) {
                $this->subs->whereIn('meal_subscriptions.emp_type_id', $empTypeId);
            } else {
                $this->subs->where('meal_subscriptions.emp_type_id', $empTypeId);
            }

            $this->subs->orderBy('meal_subscriptions.id', 'DESC');

            $rows = $this->subs->findAll();

        return view($viewPage, ['rows' => $rows]);
    }

    /** GET /admin/guest-subscriptions/upload */
    public function uploadForm()
    {
        ['days' => $days, 'time' => $time, 'lead_days' => $lead] = $this->cutoffResolver->getDefault(1);

        $today      = date('Y-m-d');
        $cutoffDate = date('Y-m-d', strtotime("+{$days} days"));
        $holidays   = $this->cutoffResolver->getHolidays($today, $cutoffDate);

        return view('admin/guest/upload', [
            'cutoffDays'     => $days,
            'cut_off_time'   => $time,
            'lead_days'      => $lead,
            'publicHolidays' => $holidays,
            'mealTypes'      => $this->mealTypeModel->whereIn('id', [1,2,3])->findAll(),
            'cafeterias'     => $this->cafeteriaModel->where('is_active',1)->findAll(),
            'guestTypes'     => $this->employmentTypeModel->where('is_active', 1)->whereIn('id', [8, 9])->findAll(),
            'validation'     => \Config\Services::validation(),
        ]);
    }


    /** POST /admin/guest-subscriptions/process-upload */
    public function processUpload()
    {
        if (! $this->validate($this->validateUpload())) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $mealTypeId = (int)$this->request->getPost('meal_type_id');
        $guestTypeId = (int)$this->request->getPost('guest_type_id'); // e.g., 8 HR GUEST, 9 PROJECT GUEST, 10 PERSONAL GUEST
        $dates = $this->parseMealDates();

        // Resolve flow & status
        $flow = $this->getApprovalFlow($mealTypeId, $guestTypeId);
        $status = ($flow && strtoupper($flow['type']) === 'MANUAL') ? 'PENDING' : 'ACTIVE';

        // Parse rows from uploaded Excel
        $filePath = $this->handleUploadedFile();
        $rows = $this->csvSubscriptionImporter->parseExcelGuest($filePath); // each row: [name, phone, cafeteriaName]
        $this->deleteFileIfExists($filePath);

        // Insert everything
        $result = $this->insertSubscriptionsBulk($mealTypeId, $guestTypeId, $dates, $rows, $status, $flow);
        if ($result[0] !== true) {
            return redirect()->to('admin/guest-subscriptions/bulk-list')->with('success', $result[1]);
        }

        return redirect()->to('admin/guest-subscriptions/bulk-list')->with('success', 'Excel processed and subscriptions created.');
    }

    /** POST /admin/guest-subscriptions/unsubscribe/(:num) */
    public function unsubscribe($id)
    {
        $ok = $this->subs->update((int)$id, [
            'status'     => 'CANCELLED',
            'unsubs_by'  => (int)(session('user_id') ?? 0),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with($ok ? 'success' : 'error', $ok ? 'Unsubscribed successfully.' : 'Unable to unsubscribe.');
    }

    /** POST /admin/guest-subscriptions/unsubscribe-bulk */
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

    protected function validateUpload(): array
    {
        return [
            'meal_type_id'  => 'required|is_natural_no_zero',
            'guest_type_id' => 'required|is_natural_no_zero',
            'meal_dates'    => 'required|string',
            'xlsx_file'     => 'uploaded[xlsx_file]|ext_in[xlsx_file,xlsx,xls]',
        ];
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
        int $empTypeId,
        array $dates,
        array $rows,         // parsed from excel: [ [name, phone, cafeteriaName], ... ]
        string $status,
        ?array $flow
    ) : array {
        if (empty($dates) || empty($rows)) {
            return [true];
        }

        $db = $this->db;
        $db->transStart();

        $nowStr  = date('Y-m-d H:i:s');
        $userId  = (int)(session('user_id') ?? 0);
        $namesToSms = []; // phone => [otp, cafeteriaName, dates[]]

        foreach ($rows as [$name, $phone, $cafeteriaName]) {
            $name  = trim((string)$name);
            $phone = preg_replace('/\s+/', '', (string)$phone);

            if (!preg_match('/^\d{11}$/', $phone)) {
                // Collect message but continue processing others
                $db->transComplete();
                return [false, "Invalid phone for {$name}: {$phone}. Must be exactly 11 digits."];
            }

            $cafeteriaId = $this->resolveCafeteriaId($cafeteriaName);

            // Resolve user share for the guest type
            $userTk = $this->resolveUserTk($mealTypeId, $empTypeId, $cafeteriaId);
            if ($userTk === null) {
                $db->transComplete();
                return [false, "Missing Contributions for {$name} ({$phone})"];
            }

            $otp = ($status === 'ACTIVE') ? $this->getOtp() : null;

            foreach ($dates as $dstr) {
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

                // store brief remark with guest identity
                $remark = trim((string) $this->request->getPost('remark'));
                $db->table('remarks')->insert([
                    'subs_id'         => $sid,
                    'remark'          => $remark,
                    'created_at'      => $nowStr,
                ]);

                // Add meal reference table guest info
                $db->table('meal_reference')->insert([
                    'subs_id'      => $sid,
                    'ref_name'     => $name,
                    'ref_phone'    => $phone,
                    'otp'          => $otp,
                ]);
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

            // All just-created PENDING rows for this uploader and guest type
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

        // Queue SMS per guest (ACTIVE)
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

        return [true];
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

}
