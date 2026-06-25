<?php

namespace App\Models;

use CodeIgniter\Model;

class HosxpNursingLevelMapModel extends Model
{
    protected $table            = 'hosxp_nursing_level_map';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['hosxp_code', 'hosxp_name', 'ward_level', 'sort_order'];
    protected $useTimestamps    = true;
}
