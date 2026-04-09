<?php

namespace App\Models;

/**
 * FavoriteIngredient — lightweight model for an ingredient in a saved bowl.
 */
class FavoriteIngredient
{
    public int $ingredient_id = 0;
    public string $name = '';
    public ?string $name_jp = null;
    public string $category_name = '';

    /**
     * Convert to camelCase array for JSON response.
     */
    public function toArray(): array
    {
        return [
            'id'       => (int) $this->ingredient_id,
            'name'     => $this->name,
            'nameJp'   => $this->name_jp,
            'category' => $this->category_name,
        ];
    }
}
