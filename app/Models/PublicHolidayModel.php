<?php namespace App\Models;

use CodeIgniter\Model;

class PublicHolidayModel extends Model
{
    protected $table         = 'public_holidays';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'holiday_date',
        'description',
        'is_active',
        'created_by',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
