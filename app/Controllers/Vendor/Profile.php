<?php

namespace App\Controllers\Vendor;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Profile extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $userId = session('user_id');
        $user   = $this->userModel->find($userId);

        return view('vendor/profile/index', [
            'user'              => $user,
            'op_contact_name'   => $user['op_contact_name']  ?? '',
            'op_contact_phone'  => $user['op_contact_phone'] ?? '',
            'op_contact_email'  => $user['op_contact_email'] ?? '',
            'description'       => $user['description']      ?? '',
        ]);
    }

    public function update()
    {
        $userId = session('user_id');
        $post   = $this->request->getPost();

        $this->userModel->update($userId, [
            'op_contact_name'   => $post['op_contact_name'],
            'op_contact_phone'  => $post['op_contact_phone'],
            'op_contact_email'  => $post['op_contact_email'],
            'description'       => $post['description'],
        ]);

        // For AJAX
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true]);
        }

        return redirect()->to('vendor/profile')
                         ->with('success', 'Profile updated successfully.');
    }
}
