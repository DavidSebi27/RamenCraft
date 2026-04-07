<?php

namespace App\Repositories;

use App\Config\Database;
use App\Models\ServedBowl;

/**
 * BowlRepository — database queries for served bowls.
 *
 * Returns ServedBowl model objects.
 * The transaction itself is managed by BowlService since it spans
 * multiple writes (bowl + ingredients + user XP).
 */
class BowlRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Insert a new bowl row and return its ID.
     */
    public function insertBowl(int $userId, int $tastiness, int $nutrition, int $total, int $xp): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO served_bowls (user_id, tastiness_score, nutrition_score, total_score, xp_earned)
             VALUES (:user_id, :tastiness, :nutrition, :total, :xp)'
        );
        $stmt->execute([
            ':user_id'   => $userId,
            ':tastiness' => $tastiness,
            ':nutrition' => $nutrition,
            ':total'     => $total,
            ':xp'        => $xp,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Insert the ingredients for a served bowl.
     */
    public function insertBowlIngredients(int $bowlId, array $ingredientIds): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO bowl_ingredients (bowl_id, ingredient_id) VALUES (:bowl_id, :ingredient_id)'
        );
        foreach ($ingredientIds as $id) {
            $stmt->execute([':bowl_id' => $bowlId, ':ingredient_id' => (int) $id]);
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
            'SELECT * FROM served_bowls
             WHERE user_id = :uid
             ORDER BY served_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        /** @var ServedBowl[] $bowls */
        $bowls = $stmt->fetchAll(\PDO::FETCH_CLASS, ServedBowl::class);

        // Load ingredients for each bowl
        foreach ($bowls as $bowl) {
            $bowl->ingredients = $this->loadIngredientsForBowl((int) $bowl->id);
        }

        return $bowls;
    }

    /**
     * Count total bowls served by a user.
     */
    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM served_bowls WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Load ingredient details for a single bowl.
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
        $stmt->execute([':bowl_id' => $bowlId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
