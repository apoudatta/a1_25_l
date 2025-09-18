<?php namespace App\Models;

use CodeIgniter\Model;

class CutoffTimeModel extends Model
{
    protected $table         = 'cutoff_times';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'meal_type_id',
        //'cutoff_date',      // nullable DATE for a specific override; leave null for global default
        'cut_off_time',     // TIME
        'lead_days',        // INT
        'max_horizon_days', // INT
        'is_active',        // TINYINT(1)
    ];
}
