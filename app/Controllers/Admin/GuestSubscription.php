<?php namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\GuestSubscriptionModel;
use App\Models\GuestBatchModel;
use App\Models\ApprovalFlowModel;
use App\Models\ApprovalStepModel;
use App\Models\MealApprovalModel;
use App\Models\MealTypeModel;
use App\Models\CafeteriaModel;
use App\Models\CutoffTimeModel;
use App\Models\PublicHolidayModel;
use App\Libraries\CutoffResolver;

use App\Services\Intern\CsvSubscriptionImporter;

class GuestSubscription extends BaseController
{
    protected $GuestSubscriptionModel, $GuestBatchModel, $flowModel, $ApprovalStepModel, $MealApprovalModel, $cutoffModel, $holidayModel, $cafeteriaModel, $mealTypeModel, $csvSubscriptionImporter, $cutoffResolver;

    public function __construct()
    {
        helper(['form','url']);
        $this->GuestSubscriptionModel  = new GuestSubscriptionModel();
        $this->GuestBatchModel         = new GuestBatchModel();
        $this->flowModel               = new ApprovalFlowModel();
        $this->ApprovalStepModel       = new ApprovalStepModel();
        $this->MealApprovalModel       = new MealApprovalModel();
        $this->cutoffModel             = new CutoffTimeModel();
        $this->holidayModel            = new PublicHolidayModel();
        $this->cafeteriaModel          = new CafeteriaModel();
        $this->mealTypeModel           = new MealTypeModel();

        $this->csvSubscriptionImporter = new CsvSubscriptionImporter();

        $this->cutoffResolver          = new CutoffResolver(
            new CutoffTimeModel(),
            new PublicHolidayModel()
        );
    }

    /** GET /admin/guest-subscriptions/new */
    public function new()
    {
        $cutoffRow = $this->cutoffModel
                    ->select('max_horizon_days, cut_off_time, lead_days')
                    ->where('is_active', 1)
                    ->where('meal_type_id ', 1)
                    ->first();
        
        // If nothing was returned, $cutoffRow will be null
        if (empty($cutoffRow)) {  // No active data found
            $cutoffDays = 30;  
            $cutOffTime = '22:00:00';
            $leadDays = 1;
        } else {
            // We have an array like ['max_horizon_days' => '15']
            $cutoffDays = (int) $cutoffRow['max_horizon_days'];
            $cutOffTime = $cutoffRow['cut_off_time'];
            $leadDays = $cutoffRow['lead_days'];
        }

        $today      = date('Y-m-d');
        $cutoffDate = date('Y-m-d', strtotime("+{$cutoffDays} days"));

        // 2) Pull back only active holidays between today and cutoff
        $publicHolidays = $this->holidayModel
            ->select('holiday_date')
            ->where('is_active', 1)
            ->where('holiday_date >=', $today)
            ->where('holiday_date <=', $cutoffDate)
            ->orderBy('holiday_date', 'ASC')
            ->findColumn('holiday_date');


        //$this->dump_data($leadDays);

        return view('admin/guest/new', [
            'cutoffDays'      => $cutoffDays,
            'cut_off_time'    => $cutOffTime,
            'lead_days'       => $leadDays,
            'publicHolidays'  => $publicHolidays,
            'mealTypes'       => $this->mealTypeModel->findAll(),
            'cafeterias'      => $this->cafeteriaModel->findAll(),
            'validation'      => \Config\Services::validation(),
        ]);
    }

    /** POST /admin/guest-subscriptions/store */
    public function store()
    {
        // 1) Basic validation
        $rules = [
            'guest_name'   => 'required|max_length[255]',
            'phone'        => 'required|max_length[14]',
            'meal_type_id' => 'required|integer',
            'meal_dates'   => 'required|string',   // now a CSV of dates
            'cafeteria_id' => 'required|integer',
            'remark'       => 'string',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()
                             ->withInput()
                             ->with('validation', $this->validator);
        }

        // 2) Pull inputs
        $mealTypeId  = (int) $this->request->getPost('meal_type_id');
        $phone       = $this->request->getPost('phone');
        $cafeteriaId = (int) $this->request->getPost('cafeteria_id');
        $rawDates    = explode(',', $this->request->getPost('meal_dates'));

        // Timezone
        $tz = new \DateTimeZone('Asia/Dhaka');

        // 3) Parse & normalize dates → ['YYYY-MM-DD', …]
        $dates = [];
        foreach ($rawDates as $r) {
            $r  = trim($r);
            $dt = \DateTime::createFromFormat('d/m/Y', $r, $tz);
            if (! $dt) {
                session()->setFlashdata('error', "Invalid date format: {$r}");
                return redirect()->back()->withInput();
            }
            $dates[] = $dt->format('Y-m-d');
        }

        // Sort ascending just in case
        sort($dates);
        $startDate = $dates[0];
        $endDate   = end($dates);

        // 4) Approval‐flow lookup
        $userType = 'PERSONAL GUEST';
        $today    = (new \DateTime('now', $tz))->format('Y-m-d');
        $flow = $this->flowModel
                     ->where('user_type',    $userType)
                     ->where('meal_type_id',  $mealTypeId)
                     ->where('effective_date <=', $today)
                     ->where('is_active',     1)
                     ->first();

        $status = ($flow && $flow['type'] === 'MANUAL')
                ? 'PENDING'
                : 'ACTIVE';

        // 5) Cut‐off / lead‐days / horizon
        $cut = $this->cutoffModel
                    ->where('meal_type_id', $mealTypeId)
                    ->where('cutoff_date', null)
                    ->where('is_active',    1)
                    ->first();
        if (! $cut) {
            session()->setFlashdata('error', 'Cut-off settings not found');
            return redirect()->back()->withInput();
        }

        // now & cutOffMoment
        $now = new \DateTime('now', $tz);
        [$h, $m, $s] = explode(':', $cut['cut_off_time']);
        $cutOffMoment = (clone $now)->setTime((int)$h, (int)$m, (int)$s);

        // compute earliest allowed date
        $daysToAdd = (int)$cut['lead_days'];
        if ($now > $cutOffMoment) {
            // past cut-off today, push earliest one more day
            $daysToAdd++;
        }

        // earliest = today + daysToAdd at 00:00:00
        $earliest = (clone $now)
                    ->modify("+{$daysToAdd} days")
                    ->setTime(0, 0, 0);

        // latest = today + max_horizon_days at 23:59:59
        $latest = (clone $now)
                  ->modify('+' . (int)$cut['max_horizon_days'] . ' days')
                  ->setTime(23, 59, 59);

        // 6) Holiday list
        $holidays = array_column(
            $this->holidayModel->where('is_active',1)->findAll(),
            'holiday_date'
        );

        // 7) Per-date validations: cutoff window, holiday, weekly holiday
        foreach ($dates as $dstr) {
            $d = \DateTime::createFromFormat('Y-m-d', $dstr, $tz)
                 ->setTime(0, 0, 0);

            // 7a) cut-off window
            if ($d < $earliest || $d > $latest) {
                session()->setFlashdata(
                    'error',
                    "Date {$dstr} must be between "
                    . $earliest->format('Y-m-d')
                    . " and "
                    . $latest->format('Y-m-d')
                );
                return redirect()->back()->withInput();
            }

            // 7b) public holiday
            if (in_array($dstr, $holidays, true)) {
                session()->setFlashdata('error', "Cannot subscribe on public holiday: {$dstr}");
                return redirect()->back()->withInput();
            }

            // 7c) weekly holiday (Friday=5, Saturday=6)
            if (in_array((int)$d->format('w'), [5,6], true)) {
                session()->setFlashdata('error', "Cannot subscribe on weekly holiday: {$dstr}");
                return redirect()->back()->withInput();
            }
        }

        // 8) check same day meal subs active by phone no
        $existing = $this->GuestSubscriptionModel
                         ->where('user_id', session('user_id'))
                         ->where('status', 'ACTIVE')
                         ->where('phone', $phone)
                         ->whereIn('subscription_date', $dates)
                         ->countAllResults();

        if ($existing > 0) {
            session()->setFlashdata(
                'error',
                'You already have subscription(s) on one or more of those dates.'
            );
            return redirect()->back()->withInput();
        }

        

        // 11) Enqueue approval steps if needed
        if ($status === 'PENDING' && $flow) {
            $steps = $this->ApprovalStepModel
                          ->where('flow_id', $flow['id'])
                          ->orderBy('step_order','ASC')
                          ->findAll();

        }
        if ($flow && empty($steps)) {
            return redirect()->back()->with('error', 'Approval flow defined, but no steps configured. Contact admin.');
        }

        $subId = $this->GuestBatchModel->insert([
            'user_id'           => session('user_id'),
            'meal_type_id'      => $mealTypeId,
            'cafeteria_id'      => $cafeteriaId,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'status'            => $status,
            'guest_type'        => $userType,
            'remark'            => $this->request->getPost('remark'),
        ]);
        
        $otp    = ($status === 'ACTIVE') ? $this->getOtp() : null;

        $row = $this->cafeteriaModel 
                    ->select('name')
                    ->where('id', $cafeteriaId)
                    ->get()
                    ->getRowArray();
        $cafeteriaName = $row['name'] ?? 'Cafeteria';

        // 9) Insert rows
        foreach ($dates as $dstr) {


            //var_dump($dstr);exit;
            //$this->dump_data([session('user_id'),$phone, $dstr, $cafeteriaId]);
            $this->GuestSubscriptionModel->insert([
                'user_id'           => session('user_id'),
                'batch_id'          => $subId,
                'guest_name'        => $this->request->getPost('guest_name'),
                'phone'             => $phone,
                'guest_type'        => 'PERSONAL GUEST', // INTERN, OS, FTC, NEW_JOIN
                'subscription_date' => $dstr,
                'meal_type_id'      => $mealTypeId,  // lunch,ifter, seheri
                'cafeteria_id'      => $cafeteriaId,
                'status'            => $status,
                //'remark'            => $this->request->getPost('remark'),
                'otp'           => $otp,   
            ]);

        }
        // send sms start
        // Build the compact date message from the whole $dates array
        $niceDates = $this->formatDatesCompact($dates); // e.g. "Aug 31, Sep 1-3, 2025"
        // Now compose and send the SMS
        $message = "bKash Lunch OTP: {$otp}. Valid once per day on {$niceDates}, at Cafeteria {$cafeteriaName} only. Thank you";
        $this->send_sms($phone, $message);
        // send sms end
        
        // 5) If steps exist, insert into meal_approvals
        if ($status === 'PENDING' && !empty($steps)) {
            foreach ($steps as $step) {
                $this->MealApprovalModel->insert([
                    'subscription_id' => $subId,
                    'step_id'         => $step['id'],
                    'approver_role'   => $step['approver_role'],
                    'status'          => 'PENDING',
                    'subscription_type' => 'GUEST',
                ]);
            }
        }

        // 12) Final redirect
        $msg = $status === 'PENDING'
             ? 'Subscription pending approval.'
             : 'Subscription active.';

        return redirect()->to('admin/guest-subscriptions')
                         ->with('success', $msg);
    }


    /** GET /admin/guest-subscriptions */
    public function index()
    {
        $rows = $this->GuestSubscriptionModel
            ->select('guest_subscriptions.*,
                    meal_types.name    AS meal_type_name,
                    cafeterias.name    AS caffname,
                    ct.cut_off_time    AS cutoff_time,
                    ct.lead_days       AS lead_days')
            ->join('meal_types',   'meal_types.id = guest_subscriptions.meal_type_id', 'left')
            ->join('cafeterias',   'cafeterias.id = guest_subscriptions.cafeteria_id', 'left')
            ->join('cutoff_times ct', 'ct.meal_type_id = guest_subscriptions.meal_type_id AND ct.is_active = 1', 'left')
            ->where('guest_subscriptions.user_id', session('user_id'))
            ->orderBy('guest_subscriptions.id','DESC')
            ->findAll();

        return view('admin/guest/index', ['rows' => $rows]);
    }


    public function allGuestList()
    {
        $rows = $this->GuestSubscriptionModel
            ->select('guest_subscriptions.*,
                    meal_types.name   AS meal_type_name,
                    cafeterias.name   AS caffname,
                    users.employee_id,
                    users.name,
                    ct.cut_off_time   AS cutoff_time,
                    ct.lead_days      AS lead_days')
            ->join('meal_types',   'meal_types.id = guest_subscriptions.meal_type_id', 'left')
            ->join('cafeterias',   'cafeterias.id = guest_subscriptions.cafeteria_id', 'left')
            ->join('users',        'users.id = guest_subscriptions.user_id', 'left')
            ->join('cutoff_times ct', 'ct.meal_type_id = guest_subscriptions.meal_type_id AND ct.is_active = 1', 'left')
            ->where('guest_subscriptions.guest_type', 'PERSONAL GUEST')
            ->orderBy('guest_subscriptions.id','DESC')
            ->findAll();

        return view('admin/guest/all_guest_list', ['rows' => $rows]);
    }


    public function unsubscribe($id)
    {
        // Mark the request cancelled
        $this->GuestSubscriptionModel->update($id, ['status'=>'CANCELLED']);
        //$this->dump_data($id);

        return redirect()->back()
                         ->with('success','Unsubscribed successfully.');
    }

    public function bulkList()
    {
        $rows = $this->GuestSubscriptionModel
            ->select('guest_subscriptions.*,
                    meal_types.name   AS meal_type_name,
                    cafeterias.name   AS caffname,
                    users.employee_id,
                    users.name,
                    ct.cut_off_time   AS cutoff_time,
                    ct.lead_days      AS lead_days')
            ->join('meal_types',   'meal_types.id = guest_subscriptions.meal_type_id', 'left')
            ->join('cafeterias',   'cafeterias.id = guest_subscriptions.cafeteria_id', 'left')
            ->join('users',        'users.id = guest_subscriptions.user_id', 'left')
            ->join('cutoff_times ct', 'ct.meal_type_id = guest_subscriptions.meal_type_id AND ct.is_active = 1', 'left') // add extra filters if needed
            ->where('guest_subscriptions.guest_type !=', 'PERSONAL GUEST')
            ->orderBy('guest_subscriptions.id','DESC')
            ->findAll();

        return view('admin/guest/bulk_list', ['rows' => $rows]);
    }



    // Bulk Upload
    public function uploadForm()
    {
        ['days' => $days, 'time' => $time, 'lead_days' => $lead] =
            $this->cutoffResolver->getDefault(1);

        $today       = date('Y-m-d');
        $cutoffDate  = date('Y-m-d', strtotime("+{$days} days"));
        $holidays    = $this->cutoffResolver->getHolidays($today, $cutoffDate);

        return view('admin/guest/upload', [
            'cutoffDays'     => $days,
            'cut_off_time'   => $time,
            'lead_days'      => $lead,
            'publicHolidays' => $holidays,
            'mealTypes'      => $this->mealTypeModel->whereIn('id', [1, 2, 3])->findAll(),
            'cafeterias'     => $this->cafeteriaModel->findAll(),
            'validation'     => \Config\Services::validation(),
        ]);
    }

    public function processUpload()
    {
        if (! $this->validate($this->validateUpload())) {
            return redirect()->back()->withInput()
                             ->with('validation', $this->validator);
        }

        $mealTypeId = (int) $this->request->getPost('meal_type_id');
        $guestType = $this->request->getPost('guest_type_id');
        $dates = $this->parseMealDates();
        $filePath = $this->handleUploadedFile();
        $rows = $this->csvSubscriptionImporter->parseExcelGuest($filePath);
        $this->deleteFileIfExists($filePath);

        $flow   = $this->getApprovalFlow($mealTypeId, $guestType);
        $status = ($flow && $flow['type'] === 'MANUAL') ? 'PENDING' : 'ACTIVE';

        $batchId = $this->createBatch($mealTypeId, $dates, $status, $guestType);

        if ($status === 'PENDING') {
            $steps = $this->getApprovalSteps($flow['id']);
            if (empty($steps)) {
                return redirect()->back()->with('error', 'Approval flow defined, but no steps configured. Contact admin.');
            }
            $this->insertApprovalSteps($batchId, $steps, $guestType);
        }

        $result = $this->insertSubscriptions($batchId, $mealTypeId, $dates, $rows, $status, $guestType);
        if ($result[0] !== true) {
            //$this->dd($result[1]);
            return redirect()
                ->to('admin/guest-subscriptions/bulk-list')
                ->with('success', $result[1]);
        }

        return redirect()
            ->to('admin/guest-subscriptions/bulk-list')
            ->with('success', 'Excel processed and subscriptions created.');
    }

    protected function validateUpload(): array
    {
        return [
            'meal_type_id' => 'required|integer',
            'guest_type_id' => 'required',
            'meal_dates'   => 'required|string',
            //'xlsx_file'     => 'required',
        ];
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
            throw new \RuntimeException('Invalid XLSX upload.');
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

    protected function createBatch(int $mealTypeId, array $dates, string $status, $guestType): int
    {
        return $this->GuestBatchModel->insert([
            'uploaded_by'       => session('user_id'),
            'meal_type_id'      => $mealTypeId,
            'start_date'        => reset($dates),
            'end_date'          => end($dates),
            'status'            => $status,
            'guest_type'        => $guestType,
            'remark'            => request()->getPost('remark'),
        ]);
    }

    protected function insertSubscriptions(
        int $batchId,
        int $mealTypeId,
        array $dates,
        array $rows,
        string $status,
        string $guestType
    ) {
        if (empty($dates) || empty($rows)) {
            return [true];
        }

        $result = [true];
    
        $remark = (string) $this->request->getPost('remark');
    
        foreach ($rows as [$name, $phone, $cafeteriaName]) {
            $name         = trim((string) $name);
            $phone        = preg_replace('/\s+/', '', (string) $phone);
            $cafeteriaId  = $this->resolveCafeteriaId($cafeteriaName);

            if (!preg_match('/^\d{11}$/', $phone)) {
                // skip or collect for reporting
                $result = [false, "Invalid phone for {$name}: {$phone}. Must be exactly 11 digits."];
                continue;
            }
    
            // One OTP per guest covering all selected dates (change if you want per-day OTP)
            $otp = ($status === 'ACTIVE') ? $this->getOtp() : null;
    
            foreach ($dates as $date) {
                // Optional: prevent duplicates for same phone + mealType + date
                // if ($this->guestSubscriptionExists($phone, $mealTypeId, $date)) { continue; }
    
                $this->GuestSubscriptionModel->insert([
                    'user_id'           => session('user_id'),
                    'batch_id'          => $batchId,
                    'meal_type_id'      => $mealTypeId,
                    'guest_name'        => $name,
                    'phone'             => $phone,
                    'subscription_date' => $date, // expects Y-m-d
                    'guest_type'        => $guestType, // PERSONAL GUEST, etc.
                    'cafeteria_id'      => $cafeteriaId,
                    'status'            => $status,
                    'remark'            => $remark,
                    'otp'               => $otp,   // same OTP reused per day (valid once per day)
                ]);
            }
    
            // Send exactly one SMS per guest summarizing all dates
            if ($status === 'ACTIVE' && $otp) {
                $niceDates = $this->formatDatesCompact($dates); // e.g. "Aug 31, Sep 1-3, 2025"
                $message   = "bKash Lunch OTP: {$otp}. Valid once per day on {$niceDates}, at Cafeteria {$cafeteriaName} only. Thank you";
                $this->send_sms($phone, $message);
            }
        }
    
        return $result;
    }
    


    protected function getApprovalSteps(int $flowId): array
    {
        return $this->ApprovalStepModel
            ->where('flow_id', $flowId)
            ->orderBy('step_order', 'ASC')
            ->findAll();
    }

    protected function insertApprovalSteps(int $batchId, array $steps, $guestTypeId)
    {
        foreach ($steps as $step) {
            $this->ApprovalStepModel->insert([
                'subscription_type' => $guestTypeId,
                'subscription_id'   => $batchId,
                'step_id'           => $step['id'],
                'approver_role'     => $step['approver_type'] === 'ROLE' ? $step['approver_role'] : null,
                'approver_user_id'  => $step['approver_type'] === 'USER' ? $step['approver_user_id'] : null,
                'approval_status'   => 'PENDING',
            ]);
        }
    }

    protected function resolveCafeteriaId(?string $name): ?int
    {
        if (empty($name)) {
            return null;
        }

        $cafeteria = $this->cafeteriaModel
            ->select('id')
            ->where('name', $name)
            ->where('is_active', 1)
            ->first();

        return $cafeteria['id'] ?? null;
    }

    public function unsubscribe_bulk()
    {
        $ids = $this->request->getPost('subscription_ids');
        $remark = $this->request->getPost('remark');

        //$this->dd($remark);
        if (!is_array($ids) || empty($ids)) {
            return redirect()->back()->with('error', 'No subscriptions selected.');
        }

        // Example: update all selected subscriptions to CANCELLED
        $this->GuestSubscriptionModel
            ->whereIn('id', $ids)
            ->set('status', 'CANCELLED')
            ->set('approver_remark', $remark)
            ->update();

        return redirect()->back()->with('success', 'Selected subscriptions unsubscribed.');
    }

}