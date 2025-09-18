<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\UserRoleModel;
use Config\Services;

class Users extends BaseController
{
    protected $userModel;
    protected $userRoleModel;
    protected $session;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->userRoleModel = new UserRoleModel();
        $this->session   = session();
    }

    // List users
    public function index()
    {
        $data['users'] = $this->userModel->findAll();
        return view('admin/users/index', $data);
    }

    // Show create form
    public function create()
    {
        return view('admin/users/form', ['user' => null]);
    }

    // Store new user
    public function store()
    {
        $rules = [
            'name'     => 'required|min_length[2]|max_length[100]',
            'email'    => 'required|valid_email|max_length[191]|is_unique[users.email]',
            'password' => 'permit_empty|min_length[6]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $post = $this->request->getPost();

        // Hash password if provided
        if (! empty($post['password'])) {
            $post['password_hash'] = password_hash($post['password'], PASSWORD_BCRYPT);
        }
        //unset($post['password']);

        $post['login_method'] = 'LOCAL';
        $post['status'] = 'ACTIVE';

        $this->userModel->insert($post);
        $id   = (int) $this->userModel->getInsertID();
        $user = $this->userModel->select('employee_id,name,email,phone,department,designation,division,user_type,login_method,local_user_type,status,password')->find($id);

        if (! empty($post['password'])) {
            $user['password'] = $post['password'];  // attach plain password for sync use
        }

        // here add data in user_roles table
        $this->userRoleModel->insert(['user_id' => $id, 'role_id' => 4]);

        // Sync: only for LOCAL users
        $syncMsg = $this->syncPortalIfLocal($user, 'created');
        return redirect()->to('admin/users')->with('success', 'User created. ' . $syncMsg);
    }

    // Show edit form
    public function edit($id)
    {
        $user = $this->userModel->find($id);
        if (! $user) {
            return redirect()->back()->with('success',"User #$id not found");
        }
        return view('admin/users/form', ['user' => $user]);
    }

    // Update existing user
    public function update($id)
    {
        $post = $this->request->getPost();

        // Hash new password if provided
        if (! empty($post['password'])) {
            $post['password_hash'] = password_hash($post['password'], PASSWORD_BCRYPT);
        }
        //unset($post['password']);

        $this->userModel->update($id, $post);
        $user = $this->userModel->find((int) $id);
        
        if (! empty($post['password'])) {
            $user['password'] = $post['password'];  // attach plain password for sync use
        }

        // Sync: only for LOCAL users
        $syncMsg = $this->syncPortalIfLocal($user, 'updated');

        return redirect()->to('admin/users')
            ->with('success', 'User updated. ' . $syncMsg);
    }

    // active user
    public function active($id)
    {
        $this->userModel->update($id, ['status' => 'ACTIVE']);$user    = $this->userModel->find((int) $id);
        $syncMsg = $this->syncPortalIfLocal($user, 'activated');

        return redirect()->to('admin/users')->with('success', 'User Activated! ' . $syncMsg);
    }
    // Inactive user
    public function inactive($id)
    {
        $this->userModel->update($id, ['status' => 'INACTIVE']);
        $user    = $this->userModel->find((int) $id);
        $syncMsg = $this->syncPortalIfLocal($user, 'inactivated');

        return redirect()->to('admin/users')->with('success', 'User deactivated! ' . $syncMsg);
    }

    public function getEmpId($id)
    {
        $user = $this->userModel->select('employee_id')->find((int) $id);
        if (! $user) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'User not found']);
        }
        return $this->response->setJSON(['employee_id' => (string) ($user['employee_id'] ?? '')]);
    }

    /**
     * Sync to Portal only when login_method = LOCAL
     */
    protected function syncPortalIfLocal(?array $user, string $action): string
    {
        try {
            if (! $user) {
                return '(sync skipped: user not found)';
            }

            $loginMethod = strtoupper((string) ($user['login_method'] ?? ''));
            if ($loginMethod !== 'LOCAL') {
                return '(sync skipped: SSO user)';
            }

            $result = $this->sendUserToPortal($action, $user);

            if ($result['ok']) {
                return '(sync ok: ' . ($result['body']['message'] ?? 'success') . ')';
            }

            // Log and show short note
            log_message('error', 'Portal sync failed: ' . $result['error']);
            return '(sync failed: ' . ($result['body']['message'] ?? $result['error'] ?? 'unknown error') . ')';

        } catch (\Throwable $e) {
            log_message('error', 'Portal sync exception: ' . $e->getMessage());
            return '(sync error)';
        }
    }

    protected function sendUserToPortal(string $action, array $user): array
    {
        $baseURL = (string) env('PORTAL_URL', '');
        $apiKey  = (string) env('PORTAL_SYNC_API_KEY', '');
        $timeout = (int) env('PORTAL_SYNC_TIMEOUT', 15);

        if ($baseURL === '' || $apiKey === '') {
            return [
                'ok'    => false,
                'error' => 'Portal URL or PORTAL_SYNC_API_KEY not configured',
                'code'  => 0,
                'body'  => null,
            ];
        }

        $endpoint = 'api/service/user-sync';

        $payload = [
            'action' => $action,
            'user'   => $this->buildUserPayload($user),
        ];

        $client = Services::curlrequest([
            'baseURI'     => $baseURL,
            'timeout'     => $timeout,
            'http_errors' => false, // don't throw on 4xx/5xx
            'verify'      => true,  // set false only if you have self-signed certs
        ]);

        try {
            $res  = $client->post($endpoint, [
                'headers' => [
                    'X-Api-Key'    => $apiKey,            // <-- simple header auth
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $code = $res->getStatusCode();
            $body = (string) $res->getBody();
            $json = json_decode($body, true);

            return [
                'ok'    => ($code >= 200 && $code < 300) && is_array($json) && ($json['status'] ?? '') === 'success',
                'code'  => $code,
                'body'  => is_array($json) ? $json : ['raw' => $body],
                'error' => ($code >= 200 && $code < 300)
                            ? (($json['message'] ?? null) ? null : 'Invalid response structure')
                            : ('HTTP ' . $code),
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Portal sync failed: ' . $e->getMessage());
            return [
                'ok'    => false,
                'code'  => 0,
                'body'  => null,
                'error' => $e->getMessage(),
            ];
        }
    }


    /**
     * Shape the user JSON we send to Portal
     */
    protected function buildUserPayload(array $u): array
    {
        return [
            //'id'              => (int) ($u['id'] ?? 0),
            'employee_id'     => (string) ($u['employee_id'] ?? ''),
            'name'            => (string) ($u['name'] ?? ''),
            'email'           => (string) ($u['email'] ?? ''),
            'phone'           => (string) ($u['phone'] ?? ''),
            'division'        => (string) ($u['division'] ?? ''),
            'designation'     => (string) ($u['designation'] ?? ''),
            'department'      => (string) ($u['department'] ?? ''),
            'portal'          => 'LMS',
            //'user_type'       => (string) ($u['user_type'] ?? ''),
            'login_method'    => ($u['login_method'] ?? ''),  // LOCAL / SSO
            'local_user_type' => ($u['local_user_type'] ?? ''), // DRIVER/VENDOR/SYSTEM_USER (for LOCAL)
            'status'          => ($u['status'] ?? ''),        // ACTIVE/INACTIVE
            'password'        => (string) ($u['password'] ?? ''),
        ];
    }

    /**
     * Minimal HS256 JWT encoder (no external library)
     **/
    protected function makeJwt(array $payload, string $secret): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];

        $segments   = [];
        $segments[] = $this->b64url(json_encode($header, JSON_UNESCAPED_SLASHES));
        $segments[] = $this->b64url(json_encode($payload, JSON_UNESCAPED_SLASHES));

        $signingInput = implode('.', $segments);
        $signature    = hash_hmac('sha256', $signingInput, $secret, true);
        $segments[]   = $this->b64url($signature);

        return implode('.', $segments);
    }

    protected function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function lineManagerSet(int $employeeId)
    {
        $db = db_connect();

        $employee = $db->table('users')
            ->select('id,name,email,line_manager_id,status')
            ->where('id', $employeeId)
            ->get()->getRowArray();
        if (!$employee) {
            return redirect()->back()->with('error', 'Employee not found.');
        }

        $allUsers = $db->table('users')
            ->select('id,name,email,employee_id')
            ->where('status', 'ACTIVE')
            ->where('login_method', 'SSO')
            ->where('id !=', $employeeId)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        $currentLmId = $employee['line_manager_id'] ?? null;

        return view('admin/users/set_line_manager', [
            'employee'    => $employee,
            'allUsers'    => $allUsers,
            'currentLmId' => $currentLmId,
            'action'      => site_url('admin/users/'.$employeeId.'/line-manager'),
        ]);
    }

    // Save LM (updates only users.line_manager_id)
    public function lineManagerSave(int $employeeId)
    {
        $db = db_connect();

        $employee = $db->table('users')->select('id')->where('id', $employeeId)->get()->getRowArray();
        if (!$employee) {
            return redirect()->back()->with('error', 'Employee not found.');
        }

        $lineManagerId = (int) ($this->request->getPost('line_manager_id') ?? 0);

        if ($lineManagerId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Please choose a line manager.');
        }
        if ($lineManagerId === $employeeId) {
            return redirect()->back()->withInput()->with('error', 'Employee and Line Manager cannot be the same person.');
        }

        $lmExists = $db->table('users')->select('id')
            ->where('id', $lineManagerId)
            ->where('status', 'ACTIVE')
            ->get()->getRowArray();
        if (!$lmExists) {
            return redirect()->back()->withInput()->with('error', 'Selected Line Manager does not exist or is inactive.');
        }

        $db->table('users')->where('id', $employeeId)->update([
            'line_manager_id' => $lineManagerId,
            // optionally track source:
            // 'lm_source' => 'MANUAL',
        ]);

        return redirect()->to(site_url('admin/users'))
            ->with('message', 'Line Manager saved successfully.');
    }

}
