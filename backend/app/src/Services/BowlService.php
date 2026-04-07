<?php

namespace App\Services;

use App\Config\Database;
use App\Models\ServedBowl;
use App\Repositories\BowlRepository;
use App\Repositories\UserRepository;

/**
 * BowlService — business logic for serving and viewing bowls.
 *
 * Orchestrates ScoringService + BowlRepository + UserRepository
 * inside a single transaction. Controllers only call serve() and history().
 */
class BowlService
{
    private ScoringService $scoring;
    private BowlRepository $bowlRepo;
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->scoring = new ScoringService();
        $this->bowlRepo = new BowlRepository();
        $this->userRepo = new UserRepository();
    }

    /**
     * Serve a bowl: calculate scores, persist, update XP/rank.
     *
     * @return array  All data needed for the API response
     * @throws \InvalidArgumentException  If ingredient_ids is empty/invalid
     * @throws \Exception  On database failure (transaction rolled back)
     */
    public function serve(int $userId, array $ingredientIds): array
    {
        if (empty($ingredientIds)) {
            throw new \InvalidArgumentException('ingredient_ids is required and must be a non-empty array');
        }

        // Calculate scores server-side (client values are NEVER trusted)
        $scores = $this->scoring->calculate($ingredientIds);

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            // 1. Insert the bowl
            $bowlId = $this->bowlRepo->insertBowl(
                $userId,
                $scores['tastiness_score'],
                $scores['nutrition_score'],
                $scores['total_score'],
                $scores['xp_earned']
            );

            // 2. Insert ingredients
            $this->bowlRepo->insertBowlIngredients($bowlId, $ingredientIds);

            // 3. Update XP and rank
            $newTotalXp = $this->userRepo->addXp($userId, $scores['xp_earned']);
            $newRank = $this->calculateRank($newTotalXp);
            $this->userRepo->updateRank($userId, $newRank);

            $db->commit();

            return [
                'bowl_id'         => $bowlId,
                'tastiness_score' => $scores['tastiness_score'],
                'nutrition_score' => $scores['nutrition_score'],
                'total_score'     => $scores['total_score'],
                'xp_earned'       => $scores['xp_earned'],
                'total_xp'        => $newTotalXp,
                'current_rank'    => $newRank,
                'pairings_found'  => $scores['pairings_found'],
            ];
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Get a user's bowl history with pagination.
     *
     * @return array  ['data' => ServedBowl[], 'page', 'limit', 'total']
     */
    public function getHistory(int $userId, int $page, int $limit): array
    {
        $bowls = $this->bowlRepo->findByUser($userId, $page, $limit);
        $total = $this->bowlRepo->countByUser($userId);

        return [
            'data'  => array_map(fn(ServedBowl $b) => $b->toArray(), $bowls),
            'page'  => $page,
            'limit' => $limit,
            'total' => $total,
        ];
    }

    /**
     * Calculate rank from total XP.
     *
     * Pure function — easy to unit test.
     */
    private function calculateRank(int $totalXp): string
    {
        if ($totalXp >= 10000) return 'taisho';
        if ($totalXp >= 5000)  return 'shokunin';
        if ($totalXp >= 2000)  return 'tsuu';
        if ($totalXp >= 500)   return 'jouren';
        return 'minarai';
    }
}
