<?php namespace App\Controllers\Employee;

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

    /** GET /employee/guest-subscriptions/new */
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

        return view('employee/guest/new', [
            'cutoffDays'      => $cutoffDays,
            'cut_off_time'    => $cutOffTime,
            'lead_days'       => $leadDays,
            'publicHolidays'  => $publicHolidays,
            'mealTypes'       => $this->mealTypeModel->findAll(),
            'cafeterias'      => $this->cafeteriaModel->findAll(),
            'validation'      => \Config\Services::validation(),
        ]);
    }

    /** POST /employee/guest-subscriptions/store */
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
            $otp    = ($status === 'ACTIVE') ? $this->getOtp() : null;
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
                //'remark'          => $this->request->getPost('remark'),
                'otp'               => $otp,
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
                    'approver_user_id'=> session('line_manager_id'),
                    'status'          => 'PENDING',
                    'subscription_type' => 'GUEST',
                ]);
            }
        }

        // 12) Final redirect
        $msg = $status === 'PENDING'
             ? 'Subscription pending approval.'
             : 'Subscription active.';

        return redirect()->to('employee/guest-subscriptions')
                         ->with('success', $msg);
    }


    /** GET /employee/guest-subscriptions */
    // public function index()
    // {
    //     $rows = $this->GuestSubscriptionModel
    //                 ->select('guest_subscriptions.*, meal_types.name as meal_type_name, cafeterias.name as caffname')
    //                 ->join('meal_types', 'meal_types.id = guest_subscriptions.meal_type_id', 'left')
    //                 ->join('cafeterias', 'cafeterias.id = guest_subscriptions.cafeteria_id', 'left')
    //                  ->where('user_id', session('user_id'))
    //                  ->orderBy('id','DESC')
    //                  ->findAll();

    //     //$this->dd($rows);

    //     return view('employee/guest/index', [
    //         'rows' => $rows,
    //     ]);
    // }

    public function index()
    {
        $rows = $this->GuestSubscriptionModel
            ->select('guest_subscriptions.*,
                    meal_types.name  AS meal_type_name,
                    cafeterias.name  AS caffname,
                    ct.cut_off_time  AS cutoff_time,
                    ct.lead_days     AS lead_days')
            ->join('meal_types',  'meal_types.id = guest_subscriptions.meal_type_id', 'left')
            ->join('cafeterias',  'cafeterias.id = guest_subscriptions.cafeteria_id', 'left')
            ->join('cutoff_times ct', 'ct.meal_type_id = guest_subscriptions.meal_type_id AND ct.is_active = 1', 'left')
            ->where('user_id', session('user_id'))
            ->orderBy('id','DESC')
            ->findAll();

        return view('employee/guest/index', ['rows' => $rows]);
    }


    public function unsubscribe($id)
    {
        // Mark the request cancelled
        $this->GuestSubscriptionModel->update($id, ['status'=>'CANCELLED']);
        //$this->dump_data($id);

        return redirect()->back()
                         ->with('success','Unsubscribed successfully.');
    }

}