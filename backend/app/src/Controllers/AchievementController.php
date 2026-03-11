<?php

namespace App\Controllers;

use App\Config\Database;
use App\Framework\Controller;

/**
 * AchievementController — handles API requests for achievements
 *
 * Endpoints:
 *   GET    /api/achievements      — list all (paginated)
 *   GET    /api/achievements/{id} — get a single achievement
 *   POST   /api/achievements      — create a new achievement
 *   PUT    /api/achievements/{id} — update an achievement
 *   DELETE /api/achievements/{id} — delete an achievement
 */
class AchievementController extends Controller
{
    /**
     * GET /api/achievements
     *
     * Returns a paginated list of achievements.
     */
    public function getAll(): void
    {
        try {
            $db = Database::getConnection();

            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int) ($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            $countStmt = $db->query("SELECT COUNT(*) FROM achievements");
            $total = (int) $countStmt->fetchColumn();

            $sql = "SELECT * FROM achievements ORDER BY id ASC LIMIT :limit OFFSET :offset";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();

            $achievements = array_map([$this, 'formatAchievement'], $stmt->fetchAll());

            $this->sendSuccessResponse([
                'data' => $achievements,
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to fetch achievements: ' . $e->getMessage(), 500);
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

            $db = Database::getConnection();

            $stmt = $db->prepare("SELECT * FROM achievements WHERE id = :id");
            $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();

            $row = $stmt->fetch();
            if (!$row) {
                $this->sendErrorResponse('Achievement not found', 404);
                return;
            }

            $this->sendSuccessResponse($this->formatAchievement($row));
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to fetch achievement: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/achievements
     *
     * Creates a new achievement. Requires: name.
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

            $db = Database::getConnection();

            $sql = "INSERT INTO achievements (name, description, icon, requirement_type, requirement_value)
                    VALUES (:name, :description, :icon, :requirement_type, :requirement_value)";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':name' => $input['name'],
                ':description' => $input['description'] ?? null,
                ':icon' => $input['icon'] ?? null,
                ':requirement_type' => $input['requirementType'] ?? null,
                ':requirement_value' => $input['requirementValue'] ?? null,
            ]);

            $newId = (int) $db->lastInsertId();

            $fetchStmt = $db->prepare("SELECT * FROM achievements WHERE id = :id");
            $fetchStmt->bindValue(':id', $newId, \PDO::PARAM_INT);
            $fetchStmt->execute();

            $this->sendSuccessResponse($this->formatAchievement($fetchStmt->fetch()), 201);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to create achievement: ' . $e->getMessage(), 500);
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

            $db = Database::getConnection();

            $check = $db->prepare("SELECT id FROM achievements WHERE id = :id");
            $check->bindValue(':id', $id, \PDO::PARAM_INT);
            $check->execute();
            if (!$check->fetch()) {
                $this->sendErrorResponse('Achievement not found', 404);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $this->sendErrorResponse('Request body is required', 400);
                return;
            }

            $sql = "UPDATE achievements SET
                        name = COALESCE(:name, name),
                        description = COALESCE(:description, description),
                        icon = COALESCE(:icon, icon),
                        requirement_type = COALESCE(:requirement_type, requirement_type),
                        requirement_value = COALESCE(:requirement_value, requirement_value)
                    WHERE id = :id";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':name' => $input['name'] ?? null,
                ':description' => $input['description'] ?? null,
                ':icon' => $input['icon'] ?? null,
                ':requirement_type' => $input['requirementType'] ?? null,
                ':requirement_value' => $input['requirementValue'] ?? null,
                ':id' => $id,
            ]);

            $fetchStmt = $db->prepare("SELECT * FROM achievements WHERE id = :id");
            $fetchStmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $fetchStmt->execute();

            $this->sendSuccessResponse($this->formatAchievement($fetchStmt->fetch()));
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to update achievement: ' . $e->getMessage(), 500);
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

            $db = Database::getConnection();

            $stmt = $db->prepare("DELETE FROM achievements WHERE id = :id");
            $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                $this->sendErrorResponse('Achievement not found', 404);
                return;
            }

            $this->sendSuccessResponse(['message' => 'Achievement deleted']);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to delete achievement: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Convert DB row to camelCase format
     */
    private function formatAchievement(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'icon' => $row['icon'],
            'requirementType' => $row['requirement_type'],
            'requirementValue' => $row['requirement_value'] !== null ? (int) $row['requirement_value'] : null,
            'createdAt' => $row['created_at'],
        ];
    }
}
