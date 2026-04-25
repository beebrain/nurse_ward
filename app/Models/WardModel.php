<?php

namespace App\Models;

use CodeIgniter\Model;

class WardModel extends Model
{
    protected $table            = 'wards';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['name', 'code', 'department_id', 'total_beds', 'is_active'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'name'          => 'required|max_length[200]|min_length[2]',
        'total_beds'    => 'required|numeric|greater_than_equal_to[0]',
        'department_id' => 'permit_empty|numeric',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    public function getActiveWithDepartment(): array
    {
        return $this->select('wards.*, departments.short_name as department_name')
                    ->join('departments', 'departments.id = wards.department_id', 'left')
                    ->where('wards.is_active', true)
                    ->orderBy('departments.sort_order', 'ASC')
                    ->orderBy('wards.code', 'ASC')
                    ->findAll();
    }

    public function getAllWithDepartment(): array
    {
        return $this->select('wards.*, departments.short_name as department_name')
                    ->join('departments', 'departments.id = wards.department_id', 'left')
                    ->orderBy('departments.sort_order', 'ASC')
                    ->orderBy('wards.code', 'ASC')
                    ->findAll();
    }

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
}
