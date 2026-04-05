<?php

namespace App\Controllers;

use App\Config\Database;
use App\Framework\Controller;

/**
 * PairingController — handles API requests for ingredient pairings
 *
 * Endpoints:
 *   GET    /api/pairings      — list all (paginated)
 *   GET    /api/pairings/{id} — get a single pairing
 *   POST   /api/pairings      — create a new pairing
 *   PUT    /api/pairings/{id} — update a pairing
 *   DELETE /api/pairings/{id} — delete a pairing
 */
class PairingController extends Controller
{
    /**
     * GET /api/pairings
     *
     * Returns a paginated list of pairings with ingredient names.
     */
    public function getAll(): void
    {
        try {
            $db = Database::getConnection();

            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int) ($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            // Build WHERE clause
            $where = '';
            $params = [];

            if (!empty($_GET['search'])) {
                $where = "WHERE p.combo_name LIKE :search OR p.description LIKE :search2";
                $params[':search'] = '%' . $_GET['search'] . '%';
                $params[':search2'] = '%' . $_GET['search'] . '%';
            }

            if (!empty($_GET['ingredient_id'])) {
                $where .= ($where ? ' AND' : 'WHERE') . ' (p.ingredient_1_id = :iid OR p.ingredient_2_id = :iid2)';
                $params[':iid'] = (int) $_GET['ingredient_id'];
                $params[':iid2'] = (int) $_GET['ingredient_id'];
            }

            // Count total
            $countSql = "SELECT COUNT(*) FROM pairings p {$where}";
            $countStmt = $db->prepare($countSql);
            $countStmt->execute($params);
            $total = (int) $countStmt->fetchColumn();

            // Fetch with ingredient names
            $sql = "SELECT p.*,
                           i1.name AS ingredient_1_name,
                           i2.name AS ingredient_2_name
                    FROM pairings p
                    JOIN ingredients i1 ON p.ingredient_1_id = i1.id
                    JOIN ingredients i2 ON p.ingredient_2_id = i2.id
                    {$where}
                    ORDER BY p.id ASC
                    LIMIT :limit OFFSET :offset";

            $stmt = $db->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();

            $pairings = array_map([$this, 'formatPairing'], $stmt->fetchAll());

            $this->sendSuccessResponse([
                'data' => $pairings,
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to fetch pairings: ' . $e->getMessage(), 500);
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

            $db = Database::getConnection();

            $sql = "SELECT p.*,
                           i1.name AS ingredient_1_name,
                           i2.name AS ingredient_2_name
                    FROM pairings p
                    JOIN ingredients i1 ON p.ingredient_1_id = i1.id
                    JOIN ingredients i2 ON p.ingredient_2_id = i2.id
                    WHERE p.id = :id";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();

            $row = $stmt->fetch();
            if (!$row) {
                $this->sendErrorResponse('Pairing not found', 404);
                return;
            }

            $this->sendSuccessResponse($this->formatPairing($row));
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to fetch pairing: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/pairings
     *
     * Creates a new pairing. Requires: ingredient1Id, ingredient2Id, scoreModifier.
     */
    public function create(): void
    {
        $this->authenticate();

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input || empty($input['ingredient1Id']) || empty($input['ingredient2Id']) || !isset($input['scoreModifier'])) {
                $this->sendErrorResponse('Fields "ingredient1Id", "ingredient2Id", and "scoreModifier" are required', 400);
                return;
            }

            $db = Database::getConnection();

            // Verify both ingredients exist
            $check = $db->prepare("SELECT id FROM ingredients WHERE id IN (:id1, :id2)");
            $check->bindValue(':id1', (int) $input['ingredient1Id'], \PDO::PARAM_INT);
            $check->bindValue(':id2', (int) $input['ingredient2Id'], \PDO::PARAM_INT);
            $check->execute();
            if ($check->rowCount() < 2 && (int) $input['ingredient1Id'] !== (int) $input['ingredient2Id']) {
                $this->sendErrorResponse('One or both ingredient IDs do not exist', 400);
                return;
            }

            $sql = "INSERT INTO pairings (ingredient_1_id, ingredient_2_id, score_modifier, combo_name, description)
                    VALUES (:ing1, :ing2, :score, :combo, :desc)";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':ing1' => (int) $input['ingredient1Id'],
                ':ing2' => (int) $input['ingredient2Id'],
                ':score' => (int) $input['scoreModifier'],
                ':combo' => $input['comboName'] ?? null,
                ':desc' => $input['description'] ?? null,
            ]);

            $newId = (int) $db->lastInsertId();

            // Fetch the created pairing
            $fetchSql = "SELECT p.*, i1.name AS ingredient_1_name, i2.name AS ingredient_2_name
                         FROM pairings p
                         JOIN ingredients i1 ON p.ingredient_1_id = i1.id
                         JOIN ingredients i2 ON p.ingredient_2_id = i2.id
                         WHERE p.id = :id";
            $fetchStmt = $db->prepare($fetchSql);
            $fetchStmt->bindValue(':id', $newId, \PDO::PARAM_INT);
            $fetchStmt->execute();

            $this->sendSuccessResponse($this->formatPairing($fetchStmt->fetch()), 201);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to create pairing: ' . $e->getMessage(), 500);
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

            $db = Database::getConnection();

            $check = $db->prepare("SELECT id FROM pairings WHERE id = :id");
            $check->bindValue(':id', $id, \PDO::PARAM_INT);
            $check->execute();
            if (!$check->fetch()) {
                $this->sendErrorResponse('Pairing not found', 404);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $this->sendErrorResponse('Request body is required', 400);
                return;
            }

            $sql = "UPDATE pairings SET
                        ingredient_1_id = COALESCE(:ing1, ingredient_1_id),
                        ingredient_2_id = COALESCE(:ing2, ingredient_2_id),
                        score_modifier = COALESCE(:score, score_modifier),
                        combo_name = COALESCE(:combo, combo_name),
                        description = COALESCE(:desc, description)
                    WHERE id = :id";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':ing1' => $input['ingredient1Id'] ?? null,
                ':ing2' => $input['ingredient2Id'] ?? null,
                ':score' => $input['scoreModifier'] ?? null,
                ':combo' => $input['comboName'] ?? null,
                ':desc' => $input['description'] ?? null,
                ':id' => $id,
            ]);

            // Return updated
            $fetchSql = "SELECT p.*, i1.name AS ingredient_1_name, i2.name AS ingredient_2_name
                         FROM pairings p
                         JOIN ingredients i1 ON p.ingredient_1_id = i1.id
                         JOIN ingredients i2 ON p.ingredient_2_id = i2.id
                         WHERE p.id = :id";
            $fetchStmt = $db->prepare($fetchSql);
            $fetchStmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $fetchStmt->execute();

            $this->sendSuccessResponse($this->formatPairing($fetchStmt->fetch()));
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to update pairing: ' . $e->getMessage(), 500);
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

            $db = Database::getConnection();

            $stmt = $db->prepare("DELETE FROM pairings WHERE id = :id");
            $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                $this->sendErrorResponse('Pairing not found', 404);
                return;
            }

            $this->sendSuccessResponse(['message' => 'Pairing deleted']);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to delete pairing: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Convert DB row to camelCase format
     */
    private function formatPairing(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'ingredient1Id' => (int) $row['ingredient_1_id'],
            'ingredient1Name' => $row['ingredient_1_name'] ?? null,
            'ingredient2Id' => (int) $row['ingredient_2_id'],
            'ingredient2Name' => $row['ingredient_2_name'] ?? null,
            'scoreModifier' => (int) $row['score_modifier'],
            'comboName' => $row['combo_name'],
            'description' => $row['description'],
        ];
    }
}
