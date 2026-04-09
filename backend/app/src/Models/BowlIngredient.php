<?php

namespace App\Models;

/**
 * BowlIngredient — lightweight model for an ingredient in a served bowl.
 *
 * Used by BowlRepository::loadIngredientsForBowl().
 * Properties match the joined query columns.
 */
class BowlIngredient
{
    public int $ingredient_id = 0;
    public string $name = '';
    public string $category_name = '';

    /**
     * Convert to camelCase array for JSON response.
     */
    public function toArray(): array
    {
        return [
            'ingredientId'  => (int) $this->ingredient_id,
            'name'          => $this->name,
            'categoryName'  => $this->category_name,
        ];
    }
}
