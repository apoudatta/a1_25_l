<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\MealTokenModel;
use App\Models\MealSubscriptionModel;
use App\Models\CafeteriaModel;
use App\Models\MealCardModel;
use CodeIgniter\Database\ConnectionInterface;

// Simple app-level status codes (same file, same namespace)
enum ApiCode: string
{
    case OK                        = 'OK';
    case INVALID_JSON              = 'INVALID_JSON';
    case MISSING_FIELDS            = 'MISSING_FIELDS';

    // SaveNewCard
    case EMPLOYEE_NOT_FOUND        = 'EMPLOYEE_NOT_FOUND';
    case EMPLOYEE_ALREADY_HAS_CARD = 'Already_Assigned';
    case CARD_CODE_IN_USE          = 'CARD_CODE_IN_USE';
    case CARD_ALREADY_ASSIGNED     = 'CARD_ALREADY_ASSIGNED';
    case SERVER_ERROR              = 'SERVER_ERROR';

    // GetEmpMeal
    case CAFETERIA_REQUIRED        = 'CAFETERIA_REQUIRED';
    case INVALID_MEAL_TYPE         = 'INVALID_MEAL_TYPE';
    case OTP_INVALID               = 'OTP_INVALID';
    case OTP_WRONG_CAFETERIA       = 'OTP_WRONG_CAFETERIA';
    case MEAL_ALREADY_CONSUMED     = 'MEAL_ALREADY_CONSUMED';
    case TOKEN_EXISTS_REDEEMED     = 'TOKEN_EXISTS_REDEEMED';
    case ACTIVE_CARD_NOT_FOUND     = 'ACTIVE_CARD_NOT_FOUND';
    case NO_SCHEDULED_MEAL         = 'NO_SCHEDULED_MEAL';
    case DIFFERENT_CAFETERIA       = 'DIFFERENT_CAFETERIA';
    case RAMADAN_YEAR_REQUIRED     = 'RAMADAN_YEAR_REQUIRED';
    case RAMADAN_NOT_FOUND         = 'RAMADAN_NOT_FOUND';
}


class LmsApi extends BaseController
{
    protected UserModel $users;
    protected MealTokenModel $tokens;
    protected MealSubscriptionModel $subs;
    protected CafeteriaModel $cafes;
    protected MealCardModel $cards;

    public function __construct()
    {
        $this->users = new UserModel();
        $this->tokens = new MealTokenModel();
        $this->subs = new MealSubscriptionModel();
        $this->cafes = new CafeteriaModel();
        $this->cards = new MealCardModel();
    }
    

    private function json(
        bool $ok,
        $data = null,
        string $message = '',
        ApiCode|string $code = ApiCode::OK,
        int $httpStatus = 200
    ) {
        // allow passing raw string codes too
        $code = $code instanceof ApiCode ? $code->value : (string)$code;
    
        return $this->response
            ->setStatusCode($httpStatus) // keep 200 if you prefer; change per case if needed
            ->setJSON([
                'success' => $ok,
                'code'    => $code,
                'message' => $message,
                'data'    => $data,
            ]);
    }
    

    /**
     * 1) SaveNewCard
     * Input: card_code, emp_id, overwrite
     * Output: True/False
     * Rule: if employee already has a card -> false
     */
    public function saveNewCard()
    {
        $in = $this->readInput();

        if (isset($in['__json_error__'])) {
            return $this->json(false, null, $in['__json_error__'], ApiCode::INVALID_JSON);
        }

        $cardCode  = trim((string)($in['card_code'] ?? ''));
        $empId     = trim((string)($in['emp_id']    ?? ''));
        
        // accept both isConfirm and overwrite
        $overwriteRaw = $in['isConfirm'] ?? $in['overwrite'] ?? false;
        $overwrite = filter_var($overwriteRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $overwrite = $overwrite ?? false;  // if not a recognizable boolean, treat as false


        if ($cardCode === '' || $empId === '') {
            return $this->json(false, null, 'Missing card_code / emp_id', ApiCode::MISSING_FIELDS);
        }

        $user = $this->users->where('employee_id', $empId)->where('status', 'ACTIVE')->first();
        if (! $user) {
            return $this->json(false, null, 'Employee not found or inactive', ApiCode::EMPLOYEE_NOT_FOUND);
        }

        // ---------- NEW CARD (no overwrite) ----------
        if ($overwrite === false) {
            $existingForUser = $this->cards->where('user_id', $user['id'])->first();
            if ($existingForUser) {
                return $this->json(false, null,
                    'This Employee already has a card assigned. Do you want to proceed with overriding?',
                    ApiCode::EMPLOYEE_ALREADY_HAS_CARD
                );
            }

            $dup = $this->cards->where('card_code', $cardCode)->first();
            if ($dup) {
                return $this->json(false, null,
                    'This Employee already has a card assigned. Do you want to proceed with overriding?',
                    ApiCode::EMPLOYEE_ALREADY_HAS_CARD
                );
            }

            $saved = $this->cards->insert([
                'user_id'     => $user['id'],
                'employee_id' => $empId,
                'card_code'   => $cardCode,
                'status'      => 'ACTIVE',
                'created_at'  => date('Y-m-d H:i:s'),
            ], true);

            if (! $saved) {
                return $this->json(false, null, 'Unable to save card', ApiCode::SERVER_ERROR);
            }
            return $this->json(true, ['id' => (int)$saved, 'mode' => 'insert'], 'New Card Save Successfull!', ApiCode::OK);
        }

        // ---------- OVERWRITE EXISTING CARD (by user_id) ----------
        $db = \Config\Database::connect();
        $db->transStart();

        // 1) Prevent using a card_code that belongs to another user
        $conflict = $this->cards->where('card_code', $cardCode)->first();
        if ($conflict && (int)$conflict['user_id'] !== (int)$user['id']) {
            $db->transComplete();
            return $this->json(false, null, 'card_code already assigned to another user', ApiCode::CARD_CODE_IN_USE);
        }

        // 2) Fetch current card (if any) for this user
        $existingForUser = $this->cards->where('user_id', $user['id'])->first();

        // 2a) No existing row: insert one (since caller explicitly wants overwrite)
        if (! $existingForUser) {
            $newId = $this->cards->insert([
                'user_id'     => $user['id'],
                'employee_id' => $empId,
                'card_code'   => $cardCode,
                'status'      => 'ACTIVE',
                'created_at'  => date('Y-m-d H:i:s'),
            ], true);

            if (! $newId) {
                $db->transComplete();
                return $this->json(false, null, 'Unable to save card', ApiCode::SERVER_ERROR);
            }

            $db->transComplete();
            return $this->json(true, ['id' => (int)$newId, 'mode' => 'insert'], 'New Card Save Successfull!', ApiCode::OK);
        }

        // 2b) Exists and same card_code → idempotent: just ensure ACTIVE + touch updated_at
        if ((string)$existingForUser['card_code'] === $cardCode) {
            $this->cards->update($existingForUser['id'], [
                'status'     => 'ACTIVE',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $db->transComplete();
            return $this->json(true, [
                'id'   => (int)$existingForUser['id'],
                'mode' => 'noop'
            ], 'Card Override is Successfull!', ApiCode::OK);
        }

        // 2c) Exists and different card_code → update that row
        $prevCode = (string)$existingForUser['card_code'];
        $ok = $this->cards->update($existingForUser['id'], [
            'card_code'  => $cardCode,
            'status'     => 'ACTIVE',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (! $ok) {
            $db->transComplete();
            return $this->json(false, null, 'Unable to overwrite card', ApiCode::SERVER_ERROR);
        }

        $db->transComplete();
        return $this->json(true, [
            'id'                 => (int)$existingForUser['id'],
            'mode'               => 'update',
            'previous_card_code' => $prevCode
        ], 'Card Override is Successfull!', ApiCode::OK);
    }


    public function getEmpMeal()
    {
        $in = $this->readInput();
        if (isset($in['__json_error__'])) {
            return $this->json(false, null, $in['__json_error__'], ApiCode::INVALID_JSON);
        }

        $otp       = trim((string)($in['otp']       ?? ''));
        $cardCode  = trim((string)($in['card_code'] ?? ''));
        $empId     = trim((string)($in['emp_id']    ?? ''));
        $cafeteriaIdInput = (int)($in['cafeteria_id'] ?? $this->request->getGet('cafeteria_id') ?? 0); // REQUIRED
        $mealType  = strtolower(trim((string)($in['meal_type'] ?? ''))); // "lunch"/"ifter"/"sehri"
        $today     = date('Y-m-d');

        if ($cafeteriaIdInput <= 0) {
            return $this->json(false, null, 'cafeteria_id is required', ApiCode::CAFETERIA_REQUIRED);
        }

        // Map meal_type text -> id (1=lunch, 2-ifter, 3-sehri)
        $mealTypeIdInput = null;
        if ($mealType !== '') {
            if     ($mealType === 'lunch') $mealTypeIdInput = 1;
            elseif ($mealType === 'ifter') $mealTypeIdInput = 2;
            elseif ($mealType === 'sehri') $mealTypeIdInput = 3;
            elseif ($mealType === 'Eid Morning Snacks') $mealTypeIdInput = 4;
            elseif ($mealType === 'Eid Lunch') $mealTypeIdInput = 5;
            elseif ($mealType === 'Eid Evening Snacks') $mealTypeIdInput = 6;
            elseif ($mealType === 'Eid Dinner') $mealTypeIdInput = 7;
            else return $this->json(false, null, 'Invalid meal_type. Use lunch/ifter/sehri/Eid Morning Snacks/Eid Lunch/Eid Evening Snacks/Eid Dinner', ApiCode::INVALID_MEAL_TYPE);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // ------------------- CASE A: OTP path (guest -> intern) -------------------
        if ($otp !== '') {
            // fetch a row by otp (optionally filtered by meal_type and today)
            $fetchByOtp = function(string $table, string $dateCol, ?int $mealTypeId) use ($db, $otp, $today) {
                $b = $db->table($table)->where('otp', $otp)->where($dateCol, $today);
                if ($mealTypeId !== null) $b->where('meal_type_id', $mealTypeId);
                return $b->get()->getRowArray();
            };

            // Try guest first, then intern
            $src          = $fetchByOtp('guest_subscriptions',  'subscription_date', $mealTypeIdInput);
            $srcTable     = 'GUEST';
            $srcTableName = 'guest_subscriptions';
            $srcDateCol   = 'subscription_date';
            $nameField    = 'guest_name';
            $typeField    = 'guest_type';

            if (! $src) {
                $src          = $fetchByOtp('intern_subscriptions', 'subscription_date', $mealTypeIdInput);
                if ($src) {
                    $srcTable     = 'INTERN';
                    $srcTableName = 'intern_subscriptions';
                    $srcDateCol   = 'subscription_date';
                    $nameField    = 'intern_name';
                    $typeField    = 'subscription_type';
                }
            }

            if (! $src) {
                $db->transComplete();
                return $this->json(false, null, 'Invalid or unmatched OTP', ApiCode::OTP_INVALID);
            }

            // Cafeteria mismatch guard (critical)
            $cafeteriaIdOfSrc = (int)($src['cafeteria_id'] ?? 0);
            if ($cafeteriaIdOfSrc !== $cafeteriaIdInput) {
                $db->transComplete();
                return $this->json(false, null, 'OTP belongs to a different cafeteria', ApiCode::OTP_WRONG_CAFETERIA);
            }

            // Already consumed?
            if (isset($src['status']) && strtoupper($src['status']) === 'REDEEMED') {
                $db->transComplete();
                return $this->json(false, null, 'Meal already consumed', ApiCode::MEAL_ALREADY_CONSUMED);
            }

            // Collect fields
            $mealTypeId     = (int)($src['meal_type_id'] ?? 0);
            $mealDate       = $src[$srcDateCol] ?? $today;
            $subscriptionId = (int)($src['id'] ?? 0);
            $userName       = $src[$nameField] ?? '';
            $phone          = $src['phone'] ?? '';
            $userType       = $src[$typeField] ?? '';

            // Prevent duplicate token for same source row
            $existingToken = $db->table('meal_tokens')
                ->where('subscription_table', $srcTable)
                ->where('subscription_id', $subscriptionId)
                ->get()->getRowArray();

            if ($existingToken) {
                if ($existingToken && $existingToken['status'] === 'REDEEMED') {
                    $db->transComplete();
                    return $this->json(false, null, 'Meal already consumed, Token exists!', ApiCode::TOKEN_EXISTS_REDEEMED);
                }
                $db->table('meal_tokens')->where('id', $existingToken['id'])
                ->update(['status' => 'REDEEMED', 'redeemed_at' => date('Y-m-d H:i:s')]);
                $autoToken = $existingToken['token_code'];
            } else {
                // Generate and insert auto token (NOT the OTP)
                $autoToken = $this->generateMealTokenCode($db, 0, $mealTypeId, $mealDate);
                $db->table('meal_tokens')->insert([
                    'user_id'            => 0,
                    'subscription_table' => $srcTable,           // GUEST | INTERN
                    'subscription_id'    => $subscriptionId,
                    'token_code'         => $autoToken,
                    'meal_type_id'       => $mealTypeId,
                    'meal_date'          => $mealDate,
                    'cafeteria_id'       => $cafeteriaIdOfSrc,
                    'status'             => 'REDEEMED',
                    'created_at'         => date('Y-m-d H:i:s'),
                    'redeemed_at'        => date('Y-m-d H:i:s'),
                ]);
            }

            // Mark source row REDEEMED
            $db->table($srcTableName)->where('id', $subscriptionId)->update(['status' => 'REDEEMED']);

            // Build payload + dashboard (table-based)
            $cafeteria = $db->table('cafeterias')->where('id', $cafeteriaIdOfSrc)->get()->getRowArray();
            $dash = $this->computeDashboardInfo($cafeteriaIdOfSrc, $today, $db);

            $db->transComplete();

            return $this->json(true, [
                'user' => [
                    'name'  => $userName ?: null,
                    'phone' => $phone ?: null,
                    'user_type' => $userType ?: ($srcTable === 'GUEST' ? 'GUEST' : 'INTERN'),
                ],
                'meal' => [
                    'meal_date'    => $this->formatMealDateWithCurrentTime($mealDate),
                    'meal_type_id' => $mealTypeId,
                    'meal_type'    => $this->subs->getMealTypeName($mealTypeId),
                    'token_code'   => $autoToken,
                ],
                'cafeteria' => $cafeteria ? [
                    'id'       => (int)$cafeteria['id'],
                    'name'     => $cafeteria['name'] ?? null,
                    'location' => $cafeteria['location'] ?? null,
                ] : null,
                'dashboard' => $dash,
                ], '', ApiCode::OK);
        }

        // ------------------- CASE B: emp_id / card_code path (employee subscription) -------------------
        if ($empId === '' && $cardCode === '') {
            $db->transComplete();
            return $this->json(false, null, 'Provide otp or card_code or emp_id', ApiCode::MISSING_FIELDS);
        }

        // Resolve user via meal_card (ACTIVE)
        $cardQ = $db->table('meal_card')->where('status', 'ACTIVE');
        if ($empId !== '')    $cardQ->where('employee_id', $empId);
        if ($cardCode !== '') $cardQ->where('card_code', $cardCode);
        $card = $cardQ->get()->getRowArray();

        if ($card && !empty($card['user_id'])) {
            $userId = (int)$card['user_id'];
        } else {
            // Fallback: resolve by employee_id (no active card needed)
            if ($empId !== '') {
                $userRow = $db->table('users')
                    ->where('employee_id', $empId)
                    ->where('status', 'ACTIVE')
                    ->get()->getRowArray();
        
                if (! $userRow) {
                    $db->transComplete();
                    return $this->json(false, null, 'Employee not found or inactive', ApiCode::EMPLOYEE_NOT_FOUND);
                }
                $userId = (int)$userRow['id'];
            } else {
                // No card match and no empId provided → still an error
                $db->transComplete();
                return $this->json(false, null, 'Valid active card not found for given emp_id/card_code', ApiCode::ACTIVE_CARD_NOT_FOUND);
            }
        }

        // Today's subscription detail (optionally filter by meal_type)
        $msdQ = $db->table('meal_subscription_details')
            ->where('user_id', $userId)
            ->where('subscription_date', $today); // if your column is "meal_date", change here

        if ($mealTypeIdInput !== null) $msdQ->where('meal_type_id', $mealTypeIdInput);

        $msd = $msdQ->orderBy('id', 'DESC')->get()->getRowArray();
        if (! $msd) {
            $db->transComplete();
            return $this->json(false, null, 'No scheduled meal for today', ApiCode::NO_SCHEDULED_MEAL);
        }

        if (isset($msd['status']) && strtoupper($msd['status']) === 'REDEEMED') {
            $db->transComplete();
            return $this->json(false, null, 'Meal already consumed', ApiCode::MEAL_ALREADY_CONSUMED);
        }

        // ✅ Cafeteria mismatch guard (critical)
        $cafeteriaIdOfMsd = (int)($msd['cafeteria_id'] ?? 0);
        if ($cafeteriaIdOfMsd !== $cafeteriaIdInput) {
            $db->transComplete();
            return $this->json(false, null, 'This meal is for a different cafeteria', ApiCode::DIFFERENT_CAFETERIA);
        }

        // Insert token (auto) and mark detail as REDEEMED
        $autoToken = $this->generateMealTokenCode(
            $db, 
            (int)$userId, 
            (int)($msd['meal_type_id'] ?? 0), 
            $msd['meal_date'] ?? date('Y-m-d')
        );

        $db->table('meal_tokens')->insert([
            'user_id'            => $userId,
            'subscription_table' => 'EMPLOYEE',
            'subscription_id'    => (int)($msd['id'] ?? 0),
            'token_code'         => $autoToken,
            'meal_type_id'       => (int)$msd['meal_type_id'],
            'meal_date'          => $msd['subscription_date'], // if your column is meal_date, change here too
            'cafeteria_id'       => $cafeteriaIdOfMsd,
            'status'             => 'REDEEMED',
            'created_at'         => date('Y-m-d H:i:s'),
            'redeemed_at'        => date('Y-m-d H:i:s'),
        ]);

        $db->table('meal_subscription_details')->where('id', $msd['id'])->update(['status' => 'REDEEMED']);

        // Payload + dashboard (table-based)
        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();
        $cafeteria = $db->table('cafeterias')->where('id', $cafeteriaIdOfMsd)->get()->getRowArray();
        $mealTypeId = (int)$msd['meal_type_id'];
        $mealDate   = $msd['subscription_date'];

        $dash = $this->computeDashboardInfo($cafeteriaIdOfMsd, $today, $db);

        $db->transComplete();

        return $this->json(true, [
            'user' => [
                'id'          => (int)$user['id'],
                'employee_id' => $user['employee_id'] ?? null,
                'name'        => $user['name'] ?? null,
                'user_type' => 'EMPLOYEE', 
            ],
            'meal' => [
                'meal_date'    => $this->formatMealDateWithCurrentTime($mealDate),
                'meal_type_id' => $mealTypeId,
                'meal_type'    => $this->subs->getMealTypeName($mealTypeId),
                'token_code'   => $autoToken,
            ],
            'cafeteria' => $cafeteria ? [
                'id'       => (int)$cafeteria['id'],
                'name'     => $cafeteria['name'] ?? null,
                'location' => $cafeteria['location'] ?? null,
            ] : null,
            'dashboard' => $dash,
        ], '', ApiCode::OK);
    }



    /**
     * 4) Cafeteria List
     * Output: all active cafeterias (JSON)
     */
    public function cafeterias()
    {
        $rows = $this->cafes->select('id,name,location')->where('is_active', 1)->orderBy('name')->findAll();
        return $this->response->setJSON($rows);
    }

    /**
     * APIname: DashboardInfo
     * Input: cafeteria_id
     * Output: { today_registration, consumption, remaining }
     *
     * Strategy:
     *   1) If there are any tokens issued for today for this cafeteria (meal_tokens),
     *      use token counts:
     *        - registration = COUNT(tokens for today, any status)
     *        - consumption  = COUNT(tokens for today, status = 'REDEEMED')
     *   2) Otherwise, fallback to table-based counts (sum of sources):
     *        - registration = sum of (meal_subscription_details, guest_subscriptions,
     *                                 intern_subscriptions) for today with non-cancel statuses
     *        - consumption  = sum of the same sources with status = 'REDEEMED'
     *   Remaining = max(0, registration - consumption)
     */
    public function dashboardInfo()
    {
        $in = $this->readInput();
        $cafeteriaId = (int) ($in['cafeteria_id'] ?? $this->request->getGet('cafeteria_id') ?? 0);
        if ($cafeteriaId <= 0) {
            return $this->json(false, null, 'cafeteria_id is required (integer)', ApiCode::CAFETERIA_REQUIRED);
        }
        $db = \Config\Database::connect();
        $data = $this->computeDashboardInfo($cafeteriaId, date('Y-m-d'), $db);
        return $this->json(true, $data);
    }

    /**
     * Dashboard counts using ONLY source tables (no meal_tokens).
     * Counts for a given cafeteria & date:
     *  - today_registration: rows in non-cancel statuses
     *  - consumption: rows with status='REDEEMED'
     *  - remaining: registration - consumption
     */
    private function computeDashboardInfo(
        int $cafeteriaId,
        ?string $date = null,
        ?ConnectionInterface $db = null
    ): array {
        $date = $date ?: date('Y-m-d');
        $db   = $db ?: \Config\Database::connect();

        // Adjust this list to match your "registered but not cancelled" statuses.
        $nonCancelStatuses = ['PENDING', 'ACTIVE', 'REDEEMED'];

        $sum = function(string $table, string $dateCol, array $extra = []) use ($db, $cafeteriaId, $date): int {
            try {
                $b = $db->table($table)->selectCount('id', 'c')
                    ->where('cafeteria_id', $cafeteriaId)
                    ->where($dateCol, $date);

                foreach ($extra as $k => $v) {
                    if ($k === '__in__') {
                        foreach ($v as $col => $vals) $b->whereIn($col, $vals);
                    } else {
                        $b->where($k, $v);
                    }
                }
                return (int) $b->get()->getRow('c');
            } catch (\Throwable $e) {
                // Table might not exist in some setups → treat as 0
                return 0;
            }
        };

        // Registration (scheduled/approved for the day)
        $regSubs   = $sum('meal_subscription_details', 'subscription_date', ['__in__' => ['status' => $nonCancelStatuses]]);
        $regGuest  = $sum('guest_subscriptions',       'subscription_date', ['__in__' => ['status' => $nonCancelStatuses]]);
        $regIntern = $sum('intern_subscriptions',      'subscription_date', ['__in__' => ['status' => $nonCancelStatuses]]);
        $registration = $regSubs + $regGuest + $regIntern;

        // Consumption (actually redeemed)
        $conSubs   = $sum('meal_subscription_details', 'subscription_date',        ['status' => 'REDEEMED']);
        $conGuest  = $sum('guest_subscriptions',       'subscription_date', ['status' => 'REDEEMED']);
        $conIntern = $sum('intern_subscriptions',      'subscription_date', ['status' => 'REDEEMED']);
        $consumption = $conSubs + $conGuest + $conIntern;

        return [
            'today_registration' => $registration,
            'consumption'        => $consumption,
            'remaining'          => max(0, $registration - $consumption),
        ];
    }

    private function readInput(): array
    {
        // Detect JSON by Content-Type header
        $ct = $this->request->getHeaderLine('Content-Type');
        if ($ct && stripos($ct, 'application/json') !== false) {
            $raw  = $this->request->getBody();
            $data = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // return empty so caller can handle the error cleanly
                return ['__json_error__' => 'Invalid JSON: ' . json_last_error_msg()];
            }
            return $data ?? [];
        }

        // form-data / x-www-form-urlencoded
        $post = $this->request->getPost();
        if (! empty($post)) {
            return $post;
        }

        // raw urlencoded (PUT/PATCH) fallback
        $rawInput = $this->request->getRawInput();
        return $rawInput ?? [];
    }

    /**
     * Generate a unique token_code for meal_tokens.
     * Format: T{userId}-{Ymd}-{mealTypeId|X}-{RANDOM}
     * - Checks DB for collisions and retries with stronger randomness.
    */
    private function generateMealTokenCode(
        ConnectionInterface $db,
        int $userId,
        ?int $mealTypeId,
        ?string $mealDate = null,
        int $maxAttempts = 5
    ): string {
        $datePart = $mealDate ? date('Ymd', strtotime($mealDate)) : date('Ymd');
        $typePart = $mealTypeId ? (string)(int)$mealTypeId : 'X';
    
        for ($i = 0; $i < $maxAttempts; $i++) {
            // Escalate randomness on retries (first tries 6 hex, then 10, then 16…)
            $bytes   = $i < 2 ? 3 : ($i < 4 ? 5 : 8);
            $rand    = strtoupper(bin2hex(random_bytes($bytes)));
            $code    = "T{$userId}-{$datePart}-{$typePart}-{$rand}";
    
            $exists = $db->table('meal_tokens')->select('id')
                         ->where('token_code', $code)
                         ->get()->getFirstRow();
            if (! $exists) {
                return $code;
            }
        }
    
        // Last resort: max entropy
        return "T{$userId}-{$datePart}-{$typePart}-" . strtoupper(bin2hex(random_bytes(12)));
    }

    /**
     * GET /api/get-ramadan-period
     *
     * Query params:
     *   - year (required, int) e.g., 2026
     *
     * Behavior:
     *   - Returns the configured Ramadan period for the given year
     *     from the `ramadan_config` table.
     *   - No support for 'date' or 'today' lookups.
     *
     * Response:
     * {
     *   "success": true,
     *   "message": "",
     *   "data": {
     *     "year": 2026,
     *     "start_date": "2026-02-17",
     *     "end_date": "2026-03-18",
     *     "days_total": 30
     *   }
     * }
     *
     * Error cases:
     *   - Missing or invalid year:
     *       { "success": false, "message": "year is required, e.g. /api/ramadan?year=2026" }
     *   - Year not found:
     *       { "success": false, "message": "No Ramadan configuration found for the requested year" }
     */

    public function ramadanInfo()
    {
        // read year from JSON/form/query (year is REQUIRED)
        $in   = $this->readInput();
        $year = (int) ($in['year'] ?? $this->request->getGet('year') ?? 0);

        if ($year <= 0) {
            return $this->json(false, null, 'year is required, e.g. /api/ramadan?year=2026', ApiCode::RAMADAN_YEAR_REQUIRED);
        }

        $db  = \Config\Database::connect();
        $row = $db->table('ramadan_config')
                ->where('year', $year)
                ->orderBy('id', 'DESC')      // in case duplicates exist, pick latest
                ->get()
                ->getRowArray();

        if (! $row) {
            return $this->json(false, null, 'No Ramadan configuration found for the requested year', ApiCode::RAMADAN_NOT_FOUND);
        }

        $start = $row['start_date'];
        $end   = $row['end_date'];

        // total days between start & end (inclusive); if bad dates, fall back to null
        $daysTotal = null;
        try {
            $dStart = new \DateTime($start);
            $dEnd   = new \DateTime($end);
            $daysTotal = (int) $dStart->diff($dEnd)->days + 1;
        } catch (\Throwable $e) {
            // leave $daysTotal as null
        }

        return $this->json(true, [
            'year'       => (int) $row['year'],
            'start_date' => $start,
            'end_date'   => $end,
            'days_total' => $daysTotal, // 29–30 typically; based on configured dates
        ], '', ApiCode::OK);
    }

    public function getTodayMealTypes()
    {
        $db    = \Config\Database::connect();

        $tz          = new \DateTimeZone('Asia/Dhaka');
        $now         = new \DateTime('now', $tz);
        $today       = $now->format('Y-m-d');        // for DB queries
        $currentTime = $now->format('d M Y h:i A');  // e.g. "12 Jan 2025 03:20 PM"

        // ---------- 1) EID check (Eid day OR Eid next day) ----------
        // today BETWEEN occasion_date AND (occasion_date + 1 day)
        $eidRow = $db->query(
            "SELECT id, tag, name, occasion_date
            FROM occasions
            WHERE ? BETWEEN occasion_date AND DATE_ADD(occasion_date, INTERVAL 1 DAY)
            LIMIT 1",
            [$today]
        )->getRowArray();

        if ($eidRow) {
            $types = $db->table('meal_types')
                ->select('id, name')
                ->where('is_active', 1)
                ->whereIn('id', [4,5,6,7])
                ->orderBy('id', 'ASC')
                ->get()->getResultArray();

            return $this->json(true, [
                'date'       => $currentTime,
                'mode'       => 'eid',                       // eid day or next day
                'occasion'   => $eidRow['name'],
                'meal_types' => array_map(fn($r) => [
                    'id' => (int)$r['id'], 'name' => $r['name']
                ], $types),
            ]);
        }

        // ---------- 2) Ramadan period check ----------
        $ramadanRow = $db->table('ramadan_config')
            ->where('start_date <=', $today)
            ->where('end_date >=',   $today)
            ->orderBy('year', 'DESC')
            ->get()->getRowArray();

        if ($ramadanRow) {
            $types = $db->table('meal_types')
                ->select('id, name')
                ->where('is_active', 1)
                ->whereIn('id', [2,3])
                ->orderBy('id', 'ASC')
                ->get()->getResultArray();

            return $this->json(true, [
                'date'       => $currentTime,
                'mode'       => 'ramadan',
                'year'       => (int)$ramadanRow['year'],
                'start_date' => $ramadanRow['start_date'],
                'end_date'   => $ramadanRow['end_date'],
                'meal_types' => array_map(fn($r) => [
                    'id' => (int)$r['id'], 'name' => $r['name']
                ], $types),
            ]);
        }

        // ---------- 3) General day (default) ----------
        $types = $db->table('meal_types')
            ->select('id, name')
            ->where('is_active', 1)
            ->where('id', 1)
            ->get()->getResultArray();

        return $this->json(true, [
            'date'       => $currentTime,
            'mode'       => 'general',
            'meal_types' => array_map(fn($r) => [
                'id' => (int)$r['id'], 'name' => $r['name']
            ], $types),
        ]);
    }

    /**
     * Combine a Y-m-d date with the current time (Asia/Dhaka by default)
     * and return a formatted string like: "03 Sep,2025 02:30 PM".
     */
    private function formatMealDateWithCurrentTime(
        string $mealDate,
        string $tzName = 'Asia/Dhaka',
        string $outFormat = 'd M,Y h:i A'
    ): string {
        $tz  = new \DateTimeZone($tzName);
        $now = new \DateTime('now', $tz);

        // Parse the provided date (expects Y-m-d). Fallback to strtotime parsing.
        $date = \DateTime::createFromFormat('Y-m-d', trim($mealDate), $tz)
            ?: new \DateTime($mealDate, $tz);

        // Set current time on that date
        $date->setTime(
            (int)$now->format('H'),
            (int)$now->format('i'),
            (int)$now->format('s')
        );

        return $date->format($outFormat);
    }


}
