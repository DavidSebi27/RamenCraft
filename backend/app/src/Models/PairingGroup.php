<?php

namespace App\Models;

/**
 * PairingGroup — represents a grouped combo result from pairing matching.
 *
 * Multiple pairings with the same combo_name are merged into one group
 * with a summed score_modifier and a list of ingredient pairs.
 */
class PairingGroup
{
    public string $combo_name;
    public int $score_modifier = 0;
    /** @var string[] */
    public array $pairs = [];

    public function __construct(string $comboName)
    {
        $this->combo_name = $comboName;
    }

    /**
     * Add a pairing's modifier and ingredient pair to this group.
     */
    public function addPair(int $modifier, string $ingredient1, string $ingredient2): void
    {
        $this->score_modifier += $modifier;
        $this->pairs[] = $ingredient1 . ' + ' . $ingredient2;
    }

    /**
     * Convert to camelCase array for JSON response.
     */
    public function toArray(): array
    {
        return [
            'comboName'     => $this->combo_name,
            'scoreModifier' => $this->score_modifier,
            'pairs'         => $this->pairs,
        ];
    }
}
