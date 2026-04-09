<?php

namespace App\Repositories;

use App\Config\Database;
use App\Models\BowlIngredient;
use App\Models\ServedBowl;

/**
 * BowlRepository — owns ALL SQL for served bowls and bowl ingredients.
 *
 * Returns typed ServedBowl objects with BowlIngredient[] arrays.
 */
class BowlRepository
{
    private \PDO $db;

    public function __construct(?\PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Insert a new bowl row and return its ID.
     *
     * @return int  The new bowl ID
     */
    public function insertBowl(int $userId, int $tastiness, int $nutrition, int $total, int $xp): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO served_bowls (user_id, tastiness_score, nutrition_score, total_score, xp_earned)
             VALUES (:user_id, :tastiness, :nutrition, :total, :xp)'
        );
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':tastiness', $tastiness, \PDO::PARAM_INT);
        $stmt->bindValue(':nutrition', $nutrition, \PDO::PARAM_INT);
        $stmt->bindValue(':total', $total, \PDO::PARAM_INT);
        $stmt->bindValue(':xp', $xp, \PDO::PARAM_INT);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    /**
     * Insert the ingredients for a served bowl.
     *
     * @param int $bowlId
     * @param int[] $ingredientIds
     */
    public function insertBowlIngredients(int $bowlId, array $ingredientIds): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO bowl_ingredients (bowl_id, ingredient_id) VALUES (:bowl_id, :ingredient_id)'
        );
        foreach ($ingredientIds as $id) {
            $stmt->bindValue(':bowl_id', $bowlId, \PDO::PARAM_INT);
            $stmt->bindValue(':ingredient_id', (int) $id, \PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    /**
     * Find a user's bowl history with pagination.
     *
     * @return ServedBowl[]
     */
    public function findByUser(int $userId, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;

        $stmt = $this->db->prepare(
            'SELECT sb.id, sb.user_id, sb.tastiness_score, sb.nutrition_score,
                    sb.total_score, sb.xp_earned, sb.served_at
             FROM served_bowls sb
             WHERE sb.user_id = :uid
             ORDER BY sb.served_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        /** @var ServedBowl[] $bowls */
        $bowls = $stmt->fetchAll(\PDO::FETCH_CLASS, ServedBowl::class);

        // Load typed BowlIngredient objects for each bowl
        foreach ($bowls as $bowl) {
            $bowl->ingredients = $this->loadIngredientsForBowl((int) $bowl->id);
        }

        return $bowls;
    }

    /**
     * Count total bowls served by a user.
     *
     * @return int
     */
    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM served_bowls WHERE user_id = :uid');
        $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Load typed BowlIngredient objects for a single bowl.
     *
     * @return BowlIngredient[]
     */
    private function loadIngredientsForBowl(int $bowlId): array
    {
        $stmt = $this->db->prepare(
            'SELECT bi.ingredient_id, i.name, c.name AS category_name
             FROM bowl_ingredients bi
             JOIN ingredients i ON bi.ingredient_id = i.id
             JOIN categories c ON i.category_id = c.id
             WHERE bi.bowl_id = :bowl_id'
        );
        $stmt->bindValue(':bowl_id', $bowlId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_CLASS, BowlIngredient::class);
    }
}
