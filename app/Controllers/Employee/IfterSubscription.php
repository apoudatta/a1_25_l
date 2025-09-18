<?php namespace App\Controllers\Employee;

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
                    cafeterias.name AS caffname,
                    ct.cut_off_time AS cutoff_time,
                    ct.lead_days    AS lead_days')
            ->join('cafeterias', 'cafeterias.id = meal_subscription_details.cafeteria_id', 'left')
            ->join('cutoff_times ct', 'ct.meal_type_id = meal_subscription_details.meal_type_id AND ct.is_active = 1', 'left')
            ->where('user_id', session('user_id'))
            ->where('meal_subscription_details.meal_type_id', 2) // Ifter
            ->orderBy('id','DESC')
            ->findAll();

        return view('employee/ifter_subscription/history', [
            'subs' => $subs,
        ]);
    }


    /** Show new-subscription form */
    public function new()
    {
        $mealM = new MealTypeModel();
        $cafM  = new CafeteriaModel();

        // 1) Ramadan window
        $ramadanConfig = model(RamadanConfigModel::class)
                          ->orderBy('id', 'DESC')
                          ->first();
        if (empty($ramadanConfig)) {
            return redirect()->back()
                             ->with('error', 'Ramadan configuration not found');
        }
        $ramadanStart = $ramadanConfig['start_date']; // e.g. '2025-08-04'
        $ramadanEnd   = $ramadanConfig['end_date'];   // e.g. '2025-08-23'

        // 2) Cut-off time & lead-days
        $cutoffRow = model(CutoffTimeModel::class)
                    ->select('cut_off_time, lead_days')
                    ->where('cutoff_date', null)
                    ->where('is_active',    1)
                    ->where('meal_type_id',  2)  // Iftar
                    ->first();
        if (empty($cutoffRow)) {
            $cutOffTime = '22:00:00';
            $leadDays   = 1;
        } else {
            $cutOffTime = $cutoffRow['cut_off_time'];
            $leadDays   = $cutoffRow['lead_days'];
        }

        // 3) Public holidays during Ramadan
        $publicHolidays = model(PublicHolidayModel::class)
            ->select('holiday_date')
            ->where('is_active', 1)
            ->where('holiday_date >=', $ramadanStart)
            ->where('holiday_date <=', $ramadanEnd)
            ->orderBy('holiday_date','ASC')
            ->findColumn('holiday_date');

        // 4) Already-registered dates during Ramadan
        $registeredDates = model(MealSubscriptionDetailModel::class)
            ->select('subscription_date')
            ->where('user_id',        session('user_id'))
            ->where('meal_type_id',   2)
            ->whereIn('status',      ['ACTIVE','PENDING'])
            ->where('subscription_date >=', $ramadanStart)
            ->where('subscription_date <=', $ramadanEnd)
            ->orderBy('subscription_date','ASC')
            ->findColumn('subscription_date');

        return view('employee/ifter_subscription/new', [
            'reg_start_date'  => $ramadanStart,
            'reg_end_date'    => $ramadanEnd,
            'cut_off_time'    => $cutOffTime,
            'lead_days'       => $leadDays,
            'registeredDates' => $registeredDates,
            'publicHolidays'  => $publicHolidays,
            'mealTypes'       => $mealM->where('id',2)->findAll(),
            'cafeterias'      => $cafM->findAll(),
            'validation'      => \Config\Services::validation(),
        ]);
    }

    public function store()
    {
        // 1) Basic validation
        $rules = [
            'meal_type_id' => 'required|integer',
            'meal_dates'   => 'required|string',   // CSV of dates "d/m/Y"
            'cafeteria_id' => 'required|integer',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()
                             ->withInput()
                             ->with('validation', $this->validator);
        }

        // 2) Pull inputs
        $userId      = session('user_id');
        $mealTypeId  = (int) $this->request->getPost('meal_type_id');
        $cafeteriaId = (int) $this->request->getPost('cafeteria_id');
        $rawDates    = explode(',', $this->request->getPost('meal_dates'));

        // 3) Ramadan window (again)
        $tz = new \DateTimeZone('Asia/Dhaka');
        $ramadanConfig = model(RamadanConfigModel::class)
                          ->orderBy('id','DESC')
                          ->first();
        $ramadanStart = \DateTime::createFromFormat('Y-m-d', $ramadanConfig['start_date'], $tz)
                                 ->setTime(0,0,0);
        $ramadanEnd   = \DateTime::createFromFormat('Y-m-d', $ramadanConfig['end_date'],   $tz)
                                 ->setTime(23,59,59);

        // 4) Parse & normalize dates → ['YYYY-MM-DD', …]
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

        // 5) Approval‐flow lookup
        $userType = 'EMPLOYEE';
        $today    = (new \DateTime('now', $tz))->format('Y-m-d');
        $flow     = model(ApprovalFlowModel::class)
                    ->where('user_type',   $userType)
                    ->where('meal_type_id', $mealTypeId)
                    ->where('effective_date <=', $today)
                    ->where('is_active',    1)
                    ->first();
        $status = ($flow && $flow['type']==='MANUAL')
                ? 'PENDING'
                : 'ACTIVE';

        // 6) Cut‐off time & lead‐days
        $cut = model(CutoffTimeModel::class)
               ->where('meal_type_id', $mealTypeId)
               ->where('cutoff_date', null)
               ->where('is_active',   1)
               ->first();
        if (! $cut) {
            session()->setFlashdata('error','Cut-off settings not found');
            return redirect()->back()->withInput();
        }
        $now          = new \DateTime('now', $tz);
        [$h,$m,$s]    = explode(':',$cut['cut_off_time']);
        $cutOffMoment = (clone $now)->setTime((int)$h,(int)$m,(int)$s);
        $daysToAdd    = (int)$cut['lead_days'];
        if ($now > $cutOffMoment) {
            $daysToAdd++;
        }
        $earliest = (clone $now)->modify("+{$daysToAdd} days")->setTime(0,0,0);
        $latest   = $ramadanEnd;  // **limit by Ramadan end**, not horizon

        // 7) Holiday list
        $holidays = array_column(
            model(PublicHolidayModel::class)
                ->where('is_active',1)
                ->findAll(),
            'holiday_date'
        );

        // 8) Per-date validations: Ramadan window, cut-off window, holiday, weekly holiday
        foreach ($dates as $dstr) {
            $d = \DateTime::createFromFormat('Y-m-d', $dstr, $tz)
                 ->setTime(0,0,0);

            // 8a) Ramadan window
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

            // 8b) Cut-off per-day
            if ($d < $earliest) {
                session()->setFlashdata(
                    'error',
                    "Too late to register {$dstr}; earliest allowed is "
                    . $earliest->format('Y-m-d')
                );
                return redirect()->back()->withInput();
            }

            // 8c) Public holiday
            if (in_array($dstr, $holidays, true)) {
                session()->setFlashdata('error', "Cannot subscribe on public holiday: {$dstr}");
                return redirect()->back()->withInput();
            }

            // 8d) Weekly holiday (Fri=5, Sat=6)
            if (in_array((int)$d->format('w'), [5,6], true)) {
                session()->setFlashdata('error', "Cannot subscribe on weekly holiday: {$dstr}");
                return redirect()->back()->withInput();
            }
        }

        // 9) Overlap check
        $existing = model(MealSubscriptionDetailModel::class)
                    ->where('user_id',       $userId)
                    ->where('meal_type_id',  $mealTypeId)
                    ->whereIn('status',     ['ACTIVE','PENDING'])
                    ->whereIn('subscription_date', $dates)
                    ->countAllResults();
        if ($existing > 0) {
            session()->setFlashdata(
                'error',
                'You already have subscription(s) on one or more of those dates.'
            );
            return redirect()->back()->withInput();
        }

        // 10) Insert parent subscription
        $subId = model(MealSubscriptionModel::class)
                 ->insert([
                     'user_id'           => $userId,
                     'meal_type_id'      => $mealTypeId,
                     'cafeteria_id'      => $cafeteriaId,
                     'start_date'        => $startDate,
                     'end_date'          => $endDate,
                     'status'            => $status,
                     'subscription_type' => $userType,
                     'remark'            => $this->request->getPost('remark'),
                 ]);

        // 11) Approval steps (if any)
        if ($status==='PENDING' && $flow) {
            $steps = model(ApprovalStepModel::class)
                     ->where('flow_id', $flow['id'])
                     ->orderBy('step_order','ASC')
                     ->findAll();
            if (empty($steps)) {
                return redirect()->back()
                                 ->with('error','Approval flow defined, but no steps configured');
            }

            foreach ($steps as $step) {
                model(MealApprovalModel::class)->insert([
                    'subscription_id' => $subId,
                    'step_id'         => $step['id'],
                    'approver_role'   => $step['approver_role'],
                    'approver_user_id'=> session('line_manager_id'),
                    'status'          => 'PENDING',
                ]);
            }
        }

        // 12) Detail rows
        foreach ($dates as $dstr) {
            model(MealSubscriptionDetailModel::class)->insert([
                'user_id'           => $userId,
                'subscription_id'   => $subId,
                'subscription_date' => $dstr,
                'status'            => $status,
                'meal_type_id'      => $mealTypeId,
                'cafeteria_id'      => $cafeteriaId,
                'remark'            => $this->request->getPost('remark'),
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);
        }

        // 13) Final redirect
        $msg = $status==='PENDING'
             ? 'Subscription pending approval.'
             : 'Subscription active.';

        return redirect()->to('employee/ifter-subscription')
                         ->with('success', $msg);
    }

    /** Show my subscription history */
    

    public function unsubscribeSingle($id)
    {
        $sub = $this->MealSubscriptionDetailModel->find($id);
        
        if ($sub['user_id'] !== session('user_id')) {
            return redirect()->route('login');
        }

        // Mark the request cancelled
        $this->MealSubscriptionDetailModel->update($id, ['status'=>'CANCELLED']);
        
        return redirect()->back()
                         ->with('success','Unsubscribed successfully.');
    }

}
