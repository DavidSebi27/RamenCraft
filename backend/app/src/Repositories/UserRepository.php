<?php

namespace App\Repositories;

use App\Config\Database;

/**
 * UserRepository — database queries for users.
 *
 * Currently only the methods needed by BowlService.
 * Could be expanded to fully replace UserController's raw SQL.
 */
class UserRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Add XP to a user's total and return the new total.
     */
    public function addXp(int $userId, int $xpEarned): int
    {
        $this->db->prepare('UPDATE users SET total_xp = total_xp + :xp WHERE id = :id')
                 ->execute([':xp' => $xpEarned, ':id' => $userId]);

        $stmt = $this->db->prepare('SELECT total_xp FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Update a user's rank.
     */
    public function updateRank(int $userId, string $rank): void
    {
        $this->db->prepare('UPDATE users SET current_rank = :rank WHERE id = :id')
                 ->execute([':rank' => $rank, ':id' => $userId]);
    }
}
