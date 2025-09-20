<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\Database\ConnectionInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var list<string>
     */
    protected $helpers = ['form', 'url'];

    protected $session;

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.

        // Load session
        $this->session = \Config\Services::session();
    }

    protected function writeAccessLog(string $message = '', string $level = 'info'): void
    {
        date_default_timezone_set('Asia/Dhaka');

        $req   = $this->request;
        $res   = $this->response;
        $time  = date('d-M-Y:H:i:s O');
        $user  = $_SERVER['REMOTE_USER'] ?? '-';
        $ip    = $req->getIPAddress();
        $uri   = $req->getUri();
        $reqLn = sprintf(
            '%s %s%s %s',
            $req->getMethod(),
            $uri->getPath(),
            $uri->getQuery() ? ('?' . $uri->getQuery()) : '',
            $req->getServer('SERVER_PROTOCOL')
        );
        $status    = $res->getStatusCode();
        $bytes     = strlen((string)$res->getBody());
        $referer   = $req->getHeaderLine('Referer')          ?: '-';
        $agent     = $req->getHeaderLine('User-Agent')       ?: '-';
        $xfwd      = $req->getHeaderLine('X-Forwarded-For') ?: '-';
        $trace     = $req->getHeaderLine('X-Trace-Id')      ?: '-';
        $cfip      = $req->getHeaderLine('CF-Connecting-IP')?: '-';

        $line = sprintf(
            '[%s] - %s - %s "%s" %s %s "%s" "%s" "%s" %s "%s"',
            $time, $user, $ip,
            $reqLn, $status, $bytes,
            $referer, $agent, $xfwd, $trace, $cfip
        );

        if ($message !== '') {
            // tack on your custom sync summary
            $line .= ' — ' . $message;
        }

        $logPath = WRITEPATH . 'logs/adfs-sync-' . date('Y-m-d') . '.log';
        file_put_contents($logPath, $line . PHP_EOL, FILE_APPEND);

        log_message($level, $line);
    }

    function dd($data)
    {
        echo "<pre>";
        print_r($data);
        echo "</pre>";
        exit();
    }

    /**
     * Generate a 6-digit numeric OTP (leading zeros allowed) that is unique
     * across guest_subscriptions.otp and intern_subscriptions.otp.
     *
     * We also give it a couple of retries in case of race conditions.
     */
    protected function getOtp(int $length = 6): string
    {
        $db = \Config\Database::connect();

        $maxAttempts = 10;
        for ($i = 0; $i < $maxAttempts; $i++) {
            // 6 digits, can be like '004217'
            $otp = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);

            $existsOTP  = $db->table('meal_reference')->select('id')->where('otp', $otp)->get()->getFirstRow();

            if (! $existsOTP) {
                return $otp; // unique across both tables
            }
        }

        // Extremely unlikely to reach; last resort with more entropy (still 6 digits)
        // You can also throw an exception here if you prefer.
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function send_sms($phone, $message)
    {
        $db     = db_connect();
        $logs   = $db->table('sms_logs');
        $logId  = null;

        try {
            // Normalize to E.164 (e.g., +8801521450824)
            $msisdn = $this->normalizeMsisdn($phone);

            // 1) Create a PENDING log first
            $logs->insert([
                'msisdn'  => $msisdn,
                'message' => $message,
                'status'  => 'PENDING', // or 'QUEUED'
                // created_at uses DEFAULT CURRENT_TIMESTAMP
            ]);
            $logId = $db->insertID();

            // 2) Send SMS via your client
            $smsClient = new \App\Libraries\SmsClient();
            $response  = $smsClient->sendSms($msisdn, $message);

            // 3) Interpret your provider response:
            // {
            //   "response": { "code": 200, "timestamp": "...", "message": "Success" },
            //   "info":     { "request": "SMS Send", "requestID": "625adeamit914", "smsCount": 1, "balance": 941039.95 }
            // }
            $respArr = is_object($response) ? json_decode(json_encode($response), true) : (array) $response;

            $code      = (int)   ($respArr['response']['code']    ?? 0);
            $provMsg   = (string)($respArr['response']['message'] ?? '');
            $requestId = (string)($respArr['info']['requestID']   ?? '');

            // Treat code 200/202 (accepted) as success; also accept explicit "Success"
            $isOk = in_array($code, [200, 202], true) || strcasecmp($provMsg, 'Success') === 0;

            $finalStatus = $isOk ? 'SENT' : 'FAILED';

            // 4) Update the DB log status
            if ($logId) {
                $logs->where('id', $logId)->update(['status' => $finalStatus]);
            }

            // Optional file logging for traceability
            // write_log(
            //     sprintf(
            //         '[%s] code=%s msg=%s requestID=%s payload=%s',
            //         $finalStatus,
            //         $code,
            //         $provMsg,
            //         $requestId,
            //         json_encode($respArr, JSON_UNESCAPED_SLASHES)
            //     ),
            //     'send_sms.log'
            // );

            // Keep returning provider response as before
            return $response;
        } catch (\Throwable $e) {
            // Mark FAILED if we already created a row
            if ($logId) {
                try { $logs->where('id', $logId)->update(['status' => 'FAILED']); } catch (\Throwable $ignore) {}
            }
            write_log('SMS ERROR: ' . $e->getMessage(), 'send_sms.log');
            return false;
        }
    }


    private function normalizeMsisdn(string $phone): string
    {
        // keep only digits and plus, then drop the plus
        $p = preg_replace('/[^\d+]/', '', trim($phone));
        $p = ltrim($p, '+');                 // "+8801..." -> "8801..."

        // add "88" if not already present at the start
        if (strpos($p, '88') !== 0) {
            $p = '88' . $p;                  // "01XXXXXXXXX" -> "8801XXXXXXXXX"
        }

        return $p;                           // always like: 8801XXXXXXXXX
    }

    public function dtFrmt($date){
       return date('d M Y', strtotime($date));
    }

    /** for sms
     * Turn ['2025-08-31','2025-09-01','2025-09-02','2025-09-03'] into:
     * "Aug 31, Sep 1-3, 2025"
     */
    public function formatDatesCompact(array $dates, string $tz = 'Asia/Dhaka'): string
    {
        $list = [];
        foreach ($dates as $d) {
            try {
                $dt = new \DateTime($d, new \DateTimeZone($tz));
                $list[$dt->format('Y-m-d')] = $dt;
            } catch (\Throwable $e) { /* skip invalid */ }
        }
        if (!$list) return '';
        ksort($list);
        $list = array_values($list);

        $ranges = [];
        $start = $end = $list[0];
        for ($i = 1; $i < count($list); $i++) {
            $cur = $list[$i];
            $prevPlus1 = (clone $end)->modify('+1 day');
            if ($cur->format('Y-m-d') === $prevPlus1->format('Y-m-d')) {
                $end = $cur;
            } else {
                $ranges[] = [$start, $end];
                $start = $end = $cur;
            }
        }
        $ranges[] = [$start, $end];

        $parts = [];
        $years = [];
        foreach ($ranges as [$s, $e]) {
            $years[$s->format('Y')] = true;
            $years[$e->format('Y')] = true;

            if ($s->format('Y-m-d') === $e->format('Y-m-d')) {
                $parts[] = $s->format('M j');
            } else {
                if ($s->format('Y') === $e->format('Y')) {
                    $parts[] = ($s->format('m') === $e->format('m'))
                        ? $s->format('M j') . '-' . $e->format('j')
                        : $s->format('M j') . ' - ' . $e->format('M j');
                } else {
                    $parts[] = $s->format('M j, Y') . ' - ' . $e->format('M j, Y');
                }
            }
        }

        if (count($years) === 1) {
            $year = array_key_first($years);
            return implode(', ', $parts) . ', ' . $year;
        }
        return implode(', ', $parts);
    }



}
