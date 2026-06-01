<?php

namespace App\Models;

use CodeIgniter\Model;

class IpdApiFetchLogModel extends Model
{
    protected $table            = 'ipd_api_fetch_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'fetched_at',
        'record_time',
        'success',
        'wards_saved',
        'patient_total',
        'payload_json',
        'error_message',
    ];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * @return list<array<string, mixed>>
     */
    public function getRecentLogs(int $limit = 100, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $builder = $this->select('id, fetched_at, record_time, success, wards_saved, patient_total, error_message, created_at')
            ->orderBy('fetched_at', 'DESC');

        if ($dateFrom !== null && $dateFrom !== '') {
            $builder->where('DATE(fetched_at) >=', $dateFrom);
        }
        if ($dateTo !== null && $dateTo !== '') {
            $builder->where('DATE(fetched_at) <=', $dateTo);
        }

        return $builder->findAll($limit);
    }

    public function getLogWithPayload(int $id): ?array
    {
        return $this->find($id);
    }

    public function getLatestSuccessfulWithPayload(): ?array
    {
        return $this->where('success', 1)
            ->where('payload_json IS NOT NULL')
            ->where('payload_json !=', '')
            ->orderBy('fetched_at', 'DESC')
            ->first();
    }
}
