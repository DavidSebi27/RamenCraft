<?php

namespace App\Models;

/**
 * ServedBowl model — represents one bowl serving in history.
 *
 * Properties match served_bowls DB columns for PDO::FETCH_CLASS.
 * Ingredients are loaded separately by the repository as BowlIngredient[].
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

    /** @var BowlIngredient[] Populated by repository */
    public array $ingredients = [];

    /**
     * Convert to camelCase array for JSON response.
     * No duplicate snake_case keys — frontend uses camelCase only.
     */
    public function toArray(): array
    {
        return [
            'id'              => (int) $this->id,
            'userId'          => (int) $this->user_id,
            'tastinessScore'  => (int) $this->tastiness_score,
            'nutritionScore'  => (int) $this->nutrition_score,
            'totalScore'      => (int) $this->total_score,
            'xpEarned'        => (int) $this->xp_earned,
            'servedAt'        => $this->served_at,
            'ingredients'     => array_map(
                fn(BowlIngredient $i) => $i->toArray(),
                $this->ingredients
            ),
        ];
    }
}
