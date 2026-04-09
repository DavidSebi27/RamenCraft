<?php

namespace App\Models;

/**
 * Pairing model — represents an ingredient combo scoring rule.
 *
 * Properties use snake_case to match DB columns for PDO::FETCH_CLASS.
 */
class Pairing
{
    public ?int $id = null;
    public int $ingredient_1_id = 0;
    public int $ingredient_2_id = 0;
    public int $score_modifier = 0;
    public ?string $combo_name = null;
    public ?string $description = null;

    // Joined fields
    public ?string $ingredient_1_name = null;
    public ?string $ingredient_2_name = null;

    /**
     * Convert to camelCase array for JSON response.
     */
    public function toArray(): array
    {
        return [
            'id' => (int) $this->id,
            'ingredient1Id' => (int) $this->ingredient_1_id,
            'ingredient2Id' => (int) $this->ingredient_2_id,
            'scoreModifier' => (int) $this->score_modifier,
            'comboName' => $this->combo_name,
            'description' => $this->description,
            'ingredient1Name' => $this->ingredient_1_name,
            'ingredient2Name' => $this->ingredient_2_name,
        ];
    }
}
