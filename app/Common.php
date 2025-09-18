<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */


 use Config\Services;

if (! function_exists('can')) {
    function can(string $perm): bool
    {
        $uid = (int) session('user_id');
        return $uid ? Services::authz()->can($uid, $perm) : false;
    }
}

if (! function_exists('canAny')) {
    function canAny(array $perms): bool
    {
        foreach ($perms as $p) {
            if (can($p)) return true;
        }
        return false;
    }
}