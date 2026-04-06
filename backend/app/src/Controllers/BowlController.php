<?php

namespace App\Controllers;

use App\Config\Database;
use App\Framework\Controller;
use App\Services\ScoringService;

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
     * Expects JSON: { "ingredient_ids": [1, 9, 12, 17, 23] }
     *
     * Scores are calculated entirely server-side by ScoringService.
     * Client-provided scores are ignored to prevent cheating.
     */
    public function serve(): void
    {
        $payload = $this->authenticate();
        $userId = (int) $payload->sub;

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $ingredientIds = $this->validateServeInput($input);

            // Calculate scores server-side (client scores are ignored)
            $scoring = new ScoringService();
            $scores = $scoring->calculate($ingredientIds);

            // Persist bowl, update XP/rank
            $db = Database::getConnection();
            $db->beginTransaction();

            $bowlId = $this->insertBowl($db, $userId, $ingredientIds, $scores);
            $xpResult = $this->updateUserXp($db, $userId, $scores['xp_earned']);

            $db->commit();

            $this->sendSuccessResponse([
                'bowl_id'         => $bowlId,
                'tastiness_score' => $scores['tastiness_score'],
                'nutrition_score' => $scores['nutrition_score'],
                'total_score'     => $scores['total_score'],
                'xp_earned'       => $scores['xp_earned'],
                'total_xp'        => $xpResult['total_xp'],
                'current_rank'    => $xpResult['current_rank'],
                'pairings_found'  => $scores['pairings_found'],
            ], 201);

        } catch (\Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $this->sendErrorResponse('Failed to serve bowl: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Validate serve input — only ingredient_ids is required.
     */
    private function validateServeInput(?array $input): array
    {
        if (empty($input['ingredient_ids']) || !is_array($input['ingredient_ids'])) {
            throw new \InvalidArgumentException('ingredient_ids is required and must be an array');
        }
        return array_map('intval', $input['ingredient_ids']);
    }

    /**
     * Insert bowl + ingredients into the database.
     */
    private function insertBowl(\PDO $db, int $userId, array $ingredientIds, array $scores): int
    {
        $stmt = $db->prepare(
            'INSERT INTO served_bowls (user_id, tastiness_score, nutrition_score, total_score, xp_earned)
             VALUES (:user_id, :tastiness, :nutrition, :total, :xp)'
        );
        $stmt->execute([
            ':user_id'   => $userId,
            ':tastiness' => $scores['tastiness_score'],
            ':nutrition' => $scores['nutrition_score'],
            ':total'     => $scores['total_score'],
            ':xp'        => $scores['xp_earned'],
        ]);
        $bowlId = (int) $db->lastInsertId();

        $ingredientStmt = $db->prepare(
            'INSERT INTO bowl_ingredients (bowl_id, ingredient_id) VALUES (:bowl_id, :ingredient_id)'
        );
        foreach ($ingredientIds as $id) {
            $ingredientStmt->execute([':bowl_id' => $bowlId, ':ingredient_id' => $id]);
        }

        return $bowlId;
    }

    /**
     * Update user XP and recalculate rank.
     */
    private function updateUserXp(\PDO $db, int $userId, int $xpEarned): array
    {
        $db->prepare('UPDATE users SET total_xp = total_xp + :xp WHERE id = :id')
           ->execute([':xp' => $xpEarned, ':id' => $userId]);

        $stmt = $db->prepare('SELECT total_xp FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $newTotalXp = (int) $stmt->fetchColumn();

        $newRank = $this->calculateRank($newTotalXp);

        $db->prepare('UPDATE users SET current_rank = :rank WHERE id = :id')
           ->execute([':rank' => $newRank, ':id' => $userId]);

        return ['total_xp' => $newTotalXp, 'current_rank' => $newRank];
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

}
