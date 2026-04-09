<?php

namespace App\Models;

/**
 * User model — represents a player or admin account.
 *
 * password_hash is never exposed via toArray().
 */
class User
{
    public ?int $id = null;
    public string $username = '';
    public string $email = '';
    public ?string $password_hash = null;
    public string $role = 'player';
    public int $total_xp = 0;
    public string $current_rank = 'minarai';
    public ?string $created_at = null;
    public ?string $updated_at = null;

    // Joined field from /auth/me
    public ?int $bowls_served = null;

    /**
     * Convert to camelCase array for JSON response.
     * Never exposes password_hash.
     */
    public function toArray(): array
    {
        $arr = [
            'id' => (int) $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'totalXp' => (int) $this->total_xp,
            'currentRank' => $this->current_rank,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];

        if ($this->bowls_served !== null) {
            $arr['bowlsServed'] = (int) $this->bowls_served;
        }

        return $arr;
    }

    /**
     * Convert to leaderboard entry with position number.
     */
    public function toLeaderboardArray(int $position): array
    {
        return [
            'rank'        => $position,
            'id'          => (int) $this->id,
            'username'    => $this->username,
            'totalXp'     => (int) $this->total_xp,
            'currentRank' => $this->current_rank,
        ];
    }
}
