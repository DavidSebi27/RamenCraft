<?php

namespace App\Models;

/**
 * ServedBowl model — represents one bowl serving in history.
 *
 * Properties match the served_bowls DB columns for PDO::FETCH_CLASS.
 * Joined ingredients are loaded separately by the repository.
 */
class ServedBowl
{
    public ?int $id = null;
    public int $user_id = 0;
    public int $tastiness_score = 0;
    public int $nutrition_score = 0;
    public int $total_score = 0;
    public int $xp_earned = 0;
    public ?string $served_at = null;

    /** @var array Ingredient names with categories — populated by repository */
    public array $ingredients = [];

    /**
     * Convert to camelCase array for JSON response.
     */
    public function toArray(): array
    {
        return [
            'id' => (int) $this->id,
            'userId' => (int) $this->user_id,
            'tastinessScore' => (int) $this->tastiness_score,
            'nutritionScore' => (int) $this->nutrition_score,
            'totalScore' => (int) $this->total_score,
            'xpEarned' => (int) $this->xp_earned,
            'servedAt' => $this->served_at,
            'ingredients' => $this->ingredients,
            // Legacy snake_case fields for frontend compatibility
            'tastiness_score' => (int) $this->tastiness_score,
            'nutrition_score' => (int) $this->nutrition_score,
            'total_score' => (int) $this->total_score,
            'xp_earned' => (int) $this->xp_earned,
            'served_at' => $this->served_at,
        ];
    }
}
