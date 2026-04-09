<?php

namespace App\Controllers;

use App\Framework\Controller;
use App\Services\BowlService;

/**
 * BowlController — HTTP layer for bowl serving and history.
 *
 * Pure HTTP layer: parse request, call BowlService, send response.
 * All business logic lives in BowlService.
 *
 * Endpoints:
 *   POST /api/bowls/serve   — serve a bowl (authenticated)
 *   GET  /api/bowls/history — get the current user's bowl history (authenticated)
 */
class BowlController extends Controller
{
    /**
     * POST /api/bowls/serve
     *
     * Expects JSON: { "ingredient_ids": [1, 9, 13, 17, 26] }
     * Client-provided scores are ignored — BowlService recalculates everything.
     */
    public function serve(): void
    {
        $payload = $this->authenticate();
        $userId = (int) $payload->sub;

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $ingredientIds = array_map('intval', $input['ingredient_ids'] ?? []);

            $service = new BowlService();
            $result = $service->serve($userId, $ingredientIds);

            $this->sendSuccessResponse($result, 201);

        } catch (\InvalidArgumentException $e) {
            $this->sendErrorResponse('Invalid input: ingredient_ids is required', 400);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to serve bowl', 500);
        }
    }

    /**
     * GET /api/bowls/history?page=1&limit=10
     */
    public function history(): void
    {
        $payload = $this->authenticate();
        $userId = (int) $payload->sub;

        try {
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int) ($_GET['limit'] ?? 10)));

            $service = new BowlService();
            $result = $service->getHistory($userId, $page, $limit);

            $this->sendSuccessResponse($result);

        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to load history', 500);
        }
    }
}
