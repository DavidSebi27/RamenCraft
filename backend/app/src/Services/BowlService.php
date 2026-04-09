<?php

namespace App\Services;

use App\Config\Database;
use App\Models\ServedBowl;
use App\Repositories\BowlRepository;
use App\Repositories\UserRepository;

/**
 * BowlService — business logic for serving and viewing bowls.
 *
 * Orchestrates ScoringService + BowlRepository + UserRepository.
 * Dependencies injected through constructor for testability.
 */
class BowlService
{
    private ScoringService $scoring;
    private BowlRepository $bowlRepo;
    private UserRepository $userRepo;

    public function __construct(
        ?ScoringService $scoring = null,
        ?BowlRepository $bowlRepo = null,
        ?UserRepository $userRepo = null
    ) {
        $this->scoring = $scoring ?? new ScoringService();
        $this->bowlRepo = $bowlRepo ?? new BowlRepository();
        $this->userRepo = $userRepo ?? new UserRepository();
    }

    /**
     * Serve a bowl: calculate scores, persist, update XP/rank.
     *
     * @throws \InvalidArgumentException  If ingredient_ids is empty
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
            $bowlId = $this->bowlRepo->insertBowl(
                $userId,
                $scores['tastiness_score'],
                $scores['nutrition_score'],
                $scores['total_score'],
                $scores['xp_earned']
            );

            $this->bowlRepo->insertBowlIngredients($bowlId, $ingredientIds);

            $newTotalXp = $this->userRepo->addXp($userId, $scores['xp_earned']);
            $newRank = self::calculateRank($newTotalXp);
            $this->userRepo->updateRank($userId, $newRank);

            $db->commit();

            return [
                'bowlId'         => $bowlId,
                'tastinessScore' => $scores['tastiness_score'],
                'nutritionScore' => $scores['nutrition_score'],
                'totalScore'     => $scores['total_score'],
                'xpEarned'       => $scores['xp_earned'],
                'totalXp'        => $newTotalXp,
                'currentRank'    => $newRank,
                'pairingsFound'  => $scores['pairings_found'],
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
     * Calculate rank from total XP. Static so it's easy to unit test.
     */
    public static function calculateRank(int $totalXp): string
    {
        if ($totalXp >= 10000) return 'taisho';
        if ($totalXp >= 5000)  return 'shokunin';
        if ($totalXp >= 2000)  return 'tsuu';
        if ($totalXp >= 500)   return 'jouren';
        return 'minarai';
    }
}
