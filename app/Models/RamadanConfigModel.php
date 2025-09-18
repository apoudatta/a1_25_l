<?php namespace App\Models;

use CodeIgniter\Model;

class RamadanConfigModel extends Model
{
    protected $table         = 'ramadan_config';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'year',            // newly added
        'start_date',
        'end_date',
    ];
    protected $useTimestamps = false;
}
