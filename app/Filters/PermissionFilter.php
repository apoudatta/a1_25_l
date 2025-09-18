<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $perms = $arguments ?? [];
        if (empty($perms)) {
            return; // no specific permission required
        }

        $userId = (int) session('user_id');
        if (! $userId) {
            // not logged in -> send to login
            return redirect()->to(site_url('auth/login'));
        }

        $authz = Services::authz();

        // SUPER ADMIN bypass
        if ($authz->isSuper($userId)) {
            return;
        }

        // allow if user has ANY of the listed permissions
        foreach ($perms as $p) {
            if ($authz->can($userId, $p)) {
                return;
            }
        }

        // not allowed -> return 403 response (works both for web & API)
        $response = Services::response();

        // JSON/AJAX?
        $accept = $request->getHeaderLine('Accept');
        if ($request->isAJAX() || str_contains($accept, 'application/json')) {
            return $response->setStatusCode(403)
                            ->setJSON([
                                'status'  => false,
                                'code'    => 403,
                                'message' => 'Forbidden',
                            ]);
        }

        // HTML response
        return $response->setStatusCode(403)
                        ->setBody('403 Forbidden: You do not have permission to access this resource.');
        // If you have a custom view, you could use:
        // return $response->setStatusCode(403)->setBody(view('errors/custom_403'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
