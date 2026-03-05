<?php

namespace App\Models;

/**
 * Category model — represents an ingredient category (broth, noodles, oil, etc.)
 *
 * Properties map directly to the `categories` database table columns.
 * The constructor accepts an associative array (like a database row)
 * and maps the values to the object properties.
 */
class Category
{
    public ?int $id;
    public string $name;
    public string $displayName;
    public int $sortOrder;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? '';
        $this->displayName = $data['display_name'] ?? '';
        $this->sortOrder = $data['sort_order'] ?? 0;
    }
}
