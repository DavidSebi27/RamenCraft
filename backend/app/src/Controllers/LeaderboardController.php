<?php

namespace App\Controllers;

use App\Config\Database;
use App\Framework\Controller;

/**
 * LeaderboardController — handles the public leaderboard endpoint
 *
 * Endpoints:
 *   GET /api/leaderboard — top players ranked by XP
 */
class LeaderboardController extends Controller
{
    /**
     * GET /api/leaderboard
     *
     * Returns top players sorted by total_xp descending.
     * Query parameters:
     *   ?limit=10 — number of players to return (default: 10, max: 50)
     */
    public function getTopPlayers(): void
    {
        try {
            $db = Database::getConnection();

            $limit = min(50, max(1, (int) ($_GET['limit'] ?? 10)));

            $sql = "SELECT id, username, total_xp, current_rank
                    FROM users
                    WHERE role = 'player'
                    ORDER BY total_xp DESC, id ASC
                    LIMIT :limit";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll();

            $players = [];
            $rank = 1;
            foreach ($rows as $row) {
                $players[] = [
                    'rank' => $rank++,
                    'id' => (int) $row['id'],
                    'username' => $row['username'],
                    'totalXp' => (int) $row['total_xp'],
                    'currentRank' => $row['current_rank'],
                ];
            }

            $this->sendSuccessResponse($players);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to fetch leaderboard: ' . $e->getMessage(), 500);
        }
    }
}
