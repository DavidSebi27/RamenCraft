<?php

namespace App\Models;

/**
 * Ingredient model — represents a ramen ingredient.
 *
 * Properties use snake_case to match DB columns directly,
 * so PDO::FETCH_CLASS can hydrate them without manual mapping.
 * toArray() converts to camelCase for the JSON API.
 */
class Ingredient
{
    public ?int $id = null;
    public int $category_id = 0;
    public string $name = '';
    public ?string $name_jp = null;
    public ?string $description = null;
    public ?string $sprite_icon = null;
    public ?string $sprite_bowl = null;
    public ?float $calories_per_serving = null;
    public ?float $protein_g = null;
    public ?float $fat_g = null;
    public ?float $carbs_g = null;
    public int $is_available = 1;
    public ?string $created_at = null;

    // Joined field — not always present
    public ?string $category_name = null;

    /**
     * Convert to camelCase array for JSON response.
     */
    public function toArray(): array
    {
        return [
            'id' => (int) $this->id,
            'categoryId' => (int) $this->category_id,
            'categoryName' => $this->category_name,
            'name' => $this->name,
            'nameJp' => $this->name_jp,
            'description' => $this->description,
            'spriteIcon' => $this->sprite_icon,
            'spriteBowl' => $this->sprite_bowl,
            'caloriesPerServing' => $this->calories_per_serving !== null ? (float) $this->calories_per_serving : null,
            'proteinG' => $this->protein_g !== null ? (float) $this->protein_g : null,
            'fatG' => $this->fat_g !== null ? (float) $this->fat_g : null,
            'carbsG' => $this->carbs_g !== null ? (float) $this->carbs_g : null,
            'isAvailable' => (bool) $this->is_available,
        ];
    }
}
