<?php namespace App\Models;

use CodeIgniter\Model;

class OccasionModel extends Model
{
    protected $table      = 'occasions';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'name',
        'occasion_date',
    ];
    protected $useTimestamps = false;
}
