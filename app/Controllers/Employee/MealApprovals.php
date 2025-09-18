<?php namespace App\Controllers\Employee;

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
        $db = db_connect();
        $today = date('Y-m-d'); // only show today or future meal dates

        // Base approvals for this approver
        $qb = $this->approvalModel
            ->join('users u', 'u.id = meal_approvals.approver_user_id', 'left')
            ->select('meal_approvals.*, u.name AS approver_name');

        if (session('user_type') !== 'SUPER ADMIN') {
            $qb->where('meal_approvals.approver_user_id', session('user_id'));
        }

        $approvals = $qb->orderBy('meal_approvals.created_at', 'DESC')
                        ->asArray()->findAll();

        if (!$approvals) {
            return view('employee/approvals/index', ['approvals' => []]);
        }

        // Collect header IDs by type
        $empIds = $guestIds = $internIds = [];
        foreach ($approvals as $row) {
            $sid = (int) $row['subscription_id'];
            switch ($row['subscription_type']) {
                case 'EMPLOYEE': $empIds[]    = $sid; break; // meal_subscriptions.id
                case 'GUEST':    $guestIds[]  = $sid; break; // guest_batches.id
                case 'INTERN':   $internIds[] = $sid; break; // intern_batches.id
            }
        }
        $empIds    = $empIds    ? array_values(array_unique($empIds))    : [];
        $guestIds  = $guestIds  ? array_values(array_unique($guestIds))  : [];
        $internIds = $internIds ? array_values(array_unique($internIds)) : [];

        // ---------------- EMPLOYEE: ALL future/today detail rows per header -------
        $empDetails = []; // [subscription_id] => [ {detail...}, ... ]
        if ($empIds) {
            $rows = $db->table('meal_subscription_details msd')
                ->select("
                    msd.id               AS detail_id,
                    msd.subscription_id,
                    msd.subscription_date,
                    msd.status           AS d_status,
                    msd.created_at       AS d_created_at,
                    msd.updated_at       AS d_updated_at,
                    mt.name              AS meal_type,
                    c.name               AS cafeteria,
                    u.employee_id        AS emp_id
                ")
                ->join('meal_subscriptions ms', 'ms.id = msd.subscription_id', 'left')
                ->join('users u',               'u.id = ms.user_id', 'left')
                ->join('meal_types mt',         'mt.id = msd.meal_type_id', 'left')
                ->join('cafeterias c',          'c.id = msd.cafeteria_id', 'left')
                ->whereIn('msd.subscription_id', $empIds)
                ->where('msd.subscription_date >=', $today) // <— keep only today/future
                ->orderBy('msd.subscription_id', 'ASC')
                ->orderBy('msd.created_at', 'DESC')
                ->get()->getResultArray();

            foreach ($rows as $r) {
                $sid = (int) $r['subscription_id'];
                $eventAt = ($r['d_status'] === 'CANCELLED')
                    ? ($r['d_updated_at'] ?? $r['d_created_at'])
                    : $r['d_created_at'];

                $empDetails[$sid][] = [
                    'emp_id'     => (string) ($r['emp_id'] ?? ''),
                    'meal_type'  => (string) ($r['meal_type'] ?? ''),
                    'meal_date'  => (string) ($r['subscription_date'] ?? ''),
                    'cafeteria'  => (string) ($r['cafeteria'] ?? ''),
                    'status'     => (string) ($r['d_status'] ?? ''),
                    'event_at'   => (string) $eventAt,
                    'detail_id'  => (int) $r['detail_id'],
                ];
            }
        }

        // ---------------- GUEST: ALL future/today rows per batch ------------------
        $guestDetails = [];
        if ($guestIds) {
            $rows = $db->table('guest_subscriptions gs')
                ->select("
                    gs.id                AS detail_id,
                    gs.batch_id          AS header_id,
                    gs.subscription_date,
                    gs.status            AS d_status,
                    gs.created_at        AS d_created_at,
                    gs.updated_at        AS d_updated_at,
                    mt.name              AS meal_type,
                    c.name               AS cafeteria,
                    u.employee_id        AS emp_id
                ")
                ->join('users u',         'u.id = gs.user_id', 'left')
                ->join('meal_types mt',   'mt.id = gs.meal_type_id', 'left')
                ->join('cafeterias c',    'c.id = gs.cafeteria_id', 'left')
                ->whereIn('gs.batch_id', $guestIds)
                ->where('gs.subscription_date >=', $today) // <—
                ->orderBy('gs.batch_id', 'ASC')
                ->orderBy('gs.created_at', 'DESC')
                ->get()->getResultArray();

            foreach ($rows as $r) {
                $hid = (int) $r['header_id'];
                $eventAt = ($r['d_status'] === 'CANCELLED')
                    ? ($r['d_updated_at'] ?? $r['d_created_at'])
                    : $r['d_created_at'];

                $guestDetails[$hid][] = [
                    'emp_id'     => (string) ($r['emp_id'] ?? ''),
                    'meal_type'  => (string) ($r['meal_type'] ?? ''),
                    'meal_date'  => (string) ($r['subscription_date'] ?? ''),
                    'cafeteria'  => (string) ($r['cafeteria'] ?? ''),
                    'status'     => (string) ($r['d_status'] ?? ''),
                    'event_at'   => (string) $eventAt,
                    'detail_id'  => (int) $r['detail_id'],
                ];
            }
        }

        // ---------------- INTERN: ALL future/today rows per batch -----------------
        $internDetails = [];
        if ($internIds) {
            $rows = $db->table('intern_subscriptions ins')
                ->select("
                    ins.id               AS detail_id,
                    ins.batch_id         AS header_id,
                    ins.subscription_date,
                    ins.status           AS d_status,
                    ins.created_at       AS d_created_at,
                    ins.updated_at       AS d_updated_at,
                    mt.name              AS meal_type,
                    c.name               AS cafeteria,
                    u.employee_id        AS emp_id
                ")
                ->join('users u',         'u.id = ins.user_id', 'left')
                ->join('meal_types mt',   'mt.id = ins.meal_type_id', 'left')
                ->join('cafeterias c',    'c.id = ins.cafeteria_id', 'left')
                ->whereIn('ins.batch_id', $internIds)
                ->where('ins.subscription_date >=', $today) // <—
                ->orderBy('ins.batch_id', 'ASC')
                ->orderBy('ins.created_at', 'DESC')
                ->get()->getResultArray();

            foreach ($rows as $r) {
                $hid = (int) $r['header_id'];
                $eventAt = ($r['d_status'] === 'CANCELLED')
                    ? ($r['d_updated_at'] ?? $r['d_created_at'])
                    : $r['d_created_at'];

                $internDetails[$hid][] = [
                    'emp_id'     => (string) ($r['emp_id'] ?? ''),
                    'meal_type'  => (string) ($r['meal_type'] ?? ''),
                    'meal_date'  => (string) ($r['subscription_date'] ?? ''),
                    'cafeteria'  => (string) ($r['cafeteria'] ?? ''),
                    'status'     => (string) ($r['d_status'] ?? ''),
                    'event_at'   => (string) $eventAt,
                    'detail_id'  => (int) $r['detail_id'],
                ];
            }
        }

        // -------- Expand approvals into one row per (future) detail ---------------
        $rowsForView = [];
        foreach ($approvals as $app) {
            $sid  = (int) $app['subscription_id'];
            $type = $app['subscription_type'];

            $details = [];
            if ($type === 'EMPLOYEE')   $details = $empDetails[$sid]    ?? [];
            elseif ($type === 'GUEST')  $details = $guestDetails[$sid]  ?? [];
            elseif ($type === 'INTERN') $details = $internDetails[$sid] ?? [];

            // ONLY add rows if we have at least one future/today detail
            if (!$details) continue;

            foreach ($details as $d) {
                $rowsForView[] = array_merge($app, [
                    'disp_emp_id'    => $d['emp_id'],
                    'disp_meal_type' => $d['meal_type'],
                    'disp_meal_date' => $d['meal_date'],
                    'disp_cafe'      => $d['cafeteria'],
                    'disp_status'    => $d['status'],
                    'disp_event_at'  => $d['event_at'],
                    'detail_id'      => $d['detail_id'],
                ]);
            }
        }
        return view('employee/approvals/index', ['approvals' => $rowsForView]);
    }

    // In App\Controllers\Employee\MealApprovals
    public function bulkApprove()
    {
        $detailIds = (array) $this->request->getPost('detail_ids');
        $types     = (array) $this->request->getPost('types');
        $remark    = (string) $this->request->getPost('remark', FILTER_UNSAFE_RAW);

        if (count($detailIds) !== count($types) || count($detailIds) === 0) {
            return redirect()->back()->with('error', 'No items selected.');
        }

        //$this->dd([$detailIds, $types]);

        // Build sets per type; dedupe with associative array
        $empIds = $guestIds = $internIds = [];
        for ($i = 0, $n = count($detailIds); $i < $n; $i++) {
            $id   = (int) $detailIds[$i];
            $type = strtoupper((string) $types[$i]);

            if ($id <= 0) continue;
            switch ($type) {
                case 'EMPLOYEE': $empIds[$id]   = true; break; // meal_subscription_details.id
                case 'GUEST':    $guestIds[$id] = true; break; // guest_subscriptions.id
                case 'INTERN':   $internIds[$id]= true; break; // intern_subscriptions.id
                default: /* ignore bad type */ break;
            }
        }
        $empIds    = array_map('intval', array_keys($empIds));
        $guestIds  = array_map('intval', array_keys($guestIds));
        $internIds = array_map('intval', array_keys($internIds));

        if (!$empIds && !$guestIds && !$internIds) {
            return redirect()->back()->with('error', 'No valid items to approve.');
        }

        $db = db_connect();
        $db->transStart();

        // Approve detail rows only if currently PENDING
        if ($empIds) {
            $this->mealSubscriptionDetailModel
                ->whereIn('id', $empIds)
                ->where('status', 'PENDING')
                ->set(['status' => 'ACTIVE', 'approver_remark' => $remark])
                ->update();
        }
        if ($guestIds) {
            $this->guestSubscriptionModel
                ->whereIn('id', $guestIds)
                ->where('status', 'PENDING')
                ->set(['status' => 'ACTIVE', 'approver_remark' => $remark])
                ->update();
        }
        if ($internIds) {
            $this->internSubscriptionModel
                ->whereIn('id', $internIds)
                ->where('status', 'PENDING')
                ->set(['status' => 'ACTIVE', 'approver_remark' => $remark])
                ->update();
        }

        $db->transComplete();

        $total = count($empIds) + count($guestIds) + count($internIds);
        return redirect()->back()->with('success', "Approved: {$total} item(s).");
    }

    public function bulkReject()
    {
        $detailIds = (array) $this->request->getPost('detail_ids');
        $types     = (array) $this->request->getPost('types');
        $remark    = (string) $this->request->getPost('remark', FILTER_UNSAFE_RAW);

        if (count($detailIds) !== count($types) || count($detailIds) === 0) {
            return redirect()->back()->with('error', 'No items selected.');
        }

        $empIds = $guestIds = $internIds = [];
        for ($i = 0, $n = count($detailIds); $i < $n; $i++) {
            $id   = (int) $detailIds[$i];
            $type = strtoupper((string) $types[$i]);

            if ($id <= 0) continue;
            switch ($type) {
                case 'EMPLOYEE': $empIds[$id]   = true; break;
                case 'GUEST':    $guestIds[$id] = true; break;
                case 'INTERN':   $internIds[$id]= true; break;
                default: /* ignore bad type */ break;
            }
        }
        $empIds    = array_map('intval', array_keys($empIds));
        $guestIds  = array_map('intval', array_keys($guestIds));
        $internIds = array_map('intval', array_keys($internIds));

        if (!$empIds && !$guestIds && !$internIds) {
            return redirect()->back()->with('error', 'No valid items to reject.');
        }

        $db = db_connect();
        $db->transStart();

        // Reject (cancel) detail rows only if currently PENDING
        if ($empIds) {
            $this->mealSubscriptionDetailModel
                ->whereIn('id', $empIds)
                ->where('status', 'PENDING')
                ->set(['status' => 'CANCELLED', 'approver_remark' => $remark])
                ->update();
        }
        if ($guestIds) {
            $this->guestSubscriptionModel
                ->whereIn('id', $guestIds)
                ->where('status', 'PENDING')
                ->set(['status' => 'CANCELLED', 'approver_remark' => $remark])
                ->update();
        }
        if ($internIds) {
            $this->internSubscriptionModel
                ->whereIn('id', $internIds)
                ->where('status', 'PENDING')
                ->set(['status' => 'CANCELLED', 'approver_remark' => $remark])
                ->update();
        }

        $db->transComplete();

        $total = count($empIds) + count($guestIds) + count($internIds);
        return redirect()->back()->with('success', "Rejected: {$total} item(s).");
    }





    public function approveSingle($subscriptionType, $subsId)
    {
        $subscriptionType = strtoupper($subscriptionType);
        $remark = $this->request->getPost('remark');
        
        switch ($subscriptionType) {
            case 'INTERN':
                $subscription = $this->internSubscriptionModel->find($subsId);
                if ($subscription) {
                    $this->internSubscriptionModel->update($subsId, ['status' => 'ACTIVE', 'approver_remark' => $remark]);
                    return redirect()->back()->with('success', 'Intern subscription approved.');
                }
                break;

            case 'GUEST':
                $subscription = $this->guestSubscriptionModel->find($subsId);
                if ($subscription) {
                    $this->guestSubscriptionModel->update($subsId, ['status' => 'ACTIVE', 'approver_remark' => $remark]);
                    return redirect()->back()->with('success', 'Guest subscription approved.');
                }
                break;

            case 'EMPLOYEE':
                $subscription = $this->mealSubscriptionDetailModel->find($subsId);
                if ($subscription) {
                    $this->mealSubscriptionDetailModel->update($subsId, ['status' => 'ACTIVE', 'approver_remark' => $remark]);
                    return redirect()->back()->with('success', 'Employee subscription approved.');
                }
                break;

            default:
                return redirect()->back()->with('error', 'Invalid subscription type.');
        }

        return redirect()->back()->with('error', 'Subscription not found.');
    }

    public function rejectSingle($subscriptionType, $subsId)
    {
        $subscriptionType = strtoupper($subscriptionType);
        $remark = $this->request->getPost('remark');

        switch ($subscriptionType) {
            case 'INTERN':
                $subscription = $this->internSubscriptionModel->find($subsId);
                if ($subscription) {
                    $this->internSubscriptionModel->update($subsId, ['status' => 'CANCELLED', 'approver_remark' => $remark]);
                    return redirect()->back()->with('success', 'Intern subscription rejected.');
                }
                break;

            case 'GUEST':
                $subscription = $this->guestSubscriptionModel->find($subsId);
                if ($subscription) {
                    $this->guestSubscriptionModel->update($subsId, ['status' => 'CANCELLED', 'approver_remark' => $remark]);
                    return redirect()->back()->with('success', 'Guest subscription rejected.');
                }
                break;

            case 'EMPLOYEE':
                $subscription = $this->mealSubscriptionDetailModel->find($subsId);
                if ($subscription) {
                    $this->mealSubscriptionDetailModel->update($subsId, ['status' => 'CANCELLED', 'approver_remark' => $remark]);
                    return redirect()->back()->with('success', 'Employee subscription rejected.');
                }
                break;

            default:
                return redirect()->back()->with('error', 'Invalid subscription type.');
        }

        return redirect()->back()->with('error', 'Subscription not found.');
    }


}
