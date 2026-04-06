<?php

namespace App\Controllers;

use App\Config\Database;
use App\Framework\Controller;

/**
 * UserController — handles API requests for user management
 *
 * Endpoints:
 *   GET    /api/users      — list all users (paginated)
 *   GET    /api/users/{id} — get a single user
 *   PUT    /api/users/{id} — update a user (role, username, etc.)
 *   DELETE /api/users/{id} — delete a user
 *
 * Note: user creation is handled by auth/register (Phase 4).
 */
class UserController extends Controller
{
    /**
     * GET /api/users
     *
     * Returns a paginated list of users (never exposes password_hash).
     */
    public function getAll(): void
    {
        try {
            $db = Database::getConnection();

            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int) ($_GET['limit'] ?? 10)));
            $offset = ($page - 1) * $limit;

            $where = '';
            $params = [];

            if (!empty($_GET['search'])) {
                $where = "WHERE (username LIKE :search OR email LIKE :search2)";
                $params[':search'] = '%' . $_GET['search'] . '%';
                $params[':search2'] = '%' . $_GET['search'] . '%';
            }

            if (!empty($_GET['role'])) {
                $where .= ($where ? ' AND' : 'WHERE') . ' role = :role';
                $params[':role'] = $_GET['role'];
            }

            if (!empty($_GET['rank'])) {
                $where .= ($where ? ' AND' : 'WHERE') . ' current_rank = :rank';
                $params[':rank'] = $_GET['rank'];
            }

            $countStmt = $db->prepare("SELECT COUNT(*) FROM users {$where}");
            $countStmt->execute($params);
            $total = (int) $countStmt->fetchColumn();

            $sql = "SELECT id, username, email, role, total_xp, current_rank, created_at, updated_at
                    FROM users {$where}
                    ORDER BY id ASC
                    LIMIT :limit OFFSET :offset";

            $stmt = $db->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();

            $users = array_map([$this, 'formatUser'], $stmt->fetchAll());

            $this->sendSuccessResponse([
                'data' => $users,
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to fetch users: ' . $e->getMessage(), 500);
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

            $db = Database::getConnection();

            $stmt = $db->prepare(
                "SELECT id, username, email, role, total_xp, current_rank, created_at, updated_at
                 FROM users WHERE id = :id"
            );
            $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();

            $row = $stmt->fetch();
            if (!$row) {
                $this->sendErrorResponse('User not found', 404);
                return;
            }

            $this->sendSuccessResponse($this->formatUser($row));
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to fetch user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/users/{id}
     *
     * Updates user fields (role, username, email). Never updates password here.
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

            // Users can only update themselves, admins can update anyone
            if ($payload->role !== 'admin' && $payload->sub !== $id) {
                $this->sendErrorResponse('Forbidden — you can only update your own profile', 403);
                return;
            }

            $db = Database::getConnection();

            $check = $db->prepare("SELECT id FROM users WHERE id = :id");
            $check->bindValue(':id', $id, \PDO::PARAM_INT);
            $check->execute();
            if (!$check->fetch()) {
                $this->sendErrorResponse('User not found', 404);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $this->sendErrorResponse('Request body is required', 400);
                return;
            }

            $sql = "UPDATE users SET
                        username = COALESCE(:username, username),
                        email = COALESCE(:email, email),
                        role = COALESCE(:role, role),
                        total_xp = COALESCE(:total_xp, total_xp),
                        current_rank = COALESCE(:current_rank, current_rank)
                    WHERE id = :id";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':username' => $input['username'] ?? null,
                ':email' => $input['email'] ?? null,
                ':role' => $input['role'] ?? null,
                ':total_xp' => $input['totalXp'] ?? null,
                ':current_rank' => $input['currentRank'] ?? null,
                ':id' => $id,
            ]);

            // Return updated user
            $fetchStmt = $db->prepare(
                "SELECT id, username, email, role, total_xp, current_rank, created_at, updated_at
                 FROM users WHERE id = :id"
            );
            $fetchStmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $fetchStmt->execute();

            $this->sendSuccessResponse($this->formatUser($fetchStmt->fetch()));
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to update user: ' . $e->getMessage(), 500);
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

            $db = Database::getConnection();

            // Clean up related data first
            $db->prepare("DELETE FROM bowl_ingredients WHERE bowl_id IN (SELECT id FROM served_bowls WHERE user_id = :uid)")->execute([':uid' => $id]);
            $db->prepare("DELETE FROM served_bowls WHERE user_id = :uid")->execute([':uid' => $id]);
            $db->prepare("DELETE FROM user_achievements WHERE user_id = :uid")->execute([':uid' => $id]);
            $db->prepare("DELETE FROM favorite_ingredients WHERE favorite_id IN (SELECT id FROM favorites WHERE user_id = :uid)")->execute([':uid' => $id]);
            $db->prepare("DELETE FROM favorites WHERE user_id = :uid")->execute([':uid' => $id]);

            $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                $this->sendErrorResponse('User not found', 404);
                return;
            }

            $this->sendSuccessResponse(['message' => 'User deleted']);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to delete user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Convert DB row to camelCase (never expose password_hash)
     */
    private function formatUser(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'username' => $row['username'],
            'email' => $row['email'],
            'role' => $row['role'],
            'totalXp' => (int) $row['total_xp'],
            'currentRank' => $row['current_rank'],
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at'],
        ];
    }
}
