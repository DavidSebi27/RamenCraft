<?php

namespace App\Controllers;

use App\Framework\Controller;
use App\Models\User;
use App\Repositories\UserRepository;

/**
 * UserController — HTTP layer for user management.
 *
 * No SQL here. All data access goes through UserRepository.
 */
class UserController extends Controller
{
    /**
     * GET /api/users?search=&role=&rank=&page=&limit=
     */
    public function getAll(): void
    {
        try {
            $search = $_GET['search'] ?? null;
            $role = $_GET['role'] ?? null;
            $rank = $_GET['rank'] ?? null;
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int) ($_GET['limit'] ?? 10)));

            $repo = new UserRepository();
            $users = $repo->findAll($search, $role, $rank, $page, $limit);
            $total = $repo->count($search, $role, $rank);

            $this->sendSuccessResponse([
                'data'  => array_map(fn(User $u) => $u->toArray(), $users),
                'page'  => $page,
                'limit' => $limit,
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to fetch users', 500);
        }
    }

    /**
     * GET /api/users/{id}
     */
    public function get(array $vars = []): void
    {
        try {
            $id = (int) ($vars['id'] ?? 0);
            if ($id <= 0) {
                $this->sendErrorResponse('Invalid user ID', 400);
                return;
            }

            $repo = new UserRepository();
            $user = $repo->findById($id);

            if (!$user) {
                $this->sendErrorResponse('User not found', 404);
                return;
            }

            $this->sendSuccessResponse($user->toArray());
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to fetch user', 500);
        }
    }

    /**
     * PUT /api/users/{id}
     */
    public function update(array $vars = []): void
    {
        $payload = $this->authenticate();

        try {
            $id = (int) ($vars['id'] ?? 0);
            if ($id <= 0) {
                $this->sendErrorResponse('Invalid user ID', 400);
                return;
            }

            if ($payload->role !== 'admin' && $payload->sub !== $id) {
                $this->sendErrorResponse('Forbidden — you can only update your own profile', 403);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $this->sendErrorResponse('Request body is required', 400);
                return;
            }

            $repo = new UserRepository();

            if (!$repo->exists($id)) {
                $this->sendErrorResponse('User not found', 404);
                return;
            }

            $user = $repo->update($id, $input);

            $this->sendSuccessResponse($user->toArray());
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to update user', 500);
        }
    }

    /**
     * DELETE /api/users/{id}
     */
    public function delete(array $vars = []): void
    {
        $this->requireAdmin();

        try {
            $id = (int) ($vars['id'] ?? 0);
            if ($id <= 0) {
                $this->sendErrorResponse('Invalid user ID', 400);
                return;
            }

            $repo = new UserRepository();
            $deleted = $repo->delete($id);

            if (!$deleted) {
                $this->sendErrorResponse('User not found', 404);
                return;
            }

            $this->sendSuccessResponse(['message' => 'User deleted']);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to delete user', 500);
        }
    }
}
