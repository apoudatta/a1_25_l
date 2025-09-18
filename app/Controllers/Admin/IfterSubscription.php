<?php namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MealSubscriptionDetailModel;
use App\Models\MealSubscriptionModel;
use App\Models\ApprovalFlowModel;
use App\Models\ApprovalStepModel;
use App\Models\MealApprovalModel;
use App\Models\CutoffTimeModel;
use App\Models\PublicHolidayModel;
use App\Models\MealTypeModel;
use App\Models\CafeteriaModel;
use App\Models\RamadanConfigModel;
use CodeIgniter\Exceptions\PageForbiddenException;
use DateTime;
use DateTimeZone;

class IfterSubscription extends BaseController
{
    protected $MealSubscriptionModel;
    protected $ApprovalFlowModel;
    protected $ApprovalStepModel;
    protected $MealApprovalModel;
    protected $CutoffTimeModel;
    protected $PublicHolidayModel;
    protected $MealSubscriptionDetailModel;
    protected $RamadanConfigModel;

    public function __construct()
    {
        $this->MealSubscriptionModel      = new MealSubscriptionModel();
        $this->MealSubscriptionDetailModel= new MealSubscriptionDetailModel();
        $this->ApprovalFlowModel          = new ApprovalFlowModel();
        $this->ApprovalStepModel          = new ApprovalStepModel();
        $this->MealApprovalModel          = new MealApprovalModel();
        $this->CutoffTimeModel            = new CutoffTimeModel();
        $this->PublicHolidayModel         = new PublicHolidayModel();
        $this->RamadanConfigModel         = new RamadanConfigModel();
    }

    public function history()
    {
        $subs = $this->MealSubscriptionDetailModel
            ->select('meal_subscription_details.*,
                    cafeterias.name  AS caffname,
                    meal_types.name  AS meal_type_name,
                    ct.cut_off_time  AS cutoff_time,
                    ct.lead_days     AS lead_days')
            ->join('cafeterias',  'cafeterias.id = meal_subscription_details.cafeteria_id', 'left')
            ->join('meal_types',  'meal_types.id = meal_subscription_details.meal_type_id', 'left')
            ->join('cutoff_times ct', 'ct.meal_type_id = meal_subscription_details.meal_type_id AND ct.is_active = 1', 'left')
            ->where('meal_subscription_details.user_id', session('user_id'))
            ->where('meal_subscription_details.meal_type_id', 2) // Ifter
            ->orderBy('meal_subscription_details.id','DESC')
            ->findAll();

        return view('admin/ifter_subscription/history', ['subs' => $subs]);
    }


    public function allIfterList()
    {
        $subs = $this->MealSubscriptionDetailModel
            ->select('meal_subscription_details.*,
                    meal_types.name       AS meal_type_name,
                    cafeterias.name       AS caffname,
                    users.employee_id,
                    users.name,
                    ct.cut_off_time       AS cutoff_time,
                    ct.lead_days          AS lead_days')
            ->join('cafeterias',  'cafeterias.id  = meal_subscription_details.cafeteria_id', 'left')
            ->join('users',       'users.id       = meal_subscription_details.user_id', 'left')
            ->join('meal_types',  'meal_types.id  = meal_subscription_details.meal_type_id', 'left')
            ->join('cutoff_times ct', 'ct.meal_type_id = meal_subscription_details.meal_type_id AND ct.is_active = 1', 'left')
            ->where('meal_subscription_details.meal_type_id', 2) // Ifter
            ->orderBy('meal_subscription_details.id','DESC')
            ->findAll();

        return view('admin/ifter_subscription/all_ifter_list', ['subs' => $subs]);
    }


    /** Show new-subscription form */
    public function new()
    {
        $mealM = new MealTypeModel();
        $cafM  = new CafeteriaModel();

        $ramadanConfig = $this->RamadanConfigModel->orderBy('id','DESC')->first();
        $romadanStart = $ramadanConfig['start_date'];
        $romadanEnd = $ramadanConfig['end_date'];

        //$this->dd([$romadanStart, $romadanEnd]);
        $cutoffRow = $this->CutoffTimeModel
                    ->select('max_horizon_days, cut_off_time, lead_days')
                    ->where('cutoff_date', null)  // for eid meal cutoff date not null
                    ->where('is_active', 1)
                    ->where('meal_type_id ', 2) // for ifter
                    ->first();
        
        // If nothing was returned, $cutoffRow will be null
        if (empty($ramadanConfig)) {  // No active data found
            $cutoffDays = 30;  
            $cutOffTime = '22:00:00';
            $leadDays = 1;
        } else {
            // We have an array like ['max_horizon_days' => '15']
            $cutoffDays = (int) $cutoffRow['max_horizon_days'];
            $cutOffTime = $cutoffRow['cut_off_time'];
            $leadDays = $cutoffRow['lead_days'];
        }

        $today      = $romadanStart;
        $cutoffDate = $romadanEnd;

        // 2) Pull back only active holidays between today and cutoff
        $publicHolidays = $this->PublicHolidayModel
            ->select('holiday_date')
            ->where('is_active', 1)
            ->where('holiday_date >=', $today)
            ->where('holiday_date <=', $cutoffDate)
            ->orderBy('holiday_date', 'ASC')
            ->findColumn('holiday_date');


        // 3) Fetch only this user’s registrations within [today … cutoffDate]
        $registeredDates = $this->MealSubscriptionDetailModel
            ->select('subscription_date')
            ->where('user_id', session()->get('user_id'))
            ->where('meal_type_id', 2)
            ->whereIn('status', ['ACTIVE', 'PENDING'])
            ->where('subscription_date >=', $today)
            ->where('subscription_date <=', $cutoffDate)
            ->orderBy('subscription_date', 'ASC')
            ->findColumn('subscription_date');

        //$this->dd($registeredDates);

        return view('admin/ifter_subscription/new', [
            'cutoffDays'      => $cutoffDays,
            'cut_off_time'    => $cutOffTime,
            'lead_days'       => $leadDays,
            'registeredDates' => $registeredDates,
            'publicHolidays'  => $publicHolidays,
            'mealTypes'       => $mealM->where('id', 2)->findAll(),
            'cafeterias'      => $cafM->findAll(),
            'validation'      => \Config\Services::validation(),
            'reg_start_date'   => $romadanStart,
            'reg_end_date'     => $romadanEnd,
        ]);
    }

    public function store()
    {
        // 1) Basic validation
        $rules = [
            'meal_type_id' => 'required|integer',
            'meal_dates'   => 'required|string',   // now a CSV of dates
            'cafeteria_id' => 'required|integer',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()
                            ->withInput()
                            ->with('validation', $this->validator);
        }

        // 2) Pull inputs
        $userId      = (int) $this->request->getPost('employee_id');
        $mealTypeId  = (int) $this->request->getPost('meal_type_id');
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
        sort($dates);
        $startDate = $dates[0];
        $endDate   = end($dates);

        // 4) Fetch Ramadan window
        $ramadanConfig = $this->RamadanConfigModel
                            ->orderBy('id','DESC')
                            ->first();
        if (empty($ramadanConfig)) {
            session()->setFlashdata('error', 'Ramadan configuration not found');
            return redirect()->back()->withInput();
        }
        // Inclusive window: [00:00 of start_date ... 23:59:59 of end_date]
        $ramadanStart = \DateTime::createFromFormat('Y-m-d', $ramadanConfig['start_date'], $tz)
                                ->setTime(0, 0, 0);
        $ramadanEnd   = \DateTime::createFromFormat('Y-m-d', $ramadanConfig['end_date'],   $tz)
                                ->setTime(23, 59, 59);

        // 5) Approval‐flow lookup (unchanged)
        $userType = 'ADMIN';
        $today    = (new \DateTime('now', $tz))->format('Y-m-d');
        $flow = $this->ApprovalFlowModel
                    ->where('user_type',   $userType)
                    ->where('meal_type_id', $mealTypeId)
                    ->where('effective_date <=', $today)
                    ->where('is_active',    1)
                    ->first();
        $status = ($flow && $flow['type'] === 'MANUAL')
                ? 'PENDING'
                : 'ACTIVE';

        // 6) Holiday list
        $holidays = array_column(
            $this->PublicHolidayModel->where('is_active',1)->findAll(),
            'holiday_date'
        );

        // 7) Per-date validations: Ramadan window, public holiday, weekly holiday
        foreach ($dates as $dstr) {
            $d = \DateTime::createFromFormat('Y-m-d', $dstr, $tz)
                ->setTime(0, 0, 0);

            // 7a) Ramadan window check
            if ($d < $ramadanStart || $d > $ramadanEnd) {
                session()->setFlashdata(
                    'error',
                    "Date {$dstr} must be between "
                    . $ramadanStart->format('Y-m-d')
                    . " and "
                    . $ramadanEnd->format('Y-m-d')
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

        // 8) Overlap check against DETAILS table (unchanged)
        $existing = $this->MealSubscriptionDetailModel
                        ->where('user_id', session('user_id'))
                        ->where('meal_type_id', $mealTypeId)
                        ->whereIn('status', ['ACTIVE', 'PENDING'])
                        ->whereIn('subscription_date', $dates)
                        ->countAllResults();
        if ($existing > 0) {
            session()->setFlashdata(
                'error',
                'You already have subscription(s) on one or more of those dates.'
            );
            return redirect()->back()->withInput();
        }

        // 9) Insert parent subscription (unchanged)
        $subId = $this->MealSubscriptionModel->insert([
            'user_id'           => $userId,
            'meal_type_id'      => $mealTypeId,
            'cafeteria_id'      => $cafeteriaId,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'status'            => $status,
            'subscription_type' => $userType,
            'remark'            => $this->request->getPost('remark'),
            'created_by'        => session('user_id'),
        ]);

        // 10) Approval steps enqueue (unchanged)
        if ($status === 'PENDING' && $flow) {
            $steps = $this->ApprovalStepModel
                        ->where('flow_id', $flow['id'])
                        ->orderBy('step_order','ASC')
                        ->findAll();

            if (empty($steps)) {
                return redirect()->back()
                                ->with('error','Approval flow defined, but no steps configured.');
            }
            foreach ($steps as $step) {
                $this->MealApprovalModel->insert([
                    'subscription_id' => $subId,
                    'step_id'         => $step['id'],
                    'approver_role'   => $step['approver_role'],
                    'status'          => 'PENDING',
                ]);
            }
        }

        // 11) Insert detail rows
        foreach ($dates as $dstr) {
            $this->MealSubscriptionDetailModel->insert([
                'user_id'           => $userId,
                'subscription_id'   => $subId,
                'subscription_date' => $dstr,
                'status'            => $status,
                'meal_type_id'      => $mealTypeId,
                'cafeteria_id'      => $cafeteriaId,
                'remark'            => $this->request->getPost('remark'),
                'created_by'        => session('user_id'),
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);
        }

        // 12) Final redirect
        $msg = $status === 'PENDING'
            ? 'Subscription pending approval.'
            : 'Subscription active.';

        return redirect()->to('admin/ramadan/ifter-subscription')
                        ->with('success', $msg);
    }


    /** Show my subscription history */
    

    public function unsubscribeSingle($id)
    {
        // Mark the request cancelled
        $this->MealSubscriptionDetailModel->update($id, ['status'=>'CANCELLED','unsubs_by'=>session('user_id')]);
        
        return redirect()->back()
                         ->with('success','Unsubscribed successfully.');
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
        $this->MealSubscriptionDetailModel
            ->whereIn('id', $ids)
            ->set('status', 'CANCELLED')
            ->set('approver_remark', $remark)
            ->update();

        return redirect()->back()->with('success', 'Selected subscriptions unsubscribed.');
    }

}
