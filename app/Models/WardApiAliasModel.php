<?php

namespace App\Models;

use CodeIgniter\Model;

class WardApiAliasModel extends Model
{
    protected $table            = 'ward_api_aliases';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['ward_id', 'api_ward_code', 'api_ward_name'];
    protected $useTimestamps    = true;

    /**
     * @return array<int, list<array<string, mixed>>>
     */
    public function getAliasesGroupedByWardId(): array
    {
        $grouped = [];
        foreach ($this->orderBy('api_ward_name', 'ASC')->findAll() as $row) {
            $wid = (int) $row['ward_id'];
            $grouped[$wid][] = $row;
        }

        return $grouped;
    }

    /**
     * @return array<string, int> api_ward_name => ward_id
     */
    public function getNameToWardIdMap(?int $excludeWardId = null): array
    {
        $map = [];
        $builder = $this->select('ward_id, api_ward_name');
        if ($excludeWardId !== null) {
            $builder->where('ward_id !=', $excludeWardId);
        }
        foreach ($builder->findAll() as $row) {
            $name = trim((string) $row['api_ward_name']);
            if ($name !== '') {
                $map[$name] = (int) $row['ward_id'];
            }
        }

        return $map;
    }

    /**
     * @param list<string> $names
     */
    public function syncForWard(int $wardId, string $apiWardCode, array $names): void
    {
        $this->where('ward_id', $wardId)->delete();

        $seen = [];
        foreach ($names as $name) {
            $name = trim($name);
            if ($name === '' || isset($seen[$name])) {
                continue;
            }
            $seen[$name] = true;
            $this->insert([
                'ward_id'       => $wardId,
                'api_ward_code' => $apiWardCode !== '' ? $apiWardCode : null,
                'api_ward_name' => $name,
            ]);
        }
    }
}
