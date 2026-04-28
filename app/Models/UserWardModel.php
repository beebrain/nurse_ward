<?php

namespace App\Models;

use CodeIgniter\Model;

class UserWardModel extends Model
{
    protected $table            = 'user_ward_assignments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['user_id', 'ward_id'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /** Ward IDs assigned to a user. */
    public function getWardIdsForUser(int $userId): array
    {
        return array_column(
            $this->select('ward_id')->where('user_id', $userId)->findAll(),
            'ward_id'
        );
    }

    /** Full ward rows (with department) assigned to a user. */
    public function getWardsForUser(int $userId): array
    {
        return $this->db->table('user_ward_assignments uwa')
            ->select('w.id, w.name, w.code, d.name AS department_name')
            ->join('wards w', 'w.id = uwa.ward_id')
            ->join('departments d', 'd.id = w.department_id', 'left')
            ->where('uwa.user_id', $userId)
            ->orderBy('d.sort_order')
            ->orderBy('w.code')
            ->get()->getResultArray();
    }

    public function userCanAccessWard(int $userId, int $wardId): bool
    {
        return $this->where('user_id', $userId)->where('ward_id', $wardId)->countAllResults() > 0;
    }

    public function getAssignedUserForWard(int $wardId, ?int $excludeUserId = null): ?array
    {
        $builder = $this->db->table('user_ward_assignments uwa')
            ->select('u.id, u.username')
            ->join('users u', 'u.id = uwa.user_id')
            ->join('auth_groups_users agu', "agu.user_id = u.id AND agu.group = 'nurse'")
            ->where('uwa.ward_id', $wardId);

        if ($excludeUserId !== null) {
            $builder->where('u.id !=', $excludeUserId);
        }

        return $builder->get(1)->getRowArray() ?: null;
    }

    public function wardIsAssignedToAnotherUser(int $wardId, int $userId): bool
    {
        return $this->getAssignedUserForWard($wardId, $userId) !== null;
    }

    /** Replace all ward assignments for a user atomically. */
    public function syncUserWards(int $userId, array $wardIds): void
    {
        $this->db->transStart();
        $this->where('user_id', $userId)->delete();
        foreach (array_unique($wardIds) as $wardId) {
            $this->insert(['user_id' => $userId, 'ward_id' => (int)$wardId]);
        }
        $this->db->transComplete();
    }

    /**
     * Returns all users with the nurse role, along with a comma-separated
     * list of their assigned ward names (for the admin listing page).
     */
    public function getNursesWithAssignments(): array
    {
        $rows = $this->db->query("
            SELECT
                u.id,
                u.username,
                ai.secret AS email,
                u.approval_status,
                GROUP_CONCAT(CONCAT(IFNULL(w.code,''), ' ', w.name) ORDER BY w.code SEPARATOR ', ') AS wards_list,
                COUNT(uwa.ward_id) AS ward_count
            FROM users u
            JOIN auth_groups_users agu ON agu.user_id = u.id AND agu.group = 'nurse'
            LEFT JOIN auth_identities ai ON ai.user_id = u.id AND ai.type = 'email_password'
            LEFT JOIN user_ward_assignments uwa ON uwa.user_id = u.id
            LEFT JOIN wards w ON w.id = uwa.ward_id
            GROUP BY u.id, u.username, ai.secret, u.approval_status
            ORDER BY u.username
        ")->getResultArray();

        return $rows;
    }
}
