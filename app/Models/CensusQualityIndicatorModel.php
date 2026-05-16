<?php

namespace App\Models;

use CodeIgniter\Model;

class CensusQualityIndicatorModel extends Model
{
    protected $table            = 'census_quality_indicators';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'census_id',
        // HAI
        'hai_vap', 'hai_hap', 'hai_uti', 'hai_cauti',
        'hai_clabsi', 'hai_ssi', 'hai_bsi', 'hai_mdr',
        // Special conditions
        'new_sepsis', 'end_of_life', 'palliative_care',
        'critical_care_support',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function upsertForCensus(int $censusId, array $data): void
    {
        $existing = $this->where('census_id', $censusId)->first();
        $data['census_id'] = $censusId;
        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert($data);
        }
    }

    public function findByCensusId(int $censusId): ?array
    {
        return $this->where('census_id', $censusId)->first();
    }
}
