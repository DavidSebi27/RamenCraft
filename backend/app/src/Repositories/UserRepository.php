<?php

namespace App\Repositories;

use App\Config\Database;
use App\Models\User;

/**
 * UserRepository — owns ALL SQL for users.
 *
 * Returns typed User objects via PDO::FETCH_CLASS.
 */
class UserRepository
{
    private \PDO $db;

    private const COLUMNS = 'id, username, email, role, total_xp, current_rank, created_at, updated_at';
    private const COLUMNS_WITH_PASSWORD = 'id, username, email, password_hash, role, total_xp, current_rank, created_at, updated_at';

    public function __construct(?\PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Find all users with optional search/filter and pagination.
     *
     * @return User[]
     */
    public function findAll(?string $search = null, ?string $role = null, ?string $rank = null, int $page = 1, int $limit = 10): array
    {
        $where = '';
        $params = [];

        if ($search) {
            $where = "WHERE (username LIKE :search OR email LIKE :search2)";
            $params[':search'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }

        if ($role) {
            $where .= ($where ? ' AND' : 'WHERE') . ' role = :role';
            $params[':role'] = $role;
        }

        if ($rank) {
            $where .= ($where ? ' AND' : 'WHERE') . ' current_rank = :rank';
            $params[':rank'] = $rank;
        }

        $offset = ($page - 1) * $limit;

        $sql = "SELECT " . self::COLUMNS . " FROM users {$where} ORDER BY id ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_CLASS, User::class);
    }

    /**
     * Count total users matching filters.
     *
     * @return int
     */
    public function count(?string $search = null, ?string $role = null, ?string $rank = null): int
    {
        $where = '';
        $params = [];

        if ($search) {
            $where = "WHERE (username LIKE :search OR email LIKE :search2)";
            $params[':search'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }

        if ($role) {
            $where .= ($where ? ' AND' : 'WHERE') . ' role = :role';
            $params[':role'] = $role;
        }

        if ($rank) {
            $where .= ($where ? ' AND' : 'WHERE') . ' current_rank = :rank';
            $params[':rank'] = $rank;
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Find a single user by ID (without password).
     *
     * @return User|null
     */
    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare("SELECT " . self::COLUMNS . " FROM users WHERE id = :id");
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        $stmt->setFetchMode(\PDO::FETCH_CLASS, User::class);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Find a user by email (includes password_hash for login verification).
     *
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare("SELECT " . self::COLUMNS_WITH_PASSWORD . " FROM users WHERE email = :email");
        $stmt->execute([':email' => trim($email)]);
        $stmt->setFetchMode(\PDO::FETCH_CLASS, User::class);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Find a user by ID with bowls_served count (for /auth/me).
     *
     * @return User|null
     */
    public function findByIdWithBowlCount(int $id): ?User
    {
        $stmt = $this->db->prepare(
            "SELECT u.id, u.username, u.email, u.role, u.total_xp, u.current_rank,
                    u.created_at, u.updated_at,
                    COUNT(sb.id) AS bowls_served
             FROM users u
             LEFT JOIN served_bowls sb ON sb.user_id = u.id
             WHERE u.id = :id
             GROUP BY u.id"
        );
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        $stmt->setFetchMode(\PDO::FETCH_CLASS, User::class);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Register a new user.
     *
     * @return User
     */
    public function register(string $username, string $email, string $passwordHash): User
    {
        $stmt = $this->db->prepare(
            "INSERT INTO users (username, email, password_hash)
             VALUES (:username, :email, :password_hash)"
        );
        $stmt->execute([
            ':username' => trim($username),
            ':email' => trim($email),
            ':password_hash' => $passwordHash,
        ]);

        return $this->findById((int) $this->db->lastInsertId());
    }

    /**
     * Update user fields.
     *
     * @return User|null
     */
    public function update(int $id, array $data): ?User
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET
                username = COALESCE(:username, username),
                email = COALESCE(:email, email),
                role = COALESCE(:role, role),
                total_xp = COALESCE(:total_xp, total_xp),
                current_rank = COALESCE(:current_rank, current_rank)
             WHERE id = :id"
        );
        $stmt->execute([
            ':username' => $data['username'] ?? null,
            ':email' => $data['email'] ?? null,
            ':role' => $data['role'] ?? null,
            ':total_xp' => $data['totalXp'] ?? null,
            ':current_rank' => $data['currentRank'] ?? null,
            ':id' => $id,
        ]);

        return $this->findById($id);
    }

    /**
     * Delete a user and all related data (cascade).
     *
     * @return bool
     */
    public function delete(int $id): bool
    {
        $this->db->prepare("DELETE FROM bowl_ingredients WHERE bowl_id IN (SELECT id FROM served_bowls WHERE user_id = :uid)")
                 ->execute([':uid' => $id]);
        $this->db->prepare("DELETE FROM served_bowls WHERE user_id = :uid")
                 ->execute([':uid' => $id]);
        $this->db->prepare("DELETE FROM user_achievements WHERE user_id = :uid")
                 ->execute([':uid' => $id]);
        $this->db->prepare("DELETE FROM favorite_ingredients WHERE favorite_id IN (SELECT id FROM favorites WHERE user_id = :uid)")
                 ->execute([':uid' => $id]);
        $this->db->prepare("DELETE FROM favorites WHERE user_id = :uid")
                 ->execute([':uid' => $id]);

        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Check if a user exists.
     *
     * @return bool
     */
    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE id = :id");
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Add XP to a user's total and return the new total.
     *
     * @return int  New total XP
     */
    public function addXp(int $userId, int $xpEarned): int
    {
        $this->db->prepare('UPDATE users SET total_xp = total_xp + :xp WHERE id = :id')
                 ->execute([':xp' => $xpEarned, ':id' => $userId]);

        $stmt = $this->db->prepare('SELECT total_xp FROM users WHERE id = :id');
        $stmt->bindValue(':id', $userId, \PDO::PARAM_INT);
        $stmt->execute();
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

    /**
     * Find players ranked by XP descending for the leaderboard.
     *
     * @return User[]
     */
    public function findLeaderboard(?string $search = null, ?string $rank = null, int $page = 1, int $limit = 10): array
    {
        $where = "WHERE role = 'player'";
        $params = [];

        if ($search) {
            $where .= " AND username LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }

        if ($rank) {
            $where .= " AND current_rank = :rank";
            $params[':rank'] = $rank;
        }

        $offset = ($page - 1) * $limit;

        $sql = "SELECT " . self::COLUMNS . " FROM users {$where}
                ORDER BY total_xp DESC, id ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_CLASS, User::class);
    }

    /**
     * Count total players for leaderboard pagination.
     *
     * @return int
     */
    public function countLeaderboard(?string $search = null, ?string $rank = null): int
    {
        $where = "WHERE role = 'player'";
        $params = [];

        if ($search) {
            $where .= " AND username LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }

        if ($rank) {
            $where .= " AND current_rank = :rank";
            $params[':rank'] = $rank;
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
