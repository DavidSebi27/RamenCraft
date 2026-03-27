<?php

namespace App\Controllers;

use App\Config\Database;
use App\Framework\Controller;

/**
 * BowlController — handles serving bowls and viewing bowl history
 *
 * Endpoints:
 *   POST /api/bowls/serve  — serve a bowl (authenticated)
 *   GET  /api/bowls/history — get the current user's bowl history (authenticated)
 */
class BowlController extends Controller
{
    /**
     * POST /api/bowls/serve
     *
     * Expects JSON: {
     *   "ingredient_ids": [1, 9, 12, 17, 23],
     *   "tastiness_score": 85,
     *   "nutrition_score": 70,
     *   "total_score": 155,
     *   "xp_earned": 232
     * }
     *
     * Saves the bowl + ingredients, updates user XP + rank, returns result.
     */
    public function serve(): void
    {
        $payload = $this->authenticate();
        $userId = $payload->sub;

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validate required fields
            if (empty($input['ingredient_ids']) || !is_array($input['ingredient_ids'])) {
                $this->sendErrorResponse('ingredient_ids is required and must be an array', 400);
                return;
            }

            $ingredientIds = $input['ingredient_ids'];
            $tastinessScore = (int) ($input['tastiness_score'] ?? 0);
            $nutritionScore = (int) ($input['nutrition_score'] ?? 0);
            $totalScore = (int) ($input['total_score'] ?? 0);
            $xpEarned = (int) ($input['xp_earned'] ?? 0);

            // Cap XP per bowl to prevent cheating
            $xpEarned = min($xpEarned, 500);

            $db = Database::getConnection();
            $db->beginTransaction();

            // 1. Insert the served bowl
            $stmt = $db->prepare(
                'INSERT INTO served_bowls (user_id, tastiness_score, nutrition_score, total_score, xp_earned)
                 VALUES (:user_id, :tastiness, :nutrition, :total, :xp)'
            );
            $stmt->execute([
                ':user_id'   => $userId,
                ':tastiness' => $tastinessScore,
                ':nutrition' => $nutritionScore,
                ':total'     => $totalScore,
                ':xp'        => $xpEarned,
            ]);
            $bowlId = $db->lastInsertId();

            // 2. Insert bowl ingredients
            $ingredientStmt = $db->prepare(
                'INSERT INTO bowl_ingredients (bowl_id, ingredient_id) VALUES (:bowl_id, :ingredient_id)'
            );
            foreach ($ingredientIds as $ingredientId) {
                $ingredientStmt->execute([
                    ':bowl_id'       => $bowlId,
                    ':ingredient_id' => (int) $ingredientId,
                ]);
            }

            // 3. Update user XP
            $stmt = $db->prepare(
                'UPDATE users SET total_xp = total_xp + :xp WHERE id = :id'
            );
            $stmt->execute([':xp' => $xpEarned, ':id' => $userId]);

            // 4. Get updated user and calculate new rank
            $stmt = $db->prepare('SELECT total_xp FROM users WHERE id = :id');
            $stmt->execute([':id' => $userId]);
            $newTotalXp = (int) $stmt->fetchColumn();

            $newRank = $this->calculateRank($newTotalXp);

            // 5. Update rank in database
            $stmt = $db->prepare('UPDATE users SET current_rank = :rank WHERE id = :id');
            $stmt->execute([':rank' => $newRank, ':id' => $userId]);

            // 6. Find matching pairings for feedback
            $pairingsFound = $this->findMatchingPairings($db, $ingredientIds);

            $db->commit();

            $this->sendSuccessResponse([
                'bowl_id'        => (int) $bowlId,
                'tastiness_score' => $tastinessScore,
                'nutrition_score' => $nutritionScore,
                'total_score'    => $totalScore,
                'xp_earned'      => $xpEarned,
                'total_xp'       => $newTotalXp,
                'current_rank'   => $newRank,
                'pairings_found' => $pairingsFound,
            ], 201);

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->sendErrorResponse('Failed to serve bowl: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/bowls/history
     *
     * Returns the authenticated user's served bowls with pagination.
     * Query params: ?page=1&limit=10
     */
    public function history(): void
    {
        $payload = $this->authenticate();
        $userId = $payload->sub;

        try {
            $db = Database::getConnection();

            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int) ($_GET['limit'] ?? 10)));
            $offset = ($page - 1) * $limit;

            // Count total bowls
            $countStmt = $db->prepare('SELECT COUNT(*) FROM served_bowls WHERE user_id = :uid');
            $countStmt->execute([':uid' => $userId]);
            $total = (int) $countStmt->fetchColumn();

            // Fetch bowls
            $stmt = $db->prepare(
                'SELECT * FROM served_bowls WHERE user_id = :uid
                 ORDER BY served_at DESC LIMIT :limit OFFSET :offset'
            );
            $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            $bowls = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Fetch ingredients for each bowl
            $ingredientStmt = $db->prepare(
                'SELECT bi.ingredient_id, i.name, c.name AS category_name
                 FROM bowl_ingredients bi
                 JOIN ingredients i ON bi.ingredient_id = i.id
                 JOIN categories c ON i.category_id = c.id
                 WHERE bi.bowl_id = :bowl_id'
            );

            foreach ($bowls as &$bowl) {
                $ingredientStmt->execute([':bowl_id' => $bowl['id']]);
                $bowl['ingredients'] = $ingredientStmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            $this->sendSuccessResponse([
                'data'  => $bowls,
                'page'  => $page,
                'limit' => $limit,
                'total' => $total,
            ]);

        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to load history: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Calculate rank based on total XP.
     */
    private function calculateRank(int $totalXp): string
    {
        if ($totalXp >= 10000) return 'taisho';
        if ($totalXp >= 5000)  return 'shokunin';
        if ($totalXp >= 2000)  return 'tsuu';
        if ($totalXp >= 500)   return 'jouren';
        return 'minarai';
    }

    /**
     * Find pairings that match the given ingredient IDs.
     */
    private function findMatchingPairings(\PDO $db, array $ingredientIds): array
    {
        if (count($ingredientIds) < 2) return [];

        $placeholders = implode(',', array_fill(0, count($ingredientIds), '?'));

        $stmt = $db->prepare(
            "SELECT p.*, i1.name AS ingredient_1_name, i2.name AS ingredient_2_name
             FROM pairings p
             JOIN ingredients i1 ON p.ingredient_1_id = i1.id
             JOIN ingredients i2 ON p.ingredient_2_id = i2.id
             WHERE p.ingredient_1_id IN ({$placeholders})
               AND p.ingredient_2_id IN ({$placeholders})"
        );

        // Bind ingredient IDs twice (once for each IN clause)
        $params = array_merge(
            array_map('intval', $ingredientIds),
            array_map('intval', $ingredientIds)
        );
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
