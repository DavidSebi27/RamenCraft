<?php

namespace App\Services;

use App\Config\Database;

/**
 * ScoringService — calculates tastiness, nutrition, and XP server-side.
 *
 * This ensures scores cannot be spoofed by the client.
 * The frontend still calculates scores for instant UI feedback,
 * but the server recalculates everything from ingredient_ids alone.
 */
class ScoringService
{
    /**
     * Calculate all scores for a bowl based on ingredient IDs only.
     *
     * @param array $ingredientIds  Array of ingredient IDs in the bowl
     * @return array  [tastiness, nutrition, totalScore, xpEarned, pairingsFound]
     */
    public function calculate(array $ingredientIds): array
    {
        $db = Database::getConnection();

        // Fetch ingredient data
        $ingredients = $this->fetchIngredients($db, $ingredientIds);

        // Fetch matching pairings
        $pairings = $this->findMatchingPairings($db, $ingredientIds);

        // Calculate tastiness
        $tastiness = $this->calculateTastiness($ingredients, $pairings);

        // Calculate nutrition
        $nutrition = $this->calculateNutrition($ingredients);

        $totalScore = $tastiness + $nutrition;
        $xpEarned = min($totalScore, 500); // Cap XP per bowl

        return [
            'tastiness_score'  => $tastiness,
            'nutrition_score'  => $nutrition,
            'total_score'      => $totalScore,
            'xp_earned'        => $xpEarned,
            'pairings_found'   => $pairings,
        ];
    }

    /**
     * Fetch ingredient rows for the given IDs.
     */
    private function fetchIngredients(\PDO $db, array $ids): array
    {
        if (empty($ids)) return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare(
            "SELECT i.*, c.name AS category_name
             FROM ingredients i
             JOIN categories c ON i.category_id = c.id
             WHERE i.id IN ({$placeholders})"
        );
        $stmt->execute(array_map('intval', $ids));
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Calculate tastiness score.
     *
     * Base: 10 points per ingredient.
     * Variety: +5 per category used.
     * Pairings: sum of all matching pairing modifiers.
     */
    private function calculateTastiness(array $ingredients, array $pairings): int
    {
        // Base score: 10 per ingredient
        $score = count($ingredients) * 10;

        // Variety bonus: +5 per unique category
        $categories = array_unique(array_column($ingredients, 'category_name'));
        $score += count($categories) * 5;

        // Pairing bonuses (already grouped)
        foreach ($pairings as $pairing) {
            $score += (int) $pairing['score_modifier'];
        }

        return max(0, $score);
    }

    /**
     * Calculate nutrition score based on macronutrient balance.
     *
     * Protein adequacy (0-30), calorie balance (0-30),
     * veggie/topping bonus (0-20), completeness (0-20).
     */
    private function calculateNutrition(array $ingredients): int
    {
        $score = 0;
        $totalCalories = 0;
        $totalProtein = 0;
        $toppingCount = 0;
        $categoriesUsed = [];

        foreach ($ingredients as $ing) {
            $totalCalories += (float) ($ing['calories_per_serving'] ?? 0);
            $totalProtein += (float) ($ing['protein_g'] ?? 0);

            $cat = $ing['category_name'] ?? '';
            $categoriesUsed[$cat] = true;

            if ($cat === 'topping') {
                $toppingCount++;
            }
        }

        // Protein score (0-30)
        if ($totalProtein >= 25) $score += 30;
        elseif ($totalProtein >= 15) $score += 20;
        elseif ($totalProtein >= 8) $score += 10;

        // Calorie balance (0-30) — ideal range 300-800
        if ($totalCalories >= 300 && $totalCalories <= 800) $score += 30;
        elseif ($totalCalories >= 200 && $totalCalories <= 1000) $score += 20;
        elseif ($totalCalories > 0) $score += 10;

        // Veggie/topping bonus (0-20)
        if ($toppingCount >= 3) $score += 20;
        elseif ($toppingCount >= 2) $score += 15;
        elseif ($toppingCount >= 1) $score += 10;

        // Completeness: having all 5 categories (0-20)
        $catCount = count($categoriesUsed);
        if ($catCount >= 5) $score += 20;
        elseif ($catCount >= 4) $score += 10;

        return $score;
    }

    /**
     * Find and group matching pairings for the given ingredient IDs.
     */
    private function findMatchingPairings(\PDO $db, array $ingredientIds): array
    {
        if (count($ingredientIds) < 2) return [];

        $placeholders = implode(',', array_fill(0, count($ingredientIds), '?'));

        $stmt = $db->prepare(
            "SELECT p.combo_name, p.score_modifier, p.description,
                    i1.name AS ingredient_1_name, i2.name AS ingredient_2_name
             FROM pairings p
             JOIN ingredients i1 ON p.ingredient_1_id = i1.id
             JOIN ingredients i2 ON p.ingredient_2_id = i2.id
             WHERE p.ingredient_1_id IN ({$placeholders})
               AND p.ingredient_2_id IN ({$placeholders})"
        );

        $params = array_merge(
            array_map('intval', $ingredientIds),
            array_map('intval', $ingredientIds)
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Group by combo_name
        $grouped = [];
        foreach ($rows as $row) {
            $name = $row['combo_name'];
            if (!isset($grouped[$name])) {
                $grouped[$name] = [
                    'combo_name' => $name,
                    'score_modifier' => 0,
                    'pairs' => [],
                ];
            }
            $grouped[$name]['score_modifier'] += (int) $row['score_modifier'];
            $grouped[$name]['pairs'][] = $row['ingredient_1_name'] . ' + ' . $row['ingredient_2_name'];
        }

        return array_values($grouped);
    }
}
