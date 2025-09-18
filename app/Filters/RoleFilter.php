<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class RoleFilter implements FilterInterface
{
    /**
     * Run before the controller.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments  Allowed roles (e.g. ['ADMIN'], ['EMPLOYEE'])
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $userType = session()->get('user_type');

        // If no roles defined or user's role not in allowed list, deny
        if (empty($arguments) || ! in_array($userType, $arguments)) {
            return redirect()->to('/')->with('error', 'Access denied.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing needed here
    }
}
