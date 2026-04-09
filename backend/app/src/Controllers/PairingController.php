<?php

namespace App\Controllers;

use App\Framework\Controller;
use App\Models\Pairing;
use App\Repositories\PairingRepository;

/**
 * PairingController — HTTP layer for pairing CRUD.
 *
 * No SQL here. All data access goes through PairingRepository.
 */
class PairingController extends Controller
{
    /**
     * GET /api/pairings?search=&ingredient_id=&page=&limit=
     */
    public function getAll(): void
    {
        try {
            $search = $_GET['search'] ?? null;
            $ingredientId = isset($_GET['ingredient_id']) ? (int) $_GET['ingredient_id'] : null;
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int) ($_GET['limit'] ?? 20)));

            $repo = new PairingRepository();
            $pairings = $repo->findAll($search, $ingredientId, $page, $limit);
            $total = $repo->count($search, $ingredientId);

            $this->sendSuccessResponse([
                'data'  => array_map(fn(Pairing $p) => $p->toArray(), $pairings),
                'page'  => $page,
                'limit' => $limit,
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to fetch pairings', 500);
        }
    }

    /**
     * GET /api/pairings/{id}
     */
    public function get(array $vars = []): void
    {
        try {
            $id = (int) ($vars['id'] ?? 0);
            if ($id <= 0) {
                $this->sendErrorResponse('Invalid pairing ID', 400);
                return;
            }

            $repo = new PairingRepository();
            $pairing = $repo->findById($id);

            if (!$pairing) {
                $this->sendErrorResponse('Pairing not found', 404);
                return;
            }

            $this->sendSuccessResponse($pairing->toArray());
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to fetch pairing', 500);
        }
    }

    /**
     * POST /api/pairings
     */
    public function create(): void
    {
        $this->authenticate();

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input || empty($input['ingredient1Id']) || empty($input['ingredient2Id'])) {
                $this->sendErrorResponse('Fields "ingredient1Id" and "ingredient2Id" are required', 400);
                return;
            }

            $repo = new PairingRepository();
            $pairing = $repo->insert($input);

            $this->sendSuccessResponse($pairing->toArray(), 201);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to create pairing', 500);
        }
    }

    /**
     * PUT /api/pairings/{id}
     */
    public function update(array $vars = []): void
    {
        $this->authenticate();

        try {
            $id = (int) ($vars['id'] ?? 0);
            if ($id <= 0) {
                $this->sendErrorResponse('Invalid pairing ID', 400);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $this->sendErrorResponse('Request body is required', 400);
                return;
            }

            $repo = new PairingRepository();

            if (!$repo->exists($id)) {
                $this->sendErrorResponse('Pairing not found', 404);
                return;
            }

            $pairing = $repo->update($id, $input);

            $this->sendSuccessResponse($pairing->toArray());
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to update pairing', 500);
        }
    }

    /**
     * DELETE /api/pairings/{id}
     */
    public function delete(array $vars = []): void
    {
        $this->requireAdmin();

        try {
            $id = (int) ($vars['id'] ?? 0);
            if ($id <= 0) {
                $this->sendErrorResponse('Invalid pairing ID', 400);
                return;
            }

            $repo = new PairingRepository();
            $deleted = $repo->delete($id);

            if (!$deleted) {
                $this->sendErrorResponse('Pairing not found', 404);
                return;
            }

            $this->sendSuccessResponse(['message' => 'Pairing deleted']);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to delete pairing', 500);
        }
    }
}
