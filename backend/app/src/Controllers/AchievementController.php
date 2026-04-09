<?php

namespace App\Controllers;

use App\Framework\Controller;
use App\Models\Achievement;
use App\Repositories\AchievementRepository;
use App\Services\AchievementService;

/**
 * AchievementController — HTTP layer for achievements.
 *
 * No SQL here. CRUD via AchievementRepository, checking via AchievementService.
 *
 * Endpoints:
 *   GET    /api/achievements        — list all (paginated, filterable)
 *   GET    /api/achievements/{id}   — get one
 *   GET    /api/achievements/mine   — user's achievements with unlock status
 *   POST   /api/achievements/check  — evaluate after serving a bowl
 *   POST   /api/achievements        — create (authenticated)
 *   PUT    /api/achievements/{id}   — update (authenticated)
 *   DELETE /api/achievements/{id}   — delete (admin only)
 */
class AchievementController extends Controller
{
    /**
     * GET /api/achievements?search=&requirement_type=&page=&limit=
     */
    public function getAll(): void
    {
        try {
            $search = $_GET['search'] ?? null;
            $requirementType = $_GET['requirement_type'] ?? null;
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int) ($_GET['limit'] ?? 20)));

            $repo = new AchievementRepository();
            $achievements = $repo->findAll($search, $requirementType, $page, $limit);
            $total = $repo->count($search, $requirementType);

            $this->sendSuccessResponse([
                'data'  => array_map(fn(Achievement $a) => $a->toArray(), $achievements),
                'page'  => $page,
                'limit' => $limit,
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to fetch achievements', 500);
        }
    }

    /**
     * GET /api/achievements/{id}
     */
    public function get(array $vars = []): void
    {
        try {
            $id = (int) ($vars['id'] ?? 0);
            if ($id <= 0) {
                $this->sendErrorResponse('Invalid achievement ID', 400);
                return;
            }

            $repo = new AchievementRepository();
            $achievement = $repo->findById($id);

            if (!$achievement) {
                $this->sendErrorResponse('Achievement not found', 404);
                return;
            }

            $this->sendSuccessResponse($achievement->toArray());
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to fetch achievement', 500);
        }
    }

    /**
     * POST /api/achievements
     */
    public function create(): void
    {
        $this->authenticate();

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input || empty($input['name'])) {
                $this->sendErrorResponse('Field "name" is required', 400);
                return;
            }

            $repo = new AchievementRepository();
            $achievement = $repo->insert($input);

            $this->sendSuccessResponse($achievement->toArray(), 201);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to create achievement', 500);
        }
    }

    /**
     * PUT /api/achievements/{id}
     */
    public function update(array $vars = []): void
    {
        $this->authenticate();

        try {
            $id = (int) ($vars['id'] ?? 0);
            if ($id <= 0) {
                $this->sendErrorResponse('Invalid achievement ID', 400);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $this->sendErrorResponse('Request body is required', 400);
                return;
            }

            $repo = new AchievementRepository();

            if (!$repo->exists($id)) {
                $this->sendErrorResponse('Achievement not found', 404);
                return;
            }

            $achievement = $repo->update($id, $input);

            $this->sendSuccessResponse($achievement->toArray());
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to update achievement', 500);
        }
    }

    /**
     * DELETE /api/achievements/{id}
     */
    public function delete(array $vars = []): void
    {
        $this->requireAdmin();

        try {
            $id = (int) ($vars['id'] ?? 0);
            if ($id <= 0) {
                $this->sendErrorResponse('Invalid achievement ID', 400);
                return;
            }

            $repo = new AchievementRepository();
            $deleted = $repo->delete($id);

            if (!$deleted) {
                $this->sendErrorResponse('Achievement not found', 404);
                return;
            }

            $this->sendSuccessResponse(['message' => 'Achievement deleted']);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to delete achievement', 500);
        }
    }

    /**
     * GET /api/achievements/mine?unlocked=true|false&page=&limit=
     */
    public function getMyAchievements(): void
    {
        $payload = $this->authenticate();
        $userId = (int) $payload->sub;

        try {
            $unlocked = $_GET['unlocked'] ?? null;
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int) ($_GET['limit'] ?? 50)));

            $repo = new AchievementRepository();
            $achievements = $repo->findAllWithUserStatus($userId, $unlocked, $page, $limit);

            $this->sendSuccessResponse(
                array_map(fn(Achievement $a) => $a->toArrayWithStatus(), $achievements)
            );
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to fetch achievements', 500);
        }
    }

    /**
     * POST /api/achievements/check
     *
     * Expects JSON: { "ingredient_ids": [...], "total_score": 150, "bowl_id": 42 }
     */
    public function checkAchievements(): void
    {
        $payload = $this->authenticate();
        $userId = (int) $payload->sub;

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $ingredientIds = $input['ingredient_ids'] ?? [];
            $totalScore = (int) ($input['total_score'] ?? 0);

            $service = new AchievementService();
            $newlyUnlocked = $service->checkAfterServe($userId, $ingredientIds, $totalScore);

            $this->sendSuccessResponse(
                array_map(fn(Achievement $a) => $a->toArrayWithStatus(), $newlyUnlocked)
            );
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to check achievements', 500);
        }
    }
}
