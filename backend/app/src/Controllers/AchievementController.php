<?php

namespace App\Controllers;

use App\Config\Database;
use App\Framework\Controller;

/**
 * AchievementController — handles API requests for achievements
 *
 * Endpoints:
 *   GET    /api/achievements      — list all (paginated)
 *   GET    /api/achievements/{id} — get a single achievement
 *   POST   /api/achievements      — create a new achievement
 *   PUT    /api/achievements/{id} — update an achievement
 *   DELETE /api/achievements/{id} — delete an achievement
 */
class AchievementController extends Controller
{
    /**
     * GET /api/achievements
     *
     * Returns a paginated list of achievements.
     */
    public function getAll(): void
    {
        try {
            $db = Database::getConnection();

            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int) ($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            $where = '';
            $params = [];

            if (!empty($_GET['search'])) {
                $where = "WHERE name LIKE :search OR description LIKE :search2";
                $params[':search'] = '%' . $_GET['search'] . '%';
                $params[':search2'] = '%' . $_GET['search'] . '%';
            }

            if (!empty($_GET['requirement_type'])) {
                $where .= ($where ? ' AND' : 'WHERE') . ' requirement_type = :rtype';
                $params[':rtype'] = $_GET['requirement_type'];
            }

            $countStmt = $db->prepare("SELECT COUNT(*) FROM achievements {$where}");
            $countStmt->execute($params);
            $total = (int) $countStmt->fetchColumn();

            $sql = "SELECT * FROM achievements {$where} ORDER BY id ASC LIMIT :limit OFFSET :offset";
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();

            $achievements = array_map([$this, 'formatAchievement'], $stmt->fetchAll());

            $this->sendSuccessResponse([
                'data' => $achievements,
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to fetch achievements: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/achievements/{id}
     */
    public function get(array $vars = []): void
    {
        try {
            $id = (int) ($vars['id'] ?? 0);
            if ($id <= 0) {
                $this->sendErrorResponse('Invalid achievement ID', 400);
                return;
            }

            $db = Database::getConnection();

            $stmt = $db->prepare("SELECT * FROM achievements WHERE id = :id");
            $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();

            $row = $stmt->fetch();
            if (!$row) {
                $this->sendErrorResponse('Achievement not found', 404);
                return;
            }

            $this->sendSuccessResponse($this->formatAchievement($row));
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to fetch achievement: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/achievements
     *
     * Creates a new achievement. Requires: name.
     */
    public function create(): void
    {
        $this->authenticate();

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input || empty($input['name'])) {
                $this->sendErrorResponse('Field "name" is required', 400);
                return;
            }

            $db = Database::getConnection();

            $sql = "INSERT INTO achievements (name, description, icon, requirement_type, requirement_value)
                    VALUES (:name, :description, :icon, :requirement_type, :requirement_value)";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':name' => $input['name'],
                ':description' => $input['description'] ?? null,
                ':icon' => $input['icon'] ?? null,
                ':requirement_type' => $input['requirementType'] ?? null,
                ':requirement_value' => $input['requirementValue'] ?? null,
            ]);

            $newId = (int) $db->lastInsertId();

            $fetchStmt = $db->prepare("SELECT * FROM achievements WHERE id = :id");
            $fetchStmt->bindValue(':id', $newId, \PDO::PARAM_INT);
            $fetchStmt->execute();

            $this->sendSuccessResponse($this->formatAchievement($fetchStmt->fetch()), 201);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to create achievement: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/achievements/{id}
     */
    public function update(array $vars = []): void
    {
        $this->authenticate();

        try {
            $id = (int) ($vars['id'] ?? 0);
            if ($id <= 0) {
                $this->sendErrorResponse('Invalid achievement ID', 400);
                return;
            }

            $db = Database::getConnection();

            $check = $db->prepare("SELECT id FROM achievements WHERE id = :id");
            $check->bindValue(':id', $id, \PDO::PARAM_INT);
            $check->execute();
            if (!$check->fetch()) {
                $this->sendErrorResponse('Achievement not found', 404);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $this->sendErrorResponse('Request body is required', 400);
                return;
            }

            $sql = "UPDATE achievements SET
                        name = COALESCE(:name, name),
                        description = COALESCE(:description, description),
                        icon = COALESCE(:icon, icon),
                        requirement_type = COALESCE(:requirement_type, requirement_type),
                        requirement_value = COALESCE(:requirement_value, requirement_value)
                    WHERE id = :id";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':name' => $input['name'] ?? null,
                ':description' => $input['description'] ?? null,
                ':icon' => $input['icon'] ?? null,
                ':requirement_type' => $input['requirementType'] ?? null,
                ':requirement_value' => $input['requirementValue'] ?? null,
                ':id' => $id,
            ]);

            $fetchStmt = $db->prepare("SELECT * FROM achievements WHERE id = :id");
            $fetchStmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $fetchStmt->execute();

            $this->sendSuccessResponse($this->formatAchievement($fetchStmt->fetch()));
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to update achievement: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/achievements/{id}
     */
    public function delete(array $vars = []): void
    {
        $this->requireAdmin();

        try {
            $id = (int) ($vars['id'] ?? 0);
            if ($id <= 0) {
                $this->sendErrorResponse('Invalid achievement ID', 400);
                return;
            }

            $db = Database::getConnection();

            $stmt = $db->prepare("DELETE FROM achievements WHERE id = :id");
            $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                $this->sendErrorResponse('Achievement not found', 404);
                return;
            }

            $this->sendSuccessResponse(['message' => 'Achievement deleted']);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to delete achievement: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/achievements/mine
     *
     * Returns all achievements with the authenticated user's unlock status.
     * Each achievement includes an 'unlocked' boolean and 'unlockedAt' timestamp.
     * Supports filtering: ?unlocked=true or ?unlocked=false
     */
    public function getMyAchievements(): void
    {
        $payload = $this->authenticate();
        $userId = (int) $payload->sub;

        try {
            $db = Database::getConnection();

            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int) ($_GET['limit'] ?? 50)));
            $offset = ($page - 1) * $limit;

            // Base query: LEFT JOIN to get unlock status per user
            $sql = "SELECT a.*, ua.unlocked_at
                    FROM achievements a
                    LEFT JOIN user_achievements ua
                        ON ua.achievement_id = a.id AND ua.user_id = :uid";

            $conditions = [];
            $params = [':uid' => $userId];

            // Filter by unlock status
            if (isset($_GET['unlocked'])) {
                if ($_GET['unlocked'] === 'true') {
                    $conditions[] = 'ua.id IS NOT NULL';
                } else {
                    $conditions[] = 'ua.id IS NULL';
                }
            }

            if (!empty($conditions)) {
                $sql .= ' WHERE ' . implode(' AND ', $conditions);
            }

            $sql .= " ORDER BY a.id ASC LIMIT :limit OFFSET :offset";

            $stmt = $db->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val, \PDO::PARAM_INT);
            }
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll();
            $achievements = array_map(function ($row) {
                $formatted = $this->formatAchievement($row);
                $formatted['unlocked'] = $row['unlocked_at'] !== null;
                $formatted['unlockedAt'] = $row['unlocked_at'];
                return $formatted;
            }, $rows);

            $this->sendSuccessResponse($achievements);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to fetch achievements: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/achievements/check
     *
     * Called after serving a bowl. Evaluates all achievement rules against the
     * user's history and the current bowl, awards any newly unlocked achievements.
     *
     * Expects JSON: { "ingredient_ids": [1, 9, 17], "total_score": 155, "bowl_id": 42 }
     * Returns: array of newly unlocked achievements
     */
    public function checkAchievements(): void
    {
        $payload = $this->authenticate();
        $userId = (int) $payload->sub;

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $ingredientIds = $input['ingredient_ids'] ?? [];
            $totalScore = (int) ($input['total_score'] ?? 0);

            $db = Database::getConnection();

            // Get all achievements the user hasn't unlocked yet
            $stmt = $db->prepare(
                "SELECT a.* FROM achievements a
                 WHERE a.id NOT IN (
                     SELECT achievement_id FROM user_achievements WHERE user_id = :uid
                 )"
            );
            $stmt->execute([':uid' => $userId]);
            $pending = $stmt->fetchAll();

            // Gather user stats for evaluation
            $stats = $this->gatherUserStats($db, $userId, $ingredientIds);

            $newlyUnlocked = [];

            foreach ($pending as $achievement) {
                if ($this->isAchievementEarned($achievement, $stats, $ingredientIds, $totalScore)) {
                    // Award it
                    $insert = $db->prepare(
                        "INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (:uid, :aid)"
                    );
                    $insert->execute([':uid' => $userId, ':aid' => $achievement['id']]);

                    $formatted = $this->formatAchievement($achievement);
                    $formatted['unlocked'] = true;
                    $formatted['unlockedAt'] = date('Y-m-d H:i:s');
                    $newlyUnlocked[] = $formatted;
                }
            }

            $this->sendSuccessResponse($newlyUnlocked);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to check achievements: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Gather stats needed for achievement evaluation.
     */
    private function gatherUserStats(\PDO $db, int $userId, array $currentIngredientIds): array
    {
        // Total bowls served
        $stmt = $db->prepare("SELECT COUNT(*) FROM served_bowls WHERE user_id = :uid");
        $stmt->execute([':uid' => $userId]);
        $bowlsServed = (int) $stmt->fetchColumn();

        // Unique broths used (category_id = 1)
        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT bi.ingredient_id)
             FROM bowl_ingredients bi
             JOIN served_bowls sb ON bi.bowl_id = sb.id
             JOIN ingredients i ON bi.ingredient_id = i.id
             WHERE sb.user_id = :uid AND i.category_id = 1"
        );
        $stmt->execute([':uid' => $userId]);
        $uniqueBroths = (int) $stmt->fetchColumn();

        // Unique noodles (category_id = 2)
        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT bi.ingredient_id)
             FROM bowl_ingredients bi
             JOIN served_bowls sb ON bi.bowl_id = sb.id
             JOIN ingredients i ON bi.ingredient_id = i.id
             WHERE sb.user_id = :uid AND i.category_id = 2"
        );
        $stmt->execute([':uid' => $userId]);
        $uniqueNoodles = (int) $stmt->fetchColumn();

        // Unique oils (category_id = 3)
        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT bi.ingredient_id)
             FROM bowl_ingredients bi
             JOIN served_bowls sb ON bi.bowl_id = sb.id
             JOIN ingredients i ON bi.ingredient_id = i.id
             WHERE sb.user_id = :uid AND i.category_id = 3"
        );
        $stmt->execute([':uid' => $userId]);
        $uniqueOils = (int) $stmt->fetchColumn();

        // Ajitama usage count (ingredient 19)
        $stmt = $db->prepare(
            "SELECT COUNT(*)
             FROM bowl_ingredients bi
             JOIN served_bowls sb ON bi.bowl_id = sb.id
             WHERE sb.user_id = :uid AND bi.ingredient_id = 19"
        );
        $stmt->execute([':uid' => $userId]);
        $ajitamaCount = (int) $stmt->fetchColumn();

        // Low score bowls (total_score < 30)
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM served_bowls WHERE user_id = :uid AND total_score < 30"
        );
        $stmt->execute([':uid' => $userId]);
        $lowScoreCount = (int) $stmt->fetchColumn();

        // Identical bowls: group by sorted ingredient set, find max count
        $stmt = $db->prepare(
            "SELECT COUNT(*) as cnt FROM served_bowls sb
             JOIN (
                 SELECT bowl_id, GROUP_CONCAT(ingredient_id ORDER BY ingredient_id) as combo
                 FROM bowl_ingredients GROUP BY bowl_id
             ) bi ON sb.id = bi.bowl_id
             WHERE sb.user_id = :uid
             GROUP BY bi.combo
             ORDER BY cnt DESC LIMIT 1"
        );
        $stmt->execute([':uid' => $userId]);
        $identicalBowls = (int) ($stmt->fetchColumn() ?: 0);

        // Get category IDs for current bowl ingredients
        $currentCategories = [];
        if (!empty($currentIngredientIds)) {
            $placeholders = implode(',', array_fill(0, count($currentIngredientIds), '?'));
            $stmt = $db->prepare(
                "SELECT id, category_id FROM ingredients WHERE id IN ({$placeholders})"
            );
            $stmt->execute(array_map('intval', $currentIngredientIds));
            foreach ($stmt->fetchAll() as $row) {
                $currentCategories[(int) $row['id']] = (int) $row['category_id'];
            }
        }

        return [
            'bowls_served'    => $bowlsServed,
            'unique_broths'   => $uniqueBroths,
            'unique_noodles'  => $uniqueNoodles,
            'unique_oils'     => $uniqueOils,
            'ajitama_count'   => $ajitamaCount,
            'low_score_count' => $lowScoreCount,
            'identical_bowls' => $identicalBowls,
            'current_categories' => $currentCategories,
        ];
    }

    /**
     * Check if a specific achievement has been earned.
     */
    private function isAchievementEarned(array $achievement, array $stats, array $ingredientIds, int $totalScore): bool
    {
        $type = $achievement['requirement_type'];
        $value = (int) ($achievement['requirement_value'] ?? 0);
        $name = $achievement['name'];
        $categories = $stats['current_categories'];

        switch ($type) {
            case 'bowls_served':
                return $stats['bowls_served'] >= $value;

            case 'unique_broths':
                return $stats['unique_broths'] >= $value;

            case 'unique_noodles':
                return $stats['unique_noodles'] >= $value;

            case 'unique_oils':
                return $stats['unique_oils'] >= $value;

            case 'score_threshold':
                return $totalScore >= $value;

            case 'ingredient_count':
                // "Egg is Life" — ajitama (19) used in N bowls
                return $stats['ajitama_count'] >= $value;

            case 'low_score_count':
                return $stats['low_score_count'] >= $value;

            case 'identical_bowls':
                return $stats['identical_bowls'] >= $value;

            case 'specific_combo':
                return $this->checkSpecificCombo($name, $ingredientIds, $categories);

            default:
                return false;
        }
    }

    /**
     * Check specific combo achievements based on the current bowl's ingredients.
     */
    private function checkSpecificCombo(string $achievementName, array $ids, array $categories): bool
    {
        // Helper: get IDs by category from current bowl
        $byCategory = [];
        foreach ($categories as $id => $catId) {
            $byCategory[$catId][] = $id;
        }

        $broths   = $byCategory[1] ?? [];
        $noodles  = $byCategory[2] ?? [];
        $oils     = $byCategory[3] ?? [];
        $proteins = $byCategory[4] ?? [];
        $toppings = $byCategory[5] ?? [];

        switch ($achievementName) {
            case 'Plant Power':
                // Veggie broth (8) + Seitan (20) or Cauliflower Tempura (22), no meat proteins
                $meatProteins = array_intersect($proteins, [17, 18, 21]); // chashu, chicken, karaage
                return in_array(8, $broths)
                    && (in_array(20, $proteins) || in_array(22, $proteins))
                    && empty($meatProteins);

            case 'Classic Tonkotsu':
                // Tonkotsu (1) + Pork Chashu (17) + Mayu (13)
                return in_array(1, $broths) && in_array(17, $proteins) && in_array(13, $oils);

            case 'Old School Tokyo':
                // Shoyu (2) + Thin Straight (9) + Nori (26) + Ajitama (19)
                return in_array(2, $broths) && in_array(9, $noodles)
                    && in_array(26, $toppings) && in_array(19, $proteins);

            case 'Spice Demon':
                // Tantan (5) + Chili Oil (12)
                return in_array(5, $broths) && in_array(12, $oils);

            case 'Triple Chicken Threat':
                // Tori Paitan (7) + Chicken Chashu (18) + Chicken Oil (15)
                return in_array(7, $broths) && in_array(18, $proteins) && in_array(15, $oils);

            case 'Oil Spill':
                // All 5 oils in one bowl (IDs 12-16)
                return count($oils) >= 5;

            case 'The Hypocrite':
                // Veggie broth (8) + Pork Chashu (17)
                return in_array(8, $broths) && in_array(17, $proteins);

            case 'Surf & Lard':
                // Ebi (6) + Back Fat (16)
                return in_array(6, $broths) && in_array(16, $oils);

            case 'Kitchen Sink':
                // All 7 toppings (IDs 23-29)
                return count($toppings) >= 7;

            case 'Sad Soup':
                // Broth + toppings, but no noodles and no protein
                return !empty($broths) && !empty($toppings)
                    && empty($noodles) && empty($proteins);

            case 'Naked Noodles':
                // Just broth + noodles, nothing else
                return !empty($broths) && !empty($noodles)
                    && empty($oils) && empty($proteins) && empty($toppings);

            case 'The Arsonist':
                // Tantan (5) + Chili Oil (12) + Mayu (13)
                return in_array(5, $broths) && in_array(12, $oils) && in_array(13, $oils);

            default:
                return false;
        }
    }

    /**
     * Convert DB row to camelCase format
     */
    private function formatAchievement(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'icon' => $row['icon'],
            'requirementType' => $row['requirement_type'],
            'requirementValue' => $row['requirement_value'] !== null ? (int) $row['requirement_value'] : null,
            'createdAt' => $row['created_at'],
        ];
    }
}
