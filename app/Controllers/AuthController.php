<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Controller;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use CodeIgniter\API\ResponseTrait;
use Config\Services;

class AuthController extends BaseController
{
    use ResponseTrait;
    protected $session;
    protected $userModel;

    public function __construct()
    {
        $this->session   = session();
        $this->userModel = new UserModel();
    }

    public function loginForm()
    {
        //return view('auth/login');
        Services::authz()->clearSession();
        $this->session->destroy();
        return redirect()->to(env('PORTAL_URL'));
    }

    public function login()
    {
        $post = $this->request->getPost();

        if (!empty($post['email']) && !empty($post['password'])) {
            $user = $this->userModel
                ->where('email', $post['email'])
                ->where('login_method', 'LOCAL')
                ->first();

            if ($user && password_verify($post['password'], $user['password_hash'])) {
                $this->setSessionData($user);
                //$this->dd($user);

                $userId = (int) $user['id'];
                Services::authz()->primeSession($userId);

                // If user was intercepted by a filter, send them back there
                if ($intended = session('intended_url')) {
                    session()->remove('intended_url');
                    return redirect()->to($intended);
                }

                return redirect()->to($this->getDashboardUrl($user['user_type'], $userId));
            }
            return redirect()->back()->with('error', 'Invalid credentials.');
        }
    }

    public function externalReceive()
    {
        $jwt = $this->request->getPost('token') ?? '';
        try {
            // 2) Decode & verify signature + expiry
            $decoded = JWT::decode(
                $jwt,
                new Key(env('JWT_SECRET'), env('JWT_ALGO', 'HS256'))
            );

            // 3) Extract your data
            $user = (array) $decoded->data;
        }
        catch (\Throwable $e) {
            // invalid or expired
            return $this->failForbidden('Invalid or expired token');
        }

        // Grab everything as an array
        //$payload = $this->request->getPost();
        //$user = json_decode($this->request->getPost('user_info'), true);
        if(isset($user['email']) && !empty($user['email'])){
            //$this->dd($user);
            $userdata = $this->userModel
            ->select('id, employee_id, name, email, phone, department, designation, division, status, user_type, login_method, line_manager_id')
            ->where('email', $user['email'])
            ->first();
            
            //$this->dd($userdata);
            if (! $userdata) {
                echo 'User not found for '.$user['email'];
                exit;
                // return redirect()->to('/')
                //     ->with('error', 'User not found for '.$user['email']);
            }

            if ($userdata['status'] == 'INACTIVE') {
                echo 'Account is inactive';
                exit;
                // return redirect()->to('/')
                //     ->with('error', 'Account is inactive');
            }

            $this->setSessionData($userdata);
            $userId = (int) $userdata['id'];
            Services::authz()->primeSession($userId);
            
            $getDashboardUrl = $this->getDashboardUrl($userdata['user_type'], $userId);
            if($getDashboardUrl == '/') {
                echo "no access set for this user. Contact admin.";
                exit;
            }
            return redirect()->to($getDashboardUrl);
        }
        else {
            echo 'user email not found!';
            exit;
        }
    }

    public function logout()
    {
        Services::authz()->clearSession();
        $this->session->destroy();
        return redirect()->to(env('PORTAL_URL'));
    }

    public function dashboardUrl() {
        $user_type = $this->request->getPost('user_type');
        $userId    = $this->request->getPost('userId');

        $getDashboardUrl = $this->getDashboardUrl($user_type, $userId);
        if($getDashboardUrl == '/') {
            echo "no access set for this user. Contact admin.";
            exit;
        }
        return redirect()->to($getDashboardUrl);
    }

    private function getDashboardUrl(string $type, int $userId): string
    {
        $authz = \Config\Services::authz();

        // Super admin bypass
        if ($authz->isSuper($userId)) {
            return site_url('admin/dashboard');
        }

        switch ($type) {
            case 'ADMIN':
                // Prefer the dashboard if allowed
                if ($authz->can($userId, 'admin.dashboard')) {
                    return site_url('admin/dashboard');
                }

                // Otherwise, pick the first admin page they can access
                $candidates = [
                    // perm => url (order = priority)
                    'admin.users'   => 'admin/users',
                    'admin.approvals' => 'admin/approvals',
                    'meal.subscriptions'           => 'admin/subscription',
                    'admin.guest-subscriptions'                => 'admin/guest-subscriptions',
                    'admin.ramadan'              => 'admin/ramadan',
                ];
                foreach ($candidates as $perm => $url) {
                    if ($authz->can($userId, $perm)) {
                        return site_url($url);
                    }
                }

                // Last resort: go home (or a "no access" page)
                return site_url('/');

            case 'EMPLOYEE':
                return site_url('employee/dashboard');

            case 'VENDOR':
                return site_url('vendor/dashboard');

            default:
                return site_url('/');
        }
    }

    private function setSessionData($user) {
        //$this->dd($user);
        session()->regenerate(true);

        // set fresh session data
        session()->set([
            'user_id'      => $user['id'],
            'user_name'    => $user['name'],
            'employee_id'  => $user['employee_id'],
            'email'        => $user['email'],
            'login_method' => $user['login_method'],
            'line_manager_id' => $user['line_manager_id'],
            'user_type'    => strtoupper($user['user_type'] ?? ''), // e.g. 'ADMIN'
            'isLoggedIn'   => true,
        ]);
    }

    public function test()
    {
        $msisdn = "8801838737333";
        $message = "Test from LMS robi 1";
        $response = $this->send_sms($msisdn, $message);
        $this->dd($response);
    }

}
