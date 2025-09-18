<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'id';

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'azure_id',
        'employee_id',
        'name',
        'email',
        'phone',
        'department',
        'designation',
        'division',
        'user_type',
        'login_method',
        'status',
        'line_manager_id',
        'password',
    ];

    /**
     * Compare relevant columns to decide if an update is needed.
     */
    public function needsUpdate(array $lms, array $mapped): bool
    {
        $keys = [
            'employee_id','azure_id','name','phone',
            'department','designation','division',
            'user_type','login_method','local_user_type','status'
        ];
        foreach ($keys as $k) {
            $a = (string) ($lms[$k]    ?? '');
            $b = (string) ($mapped[$k] ?? '');
            if ($a !== $b) return true;
        }
        return false;
    }


    public function getRolesFor(int $userId): array
    {
        $rows = $this->db
            ->table('user_roles as ur')
            ->select('r.name')
            ->join('roles as r', 'r.id = ur.role_id')
            ->where('ur.user_id', $userId)
            ->get()
            ->getResultArray();

        // Extract just the names:
        return array_column($rows, 'name');
    }
}
