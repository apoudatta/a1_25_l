<?php namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MealApprovalModel;

use App\Models\MealSubscriptionModel;
use App\Models\MealSubscriptionDetailModel;

use App\Models\InternBatchModel;
use App\Models\InternSubscriptionModel;

use App\Models\GuestBatchModel;
use App\Models\GuestSubscriptionModel;

use App\Models\UserModel;

class MealApprovals extends BaseController
{
    protected MealApprovalModel       $approvalModel;
    protected MealSubscriptionModel   $subscriptionModel;
    protected MealSubscriptionDetailModel  $mealSubscriptionDetailModel;
    protected InternBatchModel $internBatchModel;
    protected InternSubscriptionModel $internSubscriptionModel;
    protected GuestBatchModel  $guestBatchModel;
    protected GuestSubscriptionModel  $guestSubscriptionModel;
    protected UserModel               $userModel;

    public function __construct()
    {
        $this->approvalModel           = new MealApprovalModel();
        $this->subscriptionModel       = new MealSubscriptionModel();
        $this->mealSubscriptionDetailModel  = new MealSubscriptionDetailModel();
        $this->internBatchModel             = new InternBatchModel();
        $this->internSubscriptionModel      = new InternSubscriptionModel();
        $this->guestBatchModel              = new GuestBatchModel();
        $this->guestSubscriptionModel  = new GuestSubscriptionModel();
        $this->userModel               = new UserModel();
    }

    /** GET /admin/approvals */
    public function index()
    {
        $db    = db_connect();
        $uid   = (int) (session('user_id') ?? 0);
        $today = date('Y-m-d');

        // Collect role IDs from session if available (supports single or array)
        $roleIds = [];
        if (is_array(session('role_ids') ?? null)) {
            $roleIds = array_map('intval', (array) session('role_ids'));
        } elseif (session('role_id')) {
            $roleIds = [ (int) session('role_id') ];
        }

        $builder = $db->table('meal_approvals ma')
            ->select("
                ma.id                 AS approval_id,
                ma.subs_id,
                ma.approver_user_id,
                ma.approver_role,
                ma.approval_status,
                ma.created_at         AS approval_created_at,

                ms.user_id,
                ms.emp_type_id,
                ms.meal_type_id,
                ms.cafeteria_id,
                ms.subs_date,
                ms.status             AS subs_status,
                ms.price,
                ms.created_at,
                ms.updated_at,

                u.name                AS employee_name,
                u.employee_id,

                mt.name               AS meal_type_name,
                et.name               AS emp_type_name,
                c.name                AS cafeteria_name,

                ct.cut_off_time       AS cutoff_time,
                ct.lead_days          AS lead_days,

                mr.ref_name,
                mr.ref_phone
            ", false)
            ->join('meal_subscriptions ms', 'ms.id = ma.subs_id', 'left')
            ->join('users u',               'u.id = ms.user_id', 'left')
            ->join('meal_types mt',         'mt.id = ms.meal_type_id', 'left')
            ->join('cafeterias c',          'c.id = ms.cafeteria_id', 'left')
            ->join('employment_types et',   'et.id = ms.emp_type_id', 'left')
            ->join('cutoff_times ct',       'ct.meal_type_id = ms.meal_type_id AND ct.is_active = 1', 'left')
            // meal_reference is optional (guest/intern metadata)
            ->join('meal_reference mr',     'mr.subs_id = ms.id', 'left')
            ->where('ma.approval_status', 'PENDING')
            ->where('ms.subs_date >=', $today);

        // Access control
        if ($uid !== 1) { // 1 = SUPER ADMIN
            if (!empty($roleIds)) {
                $builder->groupStart()
                    ->where('ma.approver_user_id', $uid)
                    ->orWhereIn('ma.approver_role', $roleIds)
                ->groupEnd();
            } else {
                $builder->where('ma.approver_user_id', $uid);
            }
        }

        // (Optional) Hide cancelled subscriptions from the approval queue
        // $builder->where('ms.status !=', 'CANCELLED');

        $approvals = $builder
            ->orderBy('ms.subs_date', 'ASC')
            ->orderBy('ma.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return view('admin/approvals/index', ['approvals' => $approvals]);
    }


    /**
     * POST /admin/approvals/bulk-act/{approve|reject}
     * Body:
     *   - detail_ids[] : array of meal_subscriptions.id (selected rows)
     *   - types[]      : legacy; ignored (kept for compatibility)
     *   - remark       : optional approver remark (stored in remarks.approver_remark)
     */
    public function bulkAct(string $action)
    {
        $db     = db_connect();
        $uid    = (int) (session('user_id') ?? 0);
        $now    = date('Y-m-d H:i:s');
        $remark = trim((string) $this->request->getPost('remark', FILTER_UNSAFE_RAW));

        $action = strtolower($action);
        if (!in_array($action, ['approve', 'reject'], true)) {
            return redirect()->back()->with('error', 'Invalid action.');
        }

        // Collect IDs (keep legacy param names)
        $subsIds = array_map('intval', (array) $this->request->getPost('detail_ids'));
        $subsIds = array_values(array_unique(array_filter($subsIds, fn ($v) => $v > 0)));
        if (empty($subsIds)) {
            return redirect()->back()->with('error', 'No items selected.');
        }

        // Roles from session (supports single or array)
        $roleIds = [];
        if (is_array(session('role_ids') ?? null)) {
            $roleIds = array_map('intval', (array) session('role_ids'));
        } elseif (session('role_id')) {
            $roleIds = [ (int) session('role_id') ];
        }

        // Fetch approvable PENDING approvals for these subs, respecting access
        $ab = $db->table('meal_approvals ma')
            ->select('ma.id AS approval_id, ma.subs_id')
            ->join('meal_subscriptions ms', 'ms.id = ma.subs_id', 'left')
            ->whereIn('ma.subs_id', $subsIds)
            ->where('ma.approval_status', 'PENDING');
            // Optional: block acting on past meals
            // ->where('ms.subs_date >=', date('Y-m-d'));

        if ($uid !== 1) { // 1 = SUPER ADMIN
            if (!empty($roleIds)) {
                $ab->groupStart()
                    ->where('ma.approver_user_id', $uid)
                    ->orWhereIn('ma.approver_role', $roleIds)
                ->groupEnd();
            } else {
                $ab->where('ma.approver_user_id', $uid);
            }
        }

        $rows = $ab->get()->getResultArray();
        if (empty($rows)) {
            $msg = ($action === 'approve') ? 'No pending items available for approval.' : 'No pending items available for rejection.';
            return redirect()->back()->with('error', $msg);
        }

        $approvalIds = array_map('intval', array_column($rows, 'approval_id'));
        $targetIds   = array_map('intval', array_column($rows, 'subs_id'));

        // Decide target statuses/messages
        $newApprovalStatus = ($action === 'approve') ? 'APPROVED' : 'REJECTED';
        $newSubsStatus     = ($action === 'approve') ? 'ACTIVE'   : 'CANCELLED';
        $successMsg        = ($action === 'approve')
            ? 'Approved %d of %d selected item(s).'
            : 'Rejected %d of %d selected item(s).';

        // TX
        $db->transStart();

        // 1) Update approvals
        $db->table('meal_approvals')
            ->whereIn('id', $approvalIds)
            ->set('approval_status', $newApprovalStatus)
            ->set('approved_by', $uid)
            ->set('approved_at', $now)
            ->set('updated_at', $now)
            ->update();

        // 2) Update subscriptions
        $db->table('meal_subscriptions')
            ->whereIn('id', $targetIds)
            ->set('status', $newSubsStatus)
            ->set('updated_at', $now)
            ->update();

        // 3) Optional: add a new approver remark per subs_id
        if ($remark !== '') {
            $batch = [];
            foreach ($targetIds as $sid) {
                $batch[] = [
                    'subs_id'         => $sid,
                    'remark'          => null,
                    'approver_remark' => $remark,
                    'created_at'      => $now,
                ];
            }
            if ($batch) {
                $db->table('remarks')->insertBatch($batch);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            $failMsg = ($action === 'approve') ? 'Bulk approval failed. Please try again.' : 'Bulk rejection failed. Please try again.';
            return redirect()->back()->with('error', $failMsg);
        }

        return redirect()->back()->with('success', sprintf($successMsg, count($targetIds), count($subsIds)));
    }



    /**
     * POST /admin/approvals/act/{approve|reject}/{subsId}
     */
    public function act(string $action, int $subsId)
    {
        $db     = db_connect();
        $uid    = (int) (session('user_id') ?? 0);
        $now    = date('Y-m-d H:i:s');
        $remark = trim((string) $this->request->getPost('remark'));

        $action  = strtolower($action);
        if (!in_array($action, ['approve','reject'], true)) {
            return redirect()->back()->with('error', 'Invalid action.');
        }

        // Collect role IDs from session (supports single or array)
        $roleIds = [];
        if (is_array(session('role_ids') ?? null)) {
            $roleIds = array_map('intval', (array) session('role_ids'));
        } elseif (session('role_id')) {
            $roleIds = [ (int) session('role_id') ];
        }

        // Find a PENDING approval for this subscription that this approver can action
        $ab = $db->table('meal_approvals')
            ->where('subs_id', $subsId)
            ->where('approval_status', 'PENDING');

        // Access control: super admin (id=1) sees all; others by user or role
        if ($uid !== 1) {
            if (!empty($roleIds)) {
                $ab->groupStart()
                    ->where('approver_user_id', $uid)
                    ->orWhereIn('approver_role', $roleIds)
                ->groupEnd();
            } else {
                $ab->where('approver_user_id', $uid);
            }
        }

        $approval = $ab->get(1)->getRowArray();
        if (!$approval) {
            return redirect()->back()->with('error', 'Approval not found, already processed, or not assigned to you.');
        }

        // Decide statuses based on action
        $newApprovalStatus = ($action === 'approve') ? 'APPROVED'  : 'REJECTED';
        $newSubsStatus     = ($action === 'approve') ? 'ACTIVE'    : 'CANCELLED';
        $successMsg        = ($action === 'approve') ? 'Subscription approved.' : 'Subscription rejected.';

        // Do it in a TX
        $db->transStart();

        // 1) Update approval row
        $db->table('meal_approvals')
            ->where('id', (int) $approval['id'])
            ->update([
                'approval_status' => $newApprovalStatus,
                'approved_by'     => $uid,
                'approved_at'     => $now,
                'updated_at'      => $now,
            ]);

        // 2) Update subscription status
        $db->table('meal_subscriptions')
            ->where('id', $subsId)
            ->update([
                'status'     => $newSubsStatus,
                'updated_at' => $now,
            ]);

        // 3) Optional: store approver remark as a new row
        if ($remark !== '') {
            $db->table('remarks')->insert([
                'subs_id'         => $subsId,
                'remark'          => null,
                'approver_remark' => $remark,
                'created_at'      => $now,
            ]);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Action failed. Please try again.');
        }

        return redirect()->back()->with('success', $successMsg);
    }




}
