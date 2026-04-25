<?php

namespace App\Models;

use CodeIgniter\Model;

class CensusEditLogModel extends Model
{
    protected $table            = 'census_edit_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['census_id', 'edited_by', 'action', 'snapshot'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';    // no updated_at on audit log

    public function logAction(int $censusId, ?int $userId, string $action, ?array $snapshot): void
    {
        $this->insert([
            'census_id'  => $censusId,
            'edited_by'  => $userId,
            'action'     => $action,
            'snapshot'   => $snapshot ? json_encode($snapshot, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }

    public function getForCensus(int $censusId): array
    {
        return $this->db->table('census_edit_logs cel')
            ->select('cel.*, u.username AS editor_username')
            ->join('users u', 'u.id = cel.edited_by', 'left')
            ->where('cel.census_id', $censusId)
            ->orderBy('cel.created_at', 'ASC')
            ->get()->getResultArray();
    }
}
