<?php

namespace App\Models;

/**
 * Category model — represents an ingredient category.
 *
 * Properties use snake_case to match DB columns for PDO::FETCH_CLASS.
 */
class Category
{
    public ?int $id = null;
    public string $name = '';
    public string $display_name = '';
    public int $sort_order = 0;

    /**
     * Convert to camelCase array for JSON response.
     */
    public function toArray(): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'displayName' => $this->display_name,
            'sortOrder' => (int) $this->sort_order,
        ];
    }
}
