<?php

namespace App\Services;

use App\Models\Achievement;
use App\Repositories\AchievementRepository;

/**
 * AchievementService — business logic for achievement evaluation.
 *
 * Owns: checkAchievements(), isAchievementEarned(), checkSpecificCombo().
 * Controllers call this, never evaluate achievements directly.
 */
class AchievementService
{
    private AchievementRepository $repo;

    public function __construct(?AchievementRepository $repo = null)
    {
        $this->repo = $repo ?? new AchievementRepository();
    }

    /**
     * Check and award any newly earned achievements after serving a bowl.
     *
     * @param int $userId
     * @param int[] $ingredientIds  Ingredients in the just-served bowl
     * @param int $totalScore  Total score of the just-served bowl
     * @return Achievement[]  Newly unlocked achievements
     */
    public function checkAfterServe(int $userId, array $ingredientIds, int $totalScore): array
    {
        $pending = $this->repo->findPendingForUser($userId);
        $stats = $this->repo->gatherUserStats($userId, $ingredientIds);

        $newlyUnlocked = [];

        foreach ($pending as $achievement) {
            if ($this->isAchievementEarned($achievement, $stats, $ingredientIds, $totalScore)) {
                $this->repo->awardToUser($userId, (int) $achievement->id);
                $achievement->unlocked_at = date('Y-m-d H:i:s');
                $newlyUnlocked[] = $achievement;
            }
        }

        return $newlyUnlocked;
    }

    /**
     * Check if a specific achievement has been earned.
     */
    private function isAchievementEarned(Achievement $achievement, array $stats, array $ingredientIds, int $totalScore): bool
    {
        $type = $achievement->requirement_type;
        $value = (int) ($achievement->requirement_value ?? 0);
        $name = $achievement->name;
        $categories = $stats['current_categories'];

        switch ($type) {
            case 'bowls_served':
                return $stats['bowls_served'] >= $value;
            case 'unique_broths':
                return $stats['unique_broths'] >= $value;
            case 'unique_noodles':
                return $stats['unique_noodles'] >= $value;
            case 'unique_oils':
                return $stats['unique_oils'] >= $value;
            case 'score_threshold':
                return $totalScore >= $value;
            case 'ingredient_count':
                return $stats['ajitama_count'] >= $value;
            case 'low_score_count':
                return $stats['low_score_count'] >= $value;
            case 'identical_bowls':
                return $stats['identical_bowls'] >= $value;
            case 'specific_combo':
                return $this->checkSpecificCombo($name, $ingredientIds, $categories);
            default:
                return false;
        }
    }

    /**
     * Check specific combo achievements based on the current bowl's ingredients.
     */
    private function checkSpecificCombo(string $achievementName, array $ids, array $categories): bool
    {
        // Group current ingredient IDs by category
        $byCategory = [];
        foreach ($categories as $id => $catId) {
            $byCategory[$catId][] = $id;
        }

        $broths   = $byCategory[1] ?? [];
        $noodles  = $byCategory[2] ?? [];
        $oils     = $byCategory[3] ?? [];
        $proteins = $byCategory[4] ?? [];
        $toppings = $byCategory[5] ?? [];

        switch ($achievementName) {
            case 'Plant Power':
                $meatProteins = array_intersect($proteins, [17, 18, 21]);
                return in_array(8, $broths)
                    && (in_array(20, $proteins) || in_array(22, $proteins))
                    && empty($meatProteins);

            case 'Classic Tonkotsu':
                return in_array(1, $broths) && in_array(17, $proteins) && in_array(13, $oils);

            case 'Old School Tokyo':
                return in_array(2, $broths) && in_array(9, $noodles)
                    && in_array(26, $toppings) && in_array(19, $proteins);

            case 'Spice Demon':
                return in_array(5, $broths) && in_array(12, $oils);

            case 'Triple Chicken Threat':
                return in_array(7, $broths) && in_array(18, $proteins) && in_array(15, $oils);

            case 'Oil Spill':
                return count($oils) >= 5;

            case 'The Hypocrite':
                return in_array(8, $broths) && in_array(17, $proteins);

            case 'Surf & Lard':
                return in_array(6, $broths) && in_array(16, $oils);

            case 'Kitchen Sink':
                return count($toppings) >= 7;

            case 'Sad Soup':
                return !empty($broths) && !empty($toppings)
                    && empty($noodles) && empty($proteins);

            case 'Naked Noodles':
                return !empty($broths) && !empty($noodles)
                    && empty($oils) && empty($proteins) && empty($toppings);

            case 'The Arsonist':
                return in_array(5, $broths) && in_array(12, $oils) && in_array(13, $oils);

            default:
                return false;
        }
    }
}
