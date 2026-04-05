<?php

namespace App\Controllers;

use App\Config\Database;
use App\Framework\Controller;

/**
 * FavoritesController — save and load favorite bowl configurations
 *
 * Endpoints:
 *   GET    /api/favorites          — list user's saved bowls (paginated)
 *   GET    /api/favorites/{id}     — get a single favorite with ingredients
 *   POST   /api/favorites          — save current bowl as a favorite
 *   DELETE /api/favorites/{id}     — remove a saved bowl
 */
class FavoritesController extends Controller
{
    /**
     * GET /api/favorites
     *
     * Returns the authenticated user's saved bowls with pagination.
     * Each favorite includes its ingredient list.
     * Supports: ?page=1&limit=10&search=classic
     */
    public function getAll(): void
    {
        $payload = $this->authenticate();
        $userId = (int) $payload->sub;

        try {
            $db = Database::getConnection();

            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int) ($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            // Count total
            $countSql = "SELECT COUNT(*) FROM favorites WHERE user_id = :uid";
            $countParams = [':uid' => $userId];

            if (!empty($_GET['search'])) {
                $countSql .= " AND name LIKE :search";
                $countParams[':search'] = '%' . $_GET['search'] . '%';
            }

            $countStmt = $db->prepare($countSql);
            $countStmt->execute($countParams);
            $total = (int) $countStmt->fetchColumn();

            // Fetch favorites
            $sql = "SELECT * FROM favorites WHERE user_id = :uid";
            $params = [':uid' => $userId];

            if (!empty($_GET['search'])) {
                $sql .= " AND name LIKE :search";
                $params[':search'] = '%' . $_GET['search'] . '%';
            }

            $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

            $stmt = $db->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();

            $favorites = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Fetch ingredients for each favorite
            $ingredientStmt = $db->prepare(
                "SELECT fi.ingredient_id, i.name, i.name_jp, c.name AS category_name
                 FROM favorite_ingredients fi
                 JOIN ingredients i ON fi.ingredient_id = i.id
                 JOIN categories c ON i.category_id = c.id
                 WHERE fi.favorite_id = :fid
                 ORDER BY c.sort_order, i.name"
            );

            $result = [];
            foreach ($favorites as $fav) {
                $ingredientStmt->execute([':fid' => $fav['id']]);
                $ingredients = $ingredientStmt->fetchAll(\PDO::FETCH_ASSOC);

                $result[] = [
                    'id' => (int) $fav['id'],
                    'name' => $fav['name'],
                    'createdAt' => $fav['created_at'],
                    'ingredients' => array_map(function ($ing) {
                        return [
                            'id' => (int) $ing['ingredient_id'],
                            'name' => $ing['name'],
                            'nameJp' => $ing['name_jp'],
                            'category' => $ing['category_name'],
                        ];
                    }, $ingredients),
                ];
            }

            $this->sendSuccessResponse([
                'data' => $result,
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to fetch favorites: ' . $e->getMessage(), 500);
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

            $db = Database::getConnection();

            $stmt = $db->prepare("SELECT * FROM favorites WHERE id = :id AND user_id = :uid");
            $stmt->execute([':id' => $id, ':uid' => $userId]);
            $fav = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$fav) {
                $this->sendErrorResponse('Favorite not found', 404);
                return;
            }

            // Fetch ingredients
            $ingStmt = $db->prepare(
                "SELECT fi.ingredient_id, i.name, i.name_jp, c.name AS category_name
                 FROM favorite_ingredients fi
                 JOIN ingredients i ON fi.ingredient_id = i.id
                 JOIN categories c ON i.category_id = c.id
                 WHERE fi.favorite_id = :fid
                 ORDER BY c.sort_order, i.name"
            );
            $ingStmt->execute([':fid' => $id]);

            $this->sendSuccessResponse([
                'id' => (int) $fav['id'],
                'name' => $fav['name'],
                'createdAt' => $fav['created_at'],
                'ingredients' => array_map(function ($ing) {
                    return [
                        'id' => (int) $ing['ingredient_id'],
                        'name' => $ing['name'],
                        'nameJp' => $ing['name_jp'],
                        'category' => $ing['category_name'],
                    ];
                }, $ingStmt->fetchAll(\PDO::FETCH_ASSOC)),
            ]);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to fetch favorite: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/favorites
     *
     * Save the current bowl as a favorite.
     * Expects JSON: { "name": "My Classic Bowl", "ingredient_ids": [1, 9, 13, 17, 26] }
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

            $db = Database::getConnection();
            $db->beginTransaction();

            // Insert favorite
            $stmt = $db->prepare(
                "INSERT INTO favorites (user_id, name) VALUES (:uid, :name)"
            );
            $stmt->execute([':uid' => $userId, ':name' => $name]);
            $favId = (int) $db->lastInsertId();

            // Insert ingredients
            $ingStmt = $db->prepare(
                "INSERT INTO favorite_ingredients (favorite_id, ingredient_id) VALUES (:fid, :iid)"
            );
            foreach ($input['ingredient_ids'] as $ingredientId) {
                $ingStmt->execute([':fid' => $favId, ':iid' => (int) $ingredientId]);
            }

            $db->commit();

            $this->sendSuccessResponse([
                'id' => $favId,
                'name' => $name,
                'message' => 'Bowl saved to favorites',
            ], 201);
        } catch (\Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $this->sendErrorResponse('Failed to save favorite: ' . $e->getMessage(), 500);
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

            $db = Database::getConnection();

            // Only delete if the favorite belongs to this user
            $stmt = $db->prepare("DELETE FROM favorites WHERE id = :id AND user_id = :uid");
            $stmt->execute([':id' => $id, ':uid' => $userId]);

            if ($stmt->rowCount() === 0) {
                $this->sendErrorResponse('Favorite not found', 404);
                return;
            }

            $this->sendSuccessResponse(['message' => 'Favorite deleted']);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to delete favorite: ' . $e->getMessage(), 500);
        }
    }
}
