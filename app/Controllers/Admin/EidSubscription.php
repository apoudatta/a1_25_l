<?php namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MealSubscriptionModel;
use App\Models\MealApprovalModel;
use App\Models\CutoffTimeModel;
use App\Models\PublicHolidayModel;
use App\Models\MealTypeModel;
use App\Models\CafeteriaModel;
use App\Models\OccasionModel;

class EidSubscription extends BaseController
{
    private const MEAL_TYPE_ID = [4,5,6,7];  // eid meal types
    private const EMPLOYEE_ID  = 1;  // EMPLOYEE

    protected $MealSubscriptionModel;
    protected $MealApprovalModel;
    protected $CutoffTimeModel;
    protected $PublicHolidayModel;
    protected $OccasionModel;
    protected $db;

    public function __construct()
    {
        $this->MealSubscriptionModel      = new MealSubscriptionModel();
        $this->MealApprovalModel          = new MealApprovalModel();
        $this->CutoffTimeModel            = new CutoffTimeModel();
        $this->PublicHolidayModel         = new PublicHolidayModel();
        $this->OccasionModel              = new OccasionModel();
        $this->db          = db_connect();
    }

    /**
     * Unified list: my history (me) or all (admin)
     * Routes:
     *   GET eid-subscription                           -> browse('me')
     *   GET eid-subscription/all-eid-subscription-list -> browse('all')
     */
    public function browse(string $scope = 'me')
    {
        $uid          = (int) (session('user_id') ?? 0);
        $mealTypeIds  = is_array(self::MEAL_TYPE_ID) ? self::MEAL_TYPE_ID : [self::MEAL_TYPE_ID];

        $builder = $this->MealSubscriptionModel
            ->select("meal_subscriptions.*,
                    cafeterias.name     AS caffname,
                    meal_types.name     AS meal_type_name,
                    users.employee_id,
                    users.name,
                    ct.cut_off_time     AS cutoff_time,
                    ct.lead_days        AS lead_days", false)
            ->join('cafeterias', 'cafeterias.id = meal_subscriptions.cafeteria_id', 'left')
            ->join('meal_types', 'meal_types.id = meal_subscriptions.meal_type_id', 'left')
            ->join('users',      'users.id = meal_subscriptions.user_id', 'left')
            ->join('cutoff_times ct', 'ct.meal_type_id = meal_subscriptions.meal_type_id AND ct.is_active = 1', 'left')
            ->whereIn('meal_subscriptions.meal_type_id', $mealTypeIds);

        if ($scope === 'me') {
            $builder->where('meal_subscriptions.user_id', $uid);
        }

        $subs = $builder
            ->orderBy('meal_subscriptions.created_at', 'DESC')
            ->orderBy('meal_subscriptions.id', 'DESC')
            ->findAll();

        $view = ($scope === 'all')
            ? 'admin/eid_subscription/all_eid_subscriptions'
            : 'admin/eid_subscription/history';

        return view($view, ['subs' => $subs]);
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

        return view('admin/eid_subscription/new', [
            'mealTypes'       => $mealM->whereIn('id', self::MEAL_TYPE_ID)->findAll(),
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
        $userId      = (int) $this->request->getPost('employee_id');
        $mealTypeIds = $this->request->getPost('meal_type_id');
        $cafeteriaId = (int) $this->request->getPost('cafeteria_id');
        $meal_date   = $this->request->getPost('meal_date'); // single date
        $remark      = $this->request->getPost('remark');
        $status      = 'ACTIVE';
        $userType    = 'ADMIN';

        // 3) Overlap check
        $existing = $this->MealSubscriptionModel
            ->where('user_id', $userId)
            ->whereIn('meal_type_id', $mealTypeIds)
            ->where('subs_date', $meal_date)
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

            // User share from contributions (emp_type_id ALL; cafeteria match or NULL)
            $userTk = $this->resolveUserTk($meal_type_id, 1, $cafeteriaId);
            if ($userTk === null) {
                return redirect()->back()->withInput()->with('error', 'Please configure Contributions for this meal type first.');
            }

            $insertId = $this->MealSubscriptionModel->insert([
                'user_id'      => $userId,
                'meal_type_id' => $meal_type_id,
                'emp_type_id'  => self::EMPLOYEE_ID,
                'cafeteria_id' => $cafeteriaId,
                'subs_date'    => $meal_date,
                'status'       => $status,
                'price'        => round((float) $userTk, 2), // store numeric, not formatted string
                'created_by'   => (int) session('user_id'),
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ], true); // returns insert id (or true if no AI PK, or false on failure)

            // Handle possible boolean return
            if (!$insertId || !is_numeric($insertId)) {
                // optional: throw or handle error/rollback
                // throw new \RuntimeException('Failed to insert subscription.');
            } else {
                $insertId = (int) $insertId;

                if (!empty($remark)) {
                    $this->db->table('remarks')->insert([
                        'subs_id'         => $insertId,
                        'remark'          => $remark,
                        'approver_remark' => null,
                        'created_at'      => date('Y-m-d H:i:s'),
                    ]);
                }
            }

        }

        // 5) Final Redirect
        return redirect()->to('eid-subscription')
                        ->with('success', 'Subscription successful.');
    }


    /** Show my subscription history */
    

    public function unsubscribeSingle($id)
    {
        // Mark the request cancelled
        $this->MealSubscriptionModel->update($id, ['status'=>'CANCELLED','unsubs_by'=>session('user_id')]);
        
        return redirect()->back()
                         ->with('success','Unsubscribed successfully.');
    }

    public function getOccasionDate($eidType)
    {
        $occasion = $this->OccasionModel
                        ->where('tag', $eidType)
                        ->first();

        if ($occasion) {
            return $this->response->setJSON([
                'success' => true,
                'date' => $occasion['occasion_date']
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Occasion not found'
        ]);
    }

    public function unsubscribe_bulk()
    {
        $ids    = array_map('intval', (array) $this->request->getPost('subscription_ids'));
        $remark = trim((string) $this->request->getPost('remark'));

        if (empty($ids)) {
            return redirect()->back()->with('error', 'No subscriptions selected.');
        }

        $now = date('Y-m-d H:i:s');

        $this->db->transStart();

        // 1) Cancel selected subscriptions
        $this->MealSubscriptionModel
            ->whereIn('id', $ids)
            ->set('status', 'CANCELLED')
            ->set('unsubs_by', (int) session('user_id'))
            ->set('updated_at', $now)
            ->update();

        // 2) Always INSERT a new remark row per subs_id (skip if remark empty)
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
            if (! empty($rows)) {
                $this->db->table('remarks')->insertBatch($rows);
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->back()->with('error', 'Unsubscribe failed. Please try again.');
        }

        return redirect()->back()->with('success', 'Selected subscriptions unsubscribed.');
    }



    // --- Helper: user share from meal_contributions ---
    /**
     * Find the best matching user contribution (user share) for a meal type.
     * Priority:
     * 1) exact emp_type + exact cafeteria
     * 2) exact emp_type + cafeteria NULL
     * 3) emp_type = ALL(0) + exact cafeteria
     * 4) emp_type = ALL(0) + cafeteria NULL
     * Returns float or null when not configured.
     */
    private function resolveUserTk(int $mealTypeId, int $empTypeId, ?int $cafeteriaId): ?float
    {
        $b = $this->db->table('meal_contributions')
            ->select('user_tk')
            ->where('is_active', 1)
            ->where('meal_type_id', $mealTypeId);

        // ---- Preference ordering (no backticks) ----
        // exact emp_type first, then ALL(0)
        $b->orderBy("(emp_type_id = {$empTypeId})", 'DESC', false);

        // exact cafeteria first, then NULL
        if ($cafeteriaId === null) {
            $b->orderBy("(cafeteria_id IS NULL)", 'DESC', false);
        } else {
            $cafeteriaId = (int) $cafeteriaId;
            $b->orderBy("(cafeteria_id = {$cafeteriaId})", 'DESC', false);
        }

        // stable last tie-breaker
        $b->orderBy('id', 'DESC');

        // ---- Filters (match exact/NULL combos) ----
        if ($cafeteriaId === null) {
            $b->groupStart()
                ->where('cafeteria_id', null)
            ->groupEnd();
        } else {
            $b->groupStart()
                ->where('cafeteria_id', $cafeteriaId)
                ->orWhere('cafeteria_id', null)
            ->groupEnd();
        }

        $b->groupStart()
            ->where('emp_type_id', $empTypeId)
            ->orWhere('emp_type_id', 0)   // ALL
        ->groupEnd();

        $row = $b->get(1)->getFirstRow('array');
        return $row ? (float) $row['user_tk'] : null;
    }

}
