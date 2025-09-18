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
use App\Models\OccasionModel;
use CodeIgniter\Exceptions\PageForbiddenException;
use DateTime;
use DateTimeZone;

class EidSubscription extends BaseController
{
    protected $MealSubscriptionModel;
    protected $ApprovalFlowModel;
    protected $ApprovalStepModel;
    protected $MealApprovalModel;
    protected $CutoffTimeModel;
    protected $PublicHolidayModel;
    protected $MealSubscriptionDetailModel;
    protected $OccasionModel;

    public function __construct()
    {
        $this->MealSubscriptionModel      = new MealSubscriptionModel();
        $this->MealSubscriptionDetailModel= new MealSubscriptionDetailModel();
        $this->ApprovalFlowModel          = new ApprovalFlowModel();
        $this->ApprovalStepModel          = new ApprovalStepModel();
        $this->MealApprovalModel          = new MealApprovalModel();
        $this->CutoffTimeModel            = new CutoffTimeModel();
        $this->PublicHolidayModel         = new PublicHolidayModel();
        $this->OccasionModel              = new OccasionModel();
    }

    public function history()
    {
        $subs = $this->MealSubscriptionDetailModel
            ->select('meal_subscription_details.*,
                    cafeterias.name AS caffname,
                    meal_types.name AS meal_type_name,
                    ct.cut_off_time AS cutoff_time,
                    ct.lead_days    AS lead_days')
            ->join('cafeterias', 'cafeterias.id = meal_subscription_details.cafeteria_id', 'left')
            ->join('meal_types', 'meal_types.id = meal_subscription_details.meal_type_id', 'left')
            // replace table/columns if your cutoff config table is different
            ->join('cutoff_times ct', 'ct.meal_type_id = meal_subscription_details.meal_type_id AND ct.is_active = 1', 'left')
            ->where('user_id', session('user_id'))
            ->whereIn('meal_subscription_details.meal_type_id', [4,5,6,7]) // EID meal types
            ->orderBy('id','DESC')
            ->findAll();

        return view('employee/eid_subscription/history', [
            'subs' => $subs,
        ]);
    }


    /** Show new-subscription form */
    public function new()
    {
        $cafM  = new CafeteriaModel();
        $mealM = new MealTypeModel();

        $eidDay = $this->OccasionModel
             ->where('occasion_date >', date('Y-m-d'))
             ->orderBy('occasion_date', 'asc')
             ->first();

        if(!$eidDay){
            //session()->setFlashdata('error', 'Eid day not found. contact admin.');
            return redirect()->back()->withInput();
        }

        return view('employee/eid_subscription/new', [
            'mealTypes'       => $mealM->whereIn('id', [4,5,6,7])->findAll(),
            'cafeterias'      => $cafM->findAll(),
            'occasion_date'   => $eidDay['occasion_date'],
            'validation'      => \Config\Services::validation(),
        ]);
    }

    public function store()
    {
        // 1) Validation
        $rules = [
            'meal_date'    => 'required|date',
            'cafeteria_id' => 'required|integer',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()
                            ->withInput()
                            ->with('validation', $this->validator);
        }

        // 2) Inputs
        $mealTypeIds = $this->request->getPost('meal_type_id');
        $cafeteriaId = (int) $this->request->getPost('cafeteria_id');
        $meal_date   = $this->request->getPost('meal_date'); // single date
        $remark      = $this->request->getPost('remark');
        $userId      = session('user_id');
        $status      = 'ACTIVE';
        $userType    = 'EMPLOYEE';

        // 3) Overlap check
        $existing = $this->MealSubscriptionDetailModel
            ->where('user_id', $userId)
            ->whereIn('meal_type_id', $mealTypeIds)
            ->where('subscription_date', $meal_date)
            ->whereIn('status', ['ACTIVE', 'PENDING'])
            ->countAllResults();

        if ($existing > 0) {
            session()->setFlashdata(
                'error',
                'You already have subscription(s) on one or more of those dates.'
            );
            return redirect()->back()->withInput();
        }

        // 4) Insert for each meal type
        foreach ($mealTypeIds as $meal_type_id) {
            $subId = $this->MealSubscriptionModel->insert([
                'user_id'           => $userId,
                'meal_type_id'      => $meal_type_id,
                'cafeteria_id'      => $cafeteriaId,
                'start_date'        => $meal_date,
                'end_date'          => $meal_date,
                'status'            => $status,
                'subscription_type' => $userType,
                'remark'            => $remark,
            ]);

            $this->MealSubscriptionDetailModel->insert([
                'user_id'           => $userId,
                'subscription_id'   => $subId,
                'subscription_date' => $meal_date,
                'status'            => $status,
                'meal_type_id'      => $meal_type_id,
                'cafeteria_id'      => $cafeteriaId,
                'remark'            => $remark,
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);
        }

        // 5) Final Redirect
        return redirect()->to('employee/eid-subscription')
                        ->with('success', 'Subscription successful.');
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
