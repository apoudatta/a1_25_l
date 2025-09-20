<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType    = 'array';
    protected $protectFields = true;

    // ENUMS
    public const USER_EMPLOYEE = 'EMPLOYEE';
    public const USER_VENDOR   = 'VENDOR';
    public const USER_ADMIN    = 'ADMIN';

    public const LOGIN_SSO   = 'SSO';
    public const LOGIN_LOCAL = 'LOCAL';

    public const LOCAL_SYSTEM = 'SYSTEM';
    public const LOCAL_VENDOR = 'VENDOR';
    public const LOCAL_ADFS   = 'ADFS';

    public const STATUS_ACTIVE   = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    protected $allowedFields = [
        'employee_id',
        'name',
        'email',
        'phone',
        'department',
        'designation',
        'division',
        'user_type',
        'login_method',
        'local_user_type',
        'password',
        'status',
        'line_manager_id',
        'password_hash',
        'created_at',
        'updated_at',
    ];

    // TIMESTAMP columns exist in table
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'employee_id'    => 'permit_empty|string|max_length[20]',
        'name'           => 'permit_empty|string|max_length[100]',
        'email'          => 'required|valid_email|max_length[150]|is_unique[users.email,id,{id}]',
        'phone'          => 'permit_empty|string|max_length[20]',
        'department'     => 'permit_empty|string|max_length[100]',
        'designation'    => 'permit_empty|string|max_length[100]',
        'division'       => 'permit_empty|string|max_length[100]',
        'user_type'      => 'permit_empty|in_list[EMPLOYEE,VENDOR,ADMIN]',
        'login_method'   => 'permit_empty|in_list[SSO,LOCAL]',
        'local_user_type'=> 'required|in_list[SYSTEM,VENDOR,ADFS]',
        'password'       => 'permit_empty|string|max_length[25]',
        'status'         => 'permit_empty|in_list[ACTIVE,INACTIVE]',
        'line_manager_id'=> 'permit_empty|integer',
        'password_hash'  => 'permit_empty|string|max_length[255]',
    ];
}
