<?php namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Intern\CsvSubscriptionImporter;
use App\Libraries\CutoffResolver;
use App\Models\{
    InternSubscriptionModel,
    InternBatchModel,
    ApprovalFlowModel,
    ApprovalStepModel,
    MealApprovalModel,
    CutoffTimeModel,
    PublicHolidayModel,
    MealTypeModel,
    CafeteriaModel
};
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class InternRequisition extends BaseController
{
    use ResponseTrait;

    protected CsvSubscriptionImporter    $importer;
    protected CutoffResolver             $cutoffResolver;
    protected ApprovalFlowModel          $flowModel;
    protected ApprovalStepModel          $stepModel;
    protected MealApprovalModel          $approvalModel;
    protected InternBatchModel $batch;

    protected $internSubscriptionModel, $mealTypeModel, $cafModel;
    private array $employmentTypeCache = [];

    /**
     * CI will call this after instantiating the controller.
     */
    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['form', 'url']);
        $this->internSubscriptionModel = new InternSubscriptionModel();
        $this->importer                = new CsvSubscriptionImporter();
        $this->flowModel               = new ApprovalFlowModel();
        $this->stepModel               = new ApprovalStepModel();
        $this->approvalModel           = new MealApprovalModel();
        $this->batch                   = new InternBatchModel();
        $this->mealTypeModel           = new MealTypeModel();
        $this->cafModel                = new CafeteriaModel();
        $this->cutoffResolver          = new CutoffResolver(
            new CutoffTimeModel(),
            new PublicHolidayModel()
        );
    }

    /** GET /admin/intern-requisitions */
    public function index()
    {
        $subs = $this->internSubscriptionModel
            ->select("
                intern_subscriptions.*,
                cafeterias.name  AS caffname,
                meal_types.name  AS meal_type_name,
                et.name AS employment_type_name,
                ct.cut_off_time  AS cutoff_time,
                ct.lead_days     AS lead_days
            ")
            ->join('cafeterias', 'cafeterias.id = intern_subscriptions.cafeteria_id', 'left')
            ->join('meal_types', 'meal_types.id = intern_subscriptions.meal_type_id', 'left')
            ->join('employment_types et', 'et.id = intern_subscriptions.employment_type_id', 'left')
            ->join(
                'cutoff_times ct',
                'ct.meal_type_id = intern_subscriptions.meal_type_id AND ct.is_active = 1',
                'left'
            )
            ->orderBy('intern_subscriptions.id', 'DESC')
            ->findAll();

        return view('admin/intern/index', ['subs' => $subs]);
    }


    /** GET /admin/intern-requisitions/new */
    public function new()
    {
        ['days' => $days, 'time' => $time, 'lead_days' => $lead] =
            $this->cutoffResolver->getDefault(1);

        $today       = date('Y-m-d');
        $cutoffDate  = date('Y-m-d', strtotime("+{$days} days"));
        $holidays    = $this->cutoffResolver->getHolidays($today, $cutoffDate);

        return view('admin/intern/upload', [
            'cutoffDays'     => $days,
            'cut_off_time'   => $time,
            'lead_days'      => $lead,
            'publicHolidays' => $holidays,
            'mealTypes'      => $this->mealTypeModel->whereIn('id', [1, 2, 3])->findAll(),
            //'cafeterias'     => $this->cafModel->findAll(),
            'validation'     => \Config\Services::validation(),
        ]);
    }

    public function processUpload()
    {
        if (! $this->validateUpload()) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $mealTypeId = (int) $this->request->getPost('meal_type_id');
        $dates = $this->parseMealDates();
        $filePath = $this->handleUploadedFile();
        $rows = $this->importer->parseExcel($filePath);
        $this->deleteFileIfExists($filePath);
        $batchId = $this->createBatch($mealTypeId, $dates, 'ACTIVE');
        $result = $this->insertSubscriptions($batchId, $mealTypeId, $dates, $rows);
        if ($result[0] !== true) {
            return redirect()
                ->to('admin/intern-requisitions')
                ->with('success', $result[1]);
        }

        return redirect()
            ->to('admin/intern-requisitions')
            ->with('success', 'Excel processed and subscriptions created.');
    }

    protected function validateUpload(): bool
    {
        $rules = [
            'meal_type_id' => 'required|integer',
            'meal_dates'   => 'required|string',
            'xlsx_file'    => [
                'rules'  => 'uploaded[xlsx_file]|ext_in[xlsx_file,xlsx,xls]|max_size[xlsx_file,2048]',
                'errors' => [
                    'uploaded' => 'Please choose an Excel file to upload.',
                    'ext_in'   => 'The file must have a .xlsx or .xls extension.',
                    'max_size' => 'The file cannot exceed 2MB.',
                ],
            ],
        ];
        return $this->validate($rules);
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
            return redirect()->back()->withInput()->with('error', "Not a valid file");
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

    protected function createBatch(int $mealTypeId, array $dates, string $status): int
    {
        return $this->batch->insert([
            'uploaded_by'       => session('user_id'),
            'meal_type_id'      => $mealTypeId,
            'start_date'        => reset($dates),
            'end_date'          => end($dates),
            'status'            => $status,
            'subscription_type' => 'INTERN',
            'remark'            => request()->getPost('remark'),
        ]);
    }


    protected function insertSubscriptions(int $batchId, int $mealTypeId, array $dates, array $rows)
    {
        if (empty($dates) || empty($rows)) {
            return [true];
        }

        // Take the first date exactly as selected (array order)
        $firstSelectedDate = reset($dates);

        // Track NEW JOINERs already inserted in this request (by userRefId)
        $newJoinerInserted = [];
        $result = [true];

        $remark = $this->request->getPost('remark');

        foreach ($rows as [$userRefId, $userType, $name, $phone, $cafeteriaName]) {
            $userRefId   = trim((string) $userRefId);
            $userTypeRaw = trim((string) $userType);
            $userType    = strtoupper($userTypeRaw); // INTERN/FTC/OS/NEW JOINER (used by approval flow)
            $name        = trim((string) $name);
            $phone       = trim((string) $phone);
            $cafeteriaId = $this->resolveCafeteriaId($cafeteriaName);

            if (!preg_match('/^\d{11}$/', $phone)) {
                // skip or collect for reporting
                $result = [false, "Invalid phone for {$name}: {$phone}. Must be exactly 11 digits."];
                continue;
            }

            // employment_type_id from master (by name = original, not uppercased)
            $employmentTypeId = $this->resolveEmploymentTypeIdByName($userTypeRaw);
            if ($employmentTypeId === null) {
                return redirect()->back()->with(
                    'error',
                    "Employment Type '{$userTypeRaw}' not found or inactive. Please create/activate it first."
                );
            }

            $isNewJoiner = ($userType === 'NEW JOINER');

            // If NEW JOINER for this user was already inserted once, skip all further dates
            if ($isNewJoiner && isset($newJoinerInserted[$userRefId])) {
                continue;
            }

            // Decide which dates to insert
            $datesToInsert = $isNewJoiner ? [$firstSelectedDate] : $dates;

            // Approval flow & status (same for all dates of this row)
            $flow   = $this->getApprovalFlow($mealTypeId, $userType); // unchanged
            $status = ($flow && $flow['type'] === 'MANUAL') ? 'PENDING' : 'ACTIVE';

            // If pending, make sure steps exist
            if ($status === 'PENDING') {
                $steps = $this->getApprovalSteps($flow['id']);
                if (empty($steps)) {
                    return redirect()->back()->with('error', 'Approval flow defined, but no steps configured. Contact admin.');
                }
            }

            foreach ($datesToInsert as $date) {
                // Usual duplicate check (same userRefId + mealType + date)
                if ($this->subscriptionExists($userRefId, $mealTypeId, $date)) {
                    return redirect()->back()->with('error', "Duplicate for {$userRefId} on {$date}");
                }

                // Insert approval steps per subscription when pending
                if ($status === 'PENDING') {
                    $this->insertApprovalSteps($batchId, $steps);
                }

                $otp = ($status === 'ACTIVE') ? $this->getOtp() : null;

                // Insert the subscription (no subscription_type anymore)
                $this->internSubscriptionModel->insert([
                    'batch_id'            => $batchId,
                    'meal_type_id'        => $mealTypeId,
                    'user_reference_id'   => $userRefId,
                    'intern_name'         => $name,
                    'phone'               => $phone,
                    'subscription_date'   => $date,
                    'employment_type_id'  => $employmentTypeId, // <-- new
                    'cafeteria_id'        => $cafeteriaId,
                    'status'              => $status,
                    'remark'              => $remark,
                    'otp'                 => $otp,
                ]);


                // Mark NEW JOINER as done after the first insert
                if ($isNewJoiner) {
                    $newJoinerInserted[$userRefId] = true;
                    break; // stop looping further dates for this NEW JOINER
                }
            }

            // Send SMS only when ACTIVE (so OTP exists)
            if(isset($otp) && !empty($otp)){
                // Build the compact date message from the whole $dates array
                $niceDates = $this->formatDatesCompact($datesToInsert); // e.g. "Aug 31, Sep 1-3, 2025"
                // Now compose and send the SMS
                $message = "bKash Lunch OTP: {$otp}. Valid once per day on {$niceDates}, at Cafeteria {$cafeteriaName} only. Thank you";
                $this->send_sms($phone, $message);
            }
        }

        return $result;
    }



    protected function getApprovalSteps(int $flowId): array
    {
        return $this->stepModel
            ->where('flow_id', $flowId)
            ->orderBy('step_order', 'ASC')
            ->findAll();
    }

    protected function insertApprovalSteps(int $batchId, array $steps)
    {
        foreach ($steps as $step) {
            $this->approvalModel->insert([
                'subscription_type' => 'INTERN',
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

        $cafeteria = $this->cafModel
            ->select('id')
            ->where('name', $name)
            ->where('is_active', 1)
            ->first();

        return $cafeteria['id'] ?? null;
    }

    protected function subscriptionExists(string $userRefId, int $mealTypeId, string $date): bool
    {
        return $this->internSubscriptionModel
            ->where('user_reference_id', $userRefId)
            ->where('meal_type_id', $mealTypeId)
            ->where('subscription_date', $date)
            ->whereIn('status', ['ACTIVE', 'PENDING'])
            ->countAllResults() > 0;
    }


    /** POST /admin/intern-requisitions/unsubscribe/(:num) */
    public function unsubscribeSingle(int $id)
    {
        $sub = $this->internSubscriptionModel
                    ->find($id)
               ?? throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        $this->internSubscriptionModel
             ->update($id, ['status' => 'CANCELLED']);

        return redirect()->back()
                         ->with('success', 'Unsubscribed successfully.');
    }

    /** GET /admin/intern-requisitions/cutoff-info/(:num) */
    public function getCutOffInfo(int $mealTypeId)
    {
        $info = $this->cutoffResolver->getDefault($mealTypeId);
        return $this->respond([
            'cutoffDays' => $info['days'],
            'leadDays'   => $info['lead_days'],
            'cutOffTime' => $info['time'],
        ]);
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
        $this->internSubscriptionModel
            ->whereIn('id', $ids)
            ->set('status', 'CANCELLED')
            ->set('approver_remark', $remark)
            ->update();

        return redirect()->back()->with('success', 'Selected subscriptions unsubscribed.');
    }

    private function resolveEmploymentTypeIdByName(string $typeName): ?int
    {
        $key = strtoupper(trim($typeName));
        if (isset($this->employmentTypeCache[$key])) {
            return $this->employmentTypeCache[$key];
        }

        $db   = db_connect();
        $row  = $db->table('employment_types')
                ->select('id')
                ->where('is_active', 1)
                ->where('name', $typeName) // MySQL default collations are case-insensitive
                ->get()
                ->getRowArray();

        $id = $row['id'] ?? null;
        $this->employmentTypeCache[$key] = $id;

        return $id;
    }
}
