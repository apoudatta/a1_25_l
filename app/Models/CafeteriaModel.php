<?php namespace App\Models;

use CodeIgniter\Model;

class CafeteriaModel extends Model
{
    protected $table         = 'cafeterias';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'name',
        'location',
        'is_active',
    ];

    // Auto-manage created_at, updated_at
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
