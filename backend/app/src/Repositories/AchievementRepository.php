<?php

namespace App\Repositories;

use App\Config\Database;
use App\Models\Achievement;

/**
 * AchievementRepository — owns ALL SQL for achievements and user_achievements.
 *
 * Returns typed Achievement objects via PDO::FETCH_CLASS.
 */
class AchievementRepository
{
    private \PDO $db;

    private const COLUMNS = 'id, name, description, icon, requirement_type, requirement_value, created_at';

    public function __construct(?\PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Find all achievements with optional search/filter and pagination.
     *
     * @return Achievement[]
     */
    public function findAll(?string $search = null, ?string $requirementType = null, int $page = 1, int $limit = 20): array
    {
        $where = '';
        $params = [];

        if ($search) {
            $where = "WHERE name LIKE :search OR description LIKE :search2";
            $params[':search'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }

        if ($requirementType) {
            $where .= ($where ? ' AND' : 'WHERE') . ' requirement_type = :rtype';
            $params[':rtype'] = $requirementType;
        }

        $offset = ($page - 1) * $limit;

        $sql = "SELECT " . self::COLUMNS . " FROM achievements {$where} ORDER BY id ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_CLASS, Achievement::class);
    }

    /**
     * Count total achievements matching filters.
     *
     * @return int
     */
    public function count(?string $search = null, ?string $requirementType = null): int
    {
        $where = '';
        $params = [];

        if ($search) {
            $where = "WHERE name LIKE :search OR description LIKE :search2";
            $params[':search'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }

        if ($requirementType) {
            $where .= ($where ? ' AND' : 'WHERE') . ' requirement_type = :rtype';
            $params[':rtype'] = $requirementType;
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM achievements {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Find a single achievement by ID.
     *
     * @return Achievement|null
     */
    public function findById(int $id): ?Achievement
    {
        $stmt = $this->db->prepare("SELECT " . self::COLUMNS . " FROM achievements WHERE id = :id");
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        $stmt->setFetchMode(\PDO::FETCH_CLASS, Achievement::class);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Insert a new achievement.
     *
     * @return Achievement
     */
    public function insert(array $data): Achievement
    {
        $stmt = $this->db->prepare(
            "INSERT INTO achievements (name, description, icon, requirement_type, requirement_value)
             VALUES (:name, :description, :icon, :requirement_type, :requirement_value)"
        );
        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':icon' => $data['icon'] ?? null,
            ':requirement_type' => $data['requirementType'] ?? null,
            ':requirement_value' => $data['requirementValue'] ?? null,
        ]);
        return $this->findById((int) $this->db->lastInsertId());
    }

    /**
     * Update an existing achievement.
     *
     * @return Achievement|null
     */
    public function update(int $id, array $data): ?Achievement
    {
        $stmt = $this->db->prepare(
            "UPDATE achievements SET
                name = COALESCE(:name, name),
                description = COALESCE(:description, description),
                icon = COALESCE(:icon, icon),
                requirement_type = COALESCE(:requirement_type, requirement_type),
                requirement_value = COALESCE(:requirement_value, requirement_value)
             WHERE id = :id"
        );
        $stmt->execute([
            ':name' => $data['name'] ?? null,
            ':description' => $data['description'] ?? null,
            ':icon' => $data['icon'] ?? null,
            ':requirement_type' => $data['requirementType'] ?? null,
            ':requirement_value' => $data['requirementValue'] ?? null,
            ':id' => $id,
        ]);
        return $this->findById($id);
    }

    /**
     * Delete an achievement.
     *
     * @return bool
     */
    public function delete(int $id): bool
    {
        // Cascade: remove user_achievements first
        $this->db->prepare("DELETE FROM user_achievements WHERE achievement_id = :id")
                 ->execute([':id' => $id]);

        $stmt = $this->db->prepare("DELETE FROM achievements WHERE id = :id");
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Check if an achievement exists.
     *
     * @return bool
     */
    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM achievements WHERE id = :id");
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Get all achievements with unlock status for a specific user.
     *
     * @return Achievement[]  Each has unlocked_at populated if unlocked
     */
    public function findAllWithUserStatus(int $userId, ?string $unlockedFilter = null, int $page = 1, int $limit = 50): array
    {
        $where = '';
        $params = [':uid' => $userId];

        if ($unlockedFilter === 'true') {
            $where = 'WHERE ua.id IS NOT NULL';
        } elseif ($unlockedFilter === 'false') {
            $where = 'WHERE ua.id IS NULL';
        }

        $offset = ($page - 1) * $limit;

        $sql = "SELECT a.id, a.name, a.description, a.icon, a.requirement_type,
                       a.requirement_value, a.created_at, ua.unlocked_at
                FROM achievements a
                LEFT JOIN user_achievements ua ON ua.achievement_id = a.id AND ua.user_id = :uid
                {$where}
                ORDER BY a.id ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, \PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_CLASS, Achievement::class);
    }

    /**
     * Get all achievements not yet unlocked by a user.
     *
     * @return Achievement[]
     */
    public function findPendingForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.id, a.name, a.description, a.icon, a.requirement_type,
                    a.requirement_value, a.created_at
             FROM achievements a
             WHERE a.id NOT IN (
                 SELECT achievement_id FROM user_achievements WHERE user_id = :uid
             )"
        );
        $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_CLASS, Achievement::class);
    }

    /**
     * Award an achievement to a user (INSERT IGNORE for idempotency).
     */
    public function awardToUser(int $userId, int $achievementId): void
    {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (:uid, :aid)"
        );
        $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':aid', $achievementId, \PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Gather stats needed for achievement evaluation.
     *
     * @return array  Associative array of stat values
     */
    public function gatherUserStats(int $userId, array $currentIngredientIds): array
    {
        $bowlsServed = $this->countStat("SELECT COUNT(*) FROM served_bowls WHERE user_id = :uid", $userId);

        $uniqueBroths = $this->countDistinctIngredients($userId, 1);
        $uniqueNoodles = $this->countDistinctIngredients($userId, 2);
        $uniqueOils = $this->countDistinctIngredients($userId, 3);

        $ajitamaCount = $this->countIngredientUsage($userId, 19);

        $lowScoreCount = $this->countStat(
            "SELECT COUNT(*) FROM served_bowls WHERE user_id = :uid AND total_score < 30", $userId
        );

        $identicalBowls = $this->countIdenticalBowls($userId);

        // Get category IDs for current bowl ingredients
        $currentCategories = $this->mapIngredientCategories($currentIngredientIds);

        return [
            'bowls_served'       => $bowlsServed,
            'unique_broths'      => $uniqueBroths,
            'unique_noodles'     => $uniqueNoodles,
            'unique_oils'        => $uniqueOils,
            'ajitama_count'      => $ajitamaCount,
            'low_score_count'    => $lowScoreCount,
            'identical_bowls'    => $identicalBowls,
            'current_categories' => $currentCategories,
        ];
    }

    // --- Private stat helpers ---

    private function countStat(string $sql, int $userId): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    private function countDistinctIngredients(int $userId, int $categoryId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(DISTINCT bi.ingredient_id)
             FROM bowl_ingredients bi
             JOIN served_bowls sb ON bi.bowl_id = sb.id
             JOIN ingredients i ON bi.ingredient_id = i.id
             WHERE sb.user_id = :uid AND i.category_id = :cid"
        );
        $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':cid', $categoryId, \PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    private function countIngredientUsage(int $userId, int $ingredientId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM bowl_ingredients bi
             JOIN served_bowls sb ON bi.bowl_id = sb.id
             WHERE sb.user_id = :uid AND bi.ingredient_id = :iid"
        );
        $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':iid', $ingredientId, \PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    private function countIdenticalBowls(int $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as cnt FROM served_bowls sb
             JOIN (
                 SELECT bowl_id, GROUP_CONCAT(ingredient_id ORDER BY ingredient_id) as combo
                 FROM bowl_ingredients GROUP BY bowl_id
             ) bi ON sb.id = bi.bowl_id
             WHERE sb.user_id = :uid
             GROUP BY bi.combo
             ORDER BY cnt DESC LIMIT 1"
        );
        $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private function mapIngredientCategories(array $ingredientIds): array
    {
        if (empty($ingredientIds)) return [];

        $placeholders = implode(',', array_fill(0, count($ingredientIds), '?'));
        $stmt = $this->db->prepare(
            "SELECT id, category_id FROM ingredients WHERE id IN ({$placeholders})"
        );
        $stmt->execute(array_map('intval', $ingredientIds));

        $map = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $map[(int) $row['id']] = (int) $row['category_id'];
        }
        return $map;
    }
}
