<?php

namespace App\Models;

/**
 * Ingredient model — represents a ramen ingredient (e.g., Tonkotsu broth, Thin noodles)
 *
 * Properties map to the `ingredients` database table columns.
 * Column names use snake_case in the database, but we convert to camelCase
 * in PHP for consistency with PHP conventions.
 */
class Ingredient
{
    public ?int $id;
    public int $categoryId;
    public string $name;
    public ?string $nameJp;
    public ?string $description;
    public ?string $spriteIcon;
    public ?string $spriteBowl;
    public ?float $caloriesPerServing;
    public ?float $proteinG;
    public ?float $fatG;
    public ?float $carbsG;
    public bool $isAvailable;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->categoryId = $data['category_id'] ?? 0;
        $this->name = $data['name'] ?? '';
        $this->nameJp = $data['name_jp'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->spriteIcon = $data['sprite_icon'] ?? null;
        $this->spriteBowl = $data['sprite_bowl'] ?? null;
        $this->caloriesPerServing = isset($data['calories_per_serving']) ? (float) $data['calories_per_serving'] : null;
        $this->proteinG = isset($data['protein_g']) ? (float) $data['protein_g'] : null;
        $this->fatG = isset($data['fat_g']) ? (float) $data['fat_g'] : null;
        $this->carbsG = isset($data['carbs_g']) ? (float) $data['carbs_g'] : null;
        $this->isAvailable = (bool) ($data['is_available'] ?? true);
    }
}
