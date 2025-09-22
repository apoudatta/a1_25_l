<?php
namespace App\Controllers\Admin\Rbac;

use App\Controllers\BaseController;

class Assign extends BaseController
{
    // Show a role with all permissions (checkbox UI expected in view)
    public function role(int $roleId)
    {
        $db = db_connect();
        $role = $db->table('roles')->where('id',$roleId)->get()->getRowArray()
            ?? abort(404,'Role not found');

        $perm = $db->table('permissions')->orderBy('name');

        // roleID 1 - Super Admin, 2 - Admin, 3 - Employee, 4 - vendor
        if ($roleId === 4) {
            // vendor.* only
            $perm->like('name', 'vendor.', 'after');   // name LIKE 'vendor.%'
        } else {
            $perm->notLike('name', 'vendor.', 'after'); // name NOT LIKE 'employee.%'
    
            // If notLike() isn't available, use raw WHERE (uncomment this instead):
            // $perm->where("name NOT LIKE 'employee.%'", null, false);
        }
    
        $allPerms = $perm->get()->getResultArray();
        $existing = $db->table('role_permissions')->where('role_id',$roleId)->get()->getResultArray();
        $attached = array_column($existing, 'permission_id');


        return view('admin/rbac/assign_role_perms', [
            'role' => $role,
            'permissions' => $allPerms,
            'attached' => $attached,
        ]);
    }

    public function saveRole(int $roleId)
    {
        $db = db_connect();
        $ids = array_map('intval', (array) $this->request->getPost('permission_id'));

        $db->transStart();
        $db->table('role_permissions')->where('role_id',$roleId)->delete();
        if (! empty($ids)) {
            $rows = array_map(fn($pid)=> ['role_id'=>$roleId,'permission_id'=>$pid], $ids);
            $db->table('role_permissions')->insertBatch($rows);
        }
        $db->transComplete();

        // Invalidate cache for all users in this role (optional; simple approach)
        // You can optimize by selecting distinct user_ids from user_roles where role_id = $roleId
        service('authz')->forget((int) session('user_id'));

        return redirect()->back()->with('success','Permissions updated for role.');
    }

    // Show a user with all roles (checkbox UI expected in view)
    public function user(int $userId)
    {
        $db = db_connect();
        $user = $db->table('users')->select('id, employee_id, email')->where('id',$userId)->get()->getRowArray()
            ?? abort(404,'User not found');

        $allRoles = $db->table('roles')
            ->where('name !=', 'SUPER ADMIN')
            ->orderBy('name')
            ->get()->getResultArray();

        $existing = $db->table('user_roles')->where('user_id',$userId)->get()->getResultArray();
        $attached = array_column($existing, 'role_id');

        return view('admin/rbac/assign_user_roles', [
            'user' => $user,
            'roles' => $allRoles,
            'attached' => $attached,
        ]);
    }

    public function saveUser(int $userId)
    {
        $db = db_connect();
        $ids = array_map('intval', (array) $this->request->getPost('role_id'));

        $db->transStart();
        $db->table('user_roles')->where('user_id',$userId)->delete();
        if (! empty($ids)) {
            $rows = array_map(fn($rid)=> ['user_id'=>$userId,'role_id'=>$rid], $ids);
            $db->table('user_roles')->insertBatch($rows);

            // Admin role_id 4 => set users.user_type = 'ADMIN'
            if (in_array(4, $ids, true)) {
                $db->table('users')->where('id', $userId)->update(['user_type' => 'ADMIN']);
            }
        }
        $db->transComplete();

        service('authz')->forget($userId);

        return redirect()->back()->with('success','Roles updated for user.');
    }
}
