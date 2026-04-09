<?php

namespace App\Controllers;

use App\Framework\Controller;
use App\Models\User;
use App\Repositories\UserRepository;

/**
 * LeaderboardController — HTTP layer for the public leaderboard.
 *
 * No SQL here. Player data comes from UserRepository.
 */
class LeaderboardController extends Controller
{
    /**
     * GET /api/leaderboard?search=&rank=&page=&limit=
     */
    public function getTopPlayers(): void
    {
        try {
            $search = $_GET['search'] ?? null;
            $rank   = $_GET['rank'] ?? null;
            $page   = max(1, (int) ($_GET['page'] ?? 1));
            $limit  = min(50, max(1, (int) ($_GET['limit'] ?? 10)));

            $repo    = new UserRepository();
            $players = $repo->findLeaderboard($search, $rank, $page, $limit);
            $total   = $repo->countLeaderboard($search, $rank);

            $position = ($page - 1) * $limit + 1;
            $data = [];
            foreach ($players as $user) {
                $data[] = $user->toLeaderboardArray($position++);
            }

            $this->sendSuccessResponse([
                'data'  => $data,
                'page'  => $page,
                'limit' => $limit,
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to fetch leaderboard', 500);
        }
    }
}
