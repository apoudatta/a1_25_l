<?php
use Config\Services;

if (! function_exists('can')) {
    function can(string $permission): bool {
        $userId = (int) session('user_id');
        if (! $userId) return false;
        return Services::authz()->can($userId, $permission);
    }
}

if (! function_exists('has_role')) {
    function has_role(string $role): bool {
        $userId = (int) session('user_id');
        if (! $userId) return false;
        return Services::authz()->hasRole($userId, $role);
    }
}
