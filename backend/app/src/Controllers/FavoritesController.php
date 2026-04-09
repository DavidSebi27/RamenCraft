<?php

namespace App\Controllers;

use App\Framework\Controller;
use App\Models\Favorite;
use App\Repositories\FavoritesRepository;

/**
 * FavoritesController — HTTP layer for saved bowl configurations.
 *
 * No SQL here. All data access goes through FavoritesRepository.
 */
class FavoritesController extends Controller
{
    /**
     * GET /api/favorites?search=&page=&limit=
     */
    public function getAll(): void
    {
        $payload = $this->authenticate();
        $userId = (int) $payload->sub;

        try {
            $search = $_GET['search'] ?? null;
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int) ($_GET['limit'] ?? 20)));

            $repo = new FavoritesRepository();
            $favorites = $repo->findByUser($userId, $search, $page, $limit);
            $total = $repo->countByUser($userId, $search);

            $this->sendSuccessResponse([
                'data'  => array_map(fn(Favorite $f) => $f->toArray(), $favorites),
                'page'  => $page,
                'limit' => $limit,
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to fetch favorites', 500);
        }
    }

    /**
     * GET /api/favorites/{id}
     */
    public function get(array $vars = []): void
    {
        $payload = $this->authenticate();
        $userId = (int) $payload->sub;

        try {
            $id = (int) ($vars['id'] ?? 0);
            if ($id <= 0) {
                $this->sendErrorResponse('Invalid favorite ID', 400);
                return;
            }

            $repo = new FavoritesRepository();
            $favorite = $repo->findByIdForUser($id, $userId);

            if (!$favorite) {
                $this->sendErrorResponse('Favorite not found', 404);
                return;
            }

            $this->sendSuccessResponse($favorite->toArray());
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to fetch favorite', 500);
        }
    }

    /**
     * POST /api/favorites
     */
    public function create(): void
    {
        $payload = $this->authenticate();
        $userId = (int) $payload->sub;

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['ingredient_ids']) || !is_array($input['ingredient_ids'])) {
                $this->sendErrorResponse('ingredient_ids is required and must be an array', 400);
                return;
            }

            $name = trim($input['name'] ?? '');
            if (empty($name)) {
                $this->sendErrorResponse('Name is required', 400);
                return;
            }

            $repo = new FavoritesRepository();
            $favId = $repo->insert($userId, $name, $input['ingredient_ids']);

            $this->sendSuccessResponse([
                'id' => $favId,
                'name' => $name,
                'message' => 'Bowl saved to favorites',
            ], 201);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to save favorite', 500);
        }
    }

    /**
     * PUT /api/favorites/{id}
     */
    public function update(array $vars = []): void
    {
        $payload = $this->authenticate();
        $userId = (int) $payload->sub;

        try {
            $id = (int) ($vars['id'] ?? 0);
            if ($id <= 0) {
                $this->sendErrorResponse('Invalid favorite ID', 400);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $this->sendErrorResponse('Request body is required', 400);
                return;
            }

            $repo = new FavoritesRepository();
            $repo->update($id, $userId, $input);

            $this->sendSuccessResponse(['id' => $id, 'message' => 'Favorite updated']);
        } catch (\RuntimeException $e) {
            $this->sendErrorResponse('Favorite not found', 404);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to update favorite', 500);
        }
    }

    /**
     * DELETE /api/favorites/{id}
     */
    public function delete(array $vars = []): void
    {
        $payload = $this->authenticate();
        $userId = (int) $payload->sub;

        try {
            $id = (int) ($vars['id'] ?? 0);
            if ($id <= 0) {
                $this->sendErrorResponse('Invalid favorite ID', 400);
                return;
            }

            $repo = new FavoritesRepository();
            $deleted = $repo->deleteForUser($id, $userId);

            if (!$deleted) {
                $this->sendErrorResponse('Favorite not found', 404);
                return;
            }

            $this->sendSuccessResponse(['message' => 'Favorite deleted']);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to delete favorite', 500);
        }
    }
}
