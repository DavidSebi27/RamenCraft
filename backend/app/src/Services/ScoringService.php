<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\PairingGroup;
use App\Repositories\IngredientRepository;
use App\Repositories\PairingRepository;

/**
 * ScoringService — calculates tastiness, nutrition, and XP server-side.
 *
 * Works with typed Ingredient objects from the repository, not raw arrays.
 * Dependencies are injected through the constructor.
 */
class ScoringService
{
    private IngredientRepository $ingredientRepo;
    private PairingRepository $pairingRepo;

    public function __construct(
        ?IngredientRepository $ingredientRepo = null,
        ?PairingRepository $pairingRepo = null
    ) {
        $this->ingredientRepo = $ingredientRepo ?? new IngredientRepository();
        $this->pairingRepo = $pairingRepo ?? new PairingRepository();
    }

    /**
     * Calculate all scores for a bowl based on ingredient IDs only.
     */
    public function calculate(array $ingredientIds): array
    {
        /** @var Ingredient[] $ingredients */
        $ingredients = $this->ingredientRepo->findByIds($ingredientIds);

        $pairings = $this->pairingRepo->findMatchingForIngredients($ingredientIds);

        $tastiness = $this->calculateTastiness($ingredients, $pairings);
        $nutrition = $this->calculateNutrition($ingredients);

        $totalScore = $tastiness + $nutrition;
        $xpEarned = min($totalScore, 500);

        return [
            'tastiness_score'  => $tastiness,
            'nutrition_score'  => $nutrition,
            'total_score'      => $totalScore,
            'xp_earned'        => $xpEarned,
            'pairings_found'   => array_map(fn(PairingGroup $p) => $p->toArray(), $pairings),
        ];
    }

    /**
     * Calculate tastiness score from typed Ingredient and PairingGroup objects.
     *
     * @param Ingredient[] $ingredients
     * @param PairingGroup[] $pairings
     */
    private function calculateTastiness(array $ingredients, array $pairings): int
    {
        $score = count($ingredients) * 10;

        $categories = [];
        foreach ($ingredients as $ing) {
            if ($ing->category_name) $categories[$ing->category_name] = true;
        }
        $score += count($categories) * 5;

        foreach ($pairings as $pairing) {
            $score += $pairing->score_modifier;
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

        // Protein (0-30)
        if ($totalProtein >= 25) $score += 30;
        elseif ($totalProtein >= 15) $score += 20;
        elseif ($totalProtein >= 8) $score += 10;

        // Calorie balance (0-30)
        if ($totalCalories >= 300 && $totalCalories <= 800) $score += 30;
        elseif ($totalCalories >= 200 && $totalCalories <= 1000) $score += 20;
        elseif ($totalCalories > 0) $score += 10;

        // Toppings (0-20)
        if ($toppingCount >= 3) $score += 20;
        elseif ($toppingCount >= 2) $score += 15;
        elseif ($toppingCount >= 1) $score += 10;

        // Completeness (0-20)
        $catCount = count($categoriesUsed);
        if ($catCount >= 5) $score += 20;
        elseif ($catCount >= 4) $score += 10;

        return $score;
    }
}
