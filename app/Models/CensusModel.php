<?php

namespace App\Models;

use CodeIgniter\Model;

class CensusModel extends Model
{
    protected $table            = 'daily_census';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'ward_id', 'record_date', 'shift', 
        'admissions', 'discharges', 'transfers_in', 
        'transfers_out', 'deaths', 'total_remaining', 
        'created_by'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'ward_id'         => 'required|numeric|is_not_unique[wards.id]',
        'record_date'     => 'required|valid_date',
        'shift'           => 'required|in_list[Morning,Afternoon,Night]',
        'admissions'      => 'required|numeric|greater_than_equal_to[0]',
        'discharges'      => 'required|numeric|greater_than_equal_to[0]',
        'transfers_in'    => 'required|numeric|greater_than_equal_to[0]',
        'transfers_out'   => 'required|numeric|greater_than_equal_to[0]',
        'deaths'          => 'required|numeric|greater_than_equal_to[0]',
        'total_remaining' => 'required|numeric|greater_than_equal_to[0]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
