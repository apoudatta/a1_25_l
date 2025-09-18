<?php

namespace App\Models;

use CodeIgniter\Model;

class InternBatchModel extends Model
{
    protected $table      = 'intern_batches';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'uploaded_by',
        'meal_type_id',
        'start_date',
        'end_date',
        'status',
        'subscription_type',
        'remark',
        'upload_time',
    ];
	public    $useTimestamps = false;
}
