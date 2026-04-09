<?php

namespace App\Models;

/**
 * Achievement model — represents an achievement definition.
 *
 * Properties match the achievements DB columns for PDO::FETCH_CLASS.
 */
class Achievement
{
    public ?int $id = null;
    public string $name = '';
    public ?string $description = null;
    public ?string $icon = null;
    public ?string $requirement_type = null;
    public ?int $requirement_value = null;
    public ?string $created_at = null;

    // Joined field from user_achievements (for /mine endpoint)
    public ?string $unlocked_at = null;

    /**
     * Convert to camelCase array for JSON response.
     */
    public function toArray(): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'requirementType' => $this->requirement_type,
            'requirementValue' => $this->requirement_value !== null ? (int) $this->requirement_value : null,
            'createdAt' => $this->created_at,
        ];
    }

    /**
     * Convert with unlock status (for /mine endpoint).
     */
    public function toArrayWithStatus(): array
    {
        $arr = $this->toArray();
        $arr['unlocked'] = $this->unlocked_at !== null;
        $arr['unlockedAt'] = $this->unlocked_at;
        return $arr;
    }
}
