<?php

namespace App\Models;

use CodeIgniter\Model;

class DepartmentModel extends Model
{
    protected $table            = 'departments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['name', 'short_name', 'sort_order', 'is_active'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name' => 'required|max_length[200]',
    ];

    public function getActiveOrdered(): array
    {
        return $this->where('is_active', true)
                    ->orderBy('sort_order', 'ASC')
                    ->findAll();
    }

    public function getWithWardCount(): array
    {
        return $this->select('departments.*, COUNT(wards.id) as ward_count')
                    ->join('wards', 'wards.department_id = departments.id AND wards.deleted_at IS NULL', 'left')
                    ->where('departments.is_active', true)
                    ->groupBy('departments.id')
                    ->orderBy('departments.sort_order', 'ASC')
                    ->findAll();
    }
}
