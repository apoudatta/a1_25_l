<?php

namespace App\Models;

use CodeIgniter\Model;

class CutoffTimeModel extends Model
{
    protected $table            = 'cutoff_times';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType       = 'array';
    protected $protectFields    = true;

    protected $allowedFields = [
        'meal_type_id',
        'cut_off_time',
        'lead_days',
        'max_horizon_days',
        'is_active',
        'cutoff_date',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'meal_type_id'     => 'required|is_natural_no_zero',
        'cut_off_time'     => 'required',
        'lead_days'        => 'required|integer',
        'max_horizon_days' => 'required|integer',
        'is_active'        => 'in_list[0,1]',
        'cutoff_date'      => 'permit_empty|valid_date[Y-m-d]',
    ];
}
