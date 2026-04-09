<?php

namespace App\Models;

/**
 * Favorite model — represents a saved bowl configuration.
 */
class Favorite
{
    public ?int $id = null;
    public int $user_id = 0;
    public ?string $name = null;
    public ?string $created_at = null;

    /** @var FavoriteIngredient[] Populated by repository */
    public array $ingredients = [];

    /**
     * Convert to camelCase array for JSON response.
     */
    public function toArray(): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'createdAt' => $this->created_at,
            'ingredients' => array_map(
                fn(FavoriteIngredient $i) => $i->toArray(),
                $this->ingredients
            ),
        ];
    }
}
