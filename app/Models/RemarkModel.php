<?php

namespace App\Models;

use CodeIgniter\Model;

class RemarkModel extends Model
{
    protected $table            = 'remarks';
    protected $primaryKey       = 'id';

    protected $useAutoIncrement = true;

    protected $returnType    = 'array';
    protected $protectFields = true;

    protected $allowedFields = [
        'subs_id',
        'remark',
        'approver_remark',
        'created_at',
    ];

    // created_at exists with DEFAULT CURRENT_TIMESTAMP
    // Using timestamps lets CI set it when you insert explicitly.
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = null;

    protected $validationRules = [
        'remark'         => 'permit_empty|string',
        'approver_remark'=> 'permit_empty|string',
    ];
}
