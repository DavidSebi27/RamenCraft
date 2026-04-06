<?php

namespace App\Services;

use App\Config\Database;
use App\Models\Ingredient;
use App\Repositories\IngredientRepository;

/**
 * ScoringService — calculates tastiness, nutrition, and XP server-side.
 *
 * Works with typed Ingredient objects from the repository, not raw arrays.
 * Client-provided scores are ignored — everything is recalculated here.
 */
class ScoringService
{
    private IngredientRepository $ingredientRepo;

    public function __construct()
    {
        $this->ingredientRepo = new IngredientRepository();
    }

    /**
     * Calculate all scores for a bowl based on ingredient IDs only.
     */
    public function calculate(array $ingredientIds): array
    {
        // Fetch typed Ingredient objects from the repository
        $ingredients = $this->ingredientRepo->findByIds($ingredientIds);

        // Fetch matching pairings (still raw arrays — pairings don't have a model yet)
        $pairings = $this->findMatchingPairings(Database::getConnection(), $ingredientIds);

        $tastiness = $this->calculateTastiness($ingredients, $pairings);
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
     * Calculate tastiness score from typed Ingredient objects.
     *
     * @param Ingredient[] $ingredients
     * @param array $pairings  Grouped pairing arrays from findMatchingPairings()
     */
    private function calculateTastiness(array $ingredients, array $pairings): int
    {
        // Base: 10 per ingredient
        $score = count($ingredients) * 10;

        // Variety bonus: +5 per unique category
        $categories = [];
        foreach ($ingredients as $ing) {
            if ($ing->category_name) $categories[$ing->category_name] = true;
        }
        $score += count($categories) * 5;

        // Pairing bonuses
        foreach ($pairings as $pairing) {
            $score += (int) $pairing['score_modifier'];
        }

        return max(0, $score);
    }

    /**
     * Calculate nutrition score from typed Ingredient objects.
     *
     * @param Ingredient[] $ingredients
     */
    private function calculateNutrition(array $ingredients): int
    {
        $score = 0;
        $totalCalories = 0;
        $totalProtein = 0;
        $toppingCount = 0;
        $categoriesUsed = [];

        foreach ($ingredients as $ing) {
            $totalCalories += $ing->calories_per_serving ?? 0;
            $totalProtein += $ing->protein_g ?? 0;

            if ($ing->category_name) {
                $categoriesUsed[$ing->category_name] = true;
                if ($ing->category_name === 'topping') {
                    $toppingCount++;
                }
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
