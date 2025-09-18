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
use CodeIgniter\Exceptions\PageForbiddenException;
use DateTime;
use DateTimeZone;

class Subscription extends BaseController
{
    protected $MealSubscriptionModel;
    protected $ApprovalFlowModel;
    protected $ApprovalStepModel;
    protected $MealApprovalModel;
    protected $CutoffTimeModel;
    protected $PublicHolidayModel;
    protected $MealSubscriptionDetailModel;

    public function __construct()
    {
        $this->MealSubscriptionModel      = new MealSubscriptionModel();
        $this->MealSubscriptionDetailModel= new MealSubscriptionDetailModel();
        $this->ApprovalFlowModel          = new ApprovalFlowModel();
        $this->ApprovalStepModel          = new ApprovalStepModel();
        $this->MealApprovalModel          = new MealApprovalModel();
        $this->CutoffTimeModel            = new CutoffTimeModel();
        $this->PublicHolidayModel         = new PublicHolidayModel();
    }

    /** Show new-subscription form */
    public function new()
    {
        $mealM = new MealTypeModel();
        $cafM  = new CafeteriaModel();

        $cutoffRow = $this->CutoffTimeModel
                    ->select('max_horizon_days, cut_off_time, lead_days')
                    ->where('cutoff_date', null)  // for eid meal cutoff date not null
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
            ->where('meal_type_id', 1)
            ->where('status', 'ACTIVE')
            ->where('subscription_date >=', $today)
            ->where('subscription_date <=', $cutoffDate)
            ->orderBy('subscription_date', 'ASC')
            ->findColumn('subscription_date');

        //$this->dump_data($leadDays);

        return view('employee/subscription/new', [
            'cutoffDays'      => $cutoffDays,
            'cut_off_time'    => $cutOffTime,
            'lead_days'       => $leadDays,
            'registeredDates' => $registeredDates,
            'publicHolidays'  => $publicHolidays,
            'mealTypes'       => $mealM->where('id', 1)->findAll(),
            'cafeterias'      => $cafM->findAll(),
            'validation'      => \Config\Services::validation(),
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

        // Sort ascending just in case
        sort($dates);
        $startDate = $dates[0];
        $endDate   = end($dates);

        // 4) Approval‐flow lookup
        $userType = 'EMPLOYEE';
        $today    = (new \DateTime('now', $tz))->format('Y-m-d');
        $flow = $this->ApprovalFlowModel
                     ->where('user_type',    $userType)
                     ->where('meal_type_id',  $mealTypeId)
                     ->where('effective_date <=', $today)
                     ->where('is_active',     1)
                     ->first();

        $status = ($flow && $flow['type'] === 'MANUAL')
                ? 'PENDING'
                : 'ACTIVE';

        // 5) Cut‐off / lead‐days / horizon
        $cut = $this->CutoffTimeModel
                    ->where('meal_type_id',  $mealTypeId)
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
            $this->PublicHolidayModel->where('is_active',1)->findAll(),
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

        // 8) Overlap check against DETAILS table
        $existing = $this->MealSubscriptionDetailModel
                         ->where('user_id', session('user_id'))
                         ->where('meal_type_id', 1)
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

        // 11) Enqueue approval steps if needed
        if ($status === 'PENDING' && $flow) {
            $steps = $this->ApprovalStepModel
                          ->where('flow_id', $flow['id'])
                          ->orderBy('step_order','ASC')
                          ->findAll();
        }

        if ($flow && $status === 'PENDING' && empty($steps)) {
            return redirect()->back()->with('error', 'Approval flow defined, but no steps configured. Contact admin.');
        }

        
        // 9) Insert parent subscription
        $subId = $this->MealSubscriptionModel->insert([
            'user_id'           => session('user_id'),
            'meal_type_id'      => $mealTypeId,
            'cafeteria_id'      => $cafeteriaId,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'status'            => $status,
            'subscription_type' => $userType,
            'remark'            => $this->request->getPost('remark'),
        ]);

        // 5) If steps exist, insert into meal_approvals
        if ($status === 'PENDING' && !empty($steps)) {
            foreach ($steps as $step) {
                $this->MealApprovalModel->insert([
                    'subscription_id' => $subId,
                    'step_id'         => $step['id'],
                    'approver_role'   => $step['approver_role'],
                    'approver_user_id'=> session('line_manager_id'),
                    'status'          => 'PENDING',
                ]);
            }
        }

        // 10) Insert detail rows
        foreach ($dates as $dstr) {
            $this->MealSubscriptionDetailModel->insert([
                'user_id'           => session('user_id'),
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

        //$this->dump_data([$status, $flow]);
        

        // 12) Final redirect
        $msg = $status === 'PENDING'
             ? 'Subscription pending approval.'
             : 'Subscription active.';

        return redirect()->to('employee/subscription')
                         ->with('success', $msg);
    }

    public function history()
    {
        $subs = $this->MealSubscriptionDetailModel
            ->select('meal_subscription_details.*,
                    meal_types.name AS meal_type_name,
                    cafeterias.name AS caffname,
                    ct.cut_off_time AS cutoff_time,
                    ct.lead_days    AS lead_days')
            ->join('meal_types', 'meal_types.id = meal_subscription_details.meal_type_id', 'left')
            ->join('cafeterias', 'cafeterias.id = meal_subscription_details.cafeteria_id', 'left')
            // join your active cut-off table (adjust table/columns if different)
            ->join('cutoff_times ct', 'ct.meal_type_id = meal_subscription_details.meal_type_id AND ct.is_active = 1', 'left')
            ->where('user_id', session('user_id'))
            ->where('meal_subscription_details.meal_type_id', 1)
            ->orderBy('id', 'DESC')
            ->findAll();

        return view('employee/subscription/subscription_view', [
            'subs' => $subs,
        ]);
    }


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

    /** Cancel (unsubscribe) */
    public function unsubscribe($id)
    {
        $sub = $this->MealSubscriptionModel->find($id);
        if ($sub['user_id'] !== session('user_id')) {
            return redirect()->route('login');
        }
        //$this->dump_data("uns");

        // Mark the request cancelled
        $this->MealSubscriptionModel->update($id, ['status'=>'CANCELLED']);
        $this->MealSubscriptionDetailModel
            ->where('subscription_id', $id)
            ->set('status', 'CANCELLED')
            ->update();

        // mark any pending approvals cancelled
        // $this->MealApprovalModel
        //     ->update(
        //     ['subscription_id' => $id, 'status' => 'PENDING'], // WHERE
        //     ['status'          => 'CANCELLED']                // SET
        //     );

        return redirect()->back()
                         ->with('success','Unsubscribed successfully.');
    }
}
