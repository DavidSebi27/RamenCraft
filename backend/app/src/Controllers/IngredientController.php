<?php

namespace App\Controllers;

use App\Framework\Controller;
use App\Models\Ingredient;
use App\Repositories\IngredientRepository;

/**
 * IngredientController — HTTP layer for ingredient CRUD.
 *
 * No SQL here. All data access goes through IngredientRepository.
 *
 * Endpoints:
 *   GET    /api/ingredients       — list (paginated, filterable)
 *   GET    /api/ingredients/{id}  — get one
 *   POST   /api/ingredients       — create (authenticated)
 *   PUT    /api/ingredients/{id}  — update (authenticated)
 *   DELETE /api/ingredients/{id}  — delete (admin only)
 */
class IngredientController extends Controller
{
    /**
     * GET /api/ingredients?category=broth&search=pork&page=1&limit=10
     */
    public function getAll(): void
    {
        try {
            $category = $_GET['category'] ?? null;
            $search = $_GET['search'] ?? null;
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int) ($_GET['limit'] ?? 10)));

            $repo = new IngredientRepository();
            $ingredients = $repo->findAll($category, $search, $page, $limit);
            $total = $repo->count($category, $search);

            $this->sendSuccessResponse([
                'data'  => array_map(fn(Ingredient $i) => $i->toArray(), $ingredients),
                'page'  => $page,
                'limit' => $limit,
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to fetch ingredients', 500);
        }
    }

    /**
     * GET /api/ingredients/{id}
     */
    public function get(array $vars = []): void
    {
        try {
            $id = (int) ($vars['id'] ?? 0);
            if ($id <= 0) {
                $this->sendErrorResponse('Invalid ingredient ID', 400);
                return;
            }

            $repo = new IngredientRepository();
            $ingredient = $repo->findById($id);

            if (!$ingredient) {
                $this->sendErrorResponse('Ingredient not found', 404);
                return;
            }

            $this->sendSuccessResponse($ingredient->toArray());
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to fetch ingredient', 500);
        }
    }

    /**
     * POST /api/ingredients
     */
    public function create(): void
    {
        $this->authenticate();

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input || empty($input['name']) || empty($input['categoryId'])) {
                $this->sendErrorResponse('Fields "name" and "categoryId" are required', 400);
                return;
            }

            $repo = new IngredientRepository();
            $ingredient = $repo->insert($input);

            $this->sendSuccessResponse($ingredient->toArray(), 201);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to create ingredient', 500);
        }
    }

    /**
     * PUT /api/ingredients/{id}
     */
    public function update(array $vars = []): void
    {
        $this->authenticate();

        try {
            $id = (int) ($vars['id'] ?? 0);
            if ($id <= 0) {
                $this->sendErrorResponse('Invalid ingredient ID', 400);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $this->sendErrorResponse('Request body is required', 400);
                return;
            }

            $repo = new IngredientRepository();

            if (!$repo->exists($id)) {
                $this->sendErrorResponse('Ingredient not found', 404);
                return;
            }

            $ingredient = $repo->update($id, $input);

            $this->sendSuccessResponse($ingredient->toArray());
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to update ingredient', 500);
        }
    }

    /**
     * DELETE /api/ingredients/{id}
     */
    public function delete(array $vars = []): void
    {
        $this->requireAdmin();

        try {
            $id = (int) ($vars['id'] ?? 0);
            if ($id <= 0) {
                $this->sendErrorResponse('Invalid ingredient ID', 400);
                return;
            }

            $repo = new IngredientRepository();
            $deleted = $repo->delete($id);

            if (!$deleted) {
                $this->sendErrorResponse('Ingredient not found', 404);
                return;
            }

            $this->sendSuccessResponse(['message' => 'Ingredient deleted']);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to delete ingredient', 500);
        }
    }
}
